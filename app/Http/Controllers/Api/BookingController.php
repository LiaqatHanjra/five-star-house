<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\BookingConfirmed;
use App\Mail\ProjectRequest;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Services\GoogleCalendar;
use App\Services\PayPal;
use App\Support\BookingPageContent;
use App\Support\CheckoutDraft;
use App\Support\StudioSchedule;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class BookingController extends Controller
{
    public function __construct(
        private StudioSchedule $schedule,
        private GoogleCalendar $google,
        private PayPal $paypal,
    ) {
    }

    public function studio()
    {
        $pairs = SiteSetting::allPairs();
        $public = [
            'owner_name', 'owner_email', 'phone', 'address', 'open_time', 'close_time',
            'instagram_url', 'youtube_url', 'vimeo_url',
            'minimum_hours', 'studio_minimum_hours', 'currency',
            'studio_hourly_rate', 'event_hourly_rate', 'owner_hourly_rate',
        ];

        return response()->json([
            'settings' => array_intersect_key($pairs, array_flip($public)),
            'content' => BookingPageContent::resolved(),
        ]);
    }

    public function availability(Request $request)
    {
        $data = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'date' => ['nullable', 'date'],
            'booking_kind' => ['nullable', Rule::in(['service', 'studio_time', 'studio_event', 'owner_event'])],
            'service_id' => ['required_if:booking_kind,service', 'nullable', 'integer', 'exists:services,id'],
        ]);

        $window = $this->schedule->window();
        $minimumHours = isset($data['booking_kind'])
            ? $this->rateFor($data['booking_kind'], isset($data['service_id']) ? (int) $data['service_id'] : null)['minimum_hours']
            : $window['minimum_hours'];
        $window['minimum_hours'] = $minimumHours;
        $payload = ['window' => $window];

        if (! empty($data['month'])) {
            $marks = $this->schedule->monthMarks($data['month']);
            $payload['marks'] = $marks;
            $payload['free'] = $this->schedule->freeHours($data['month'], $marks);
        }

        if (! empty($data['date'])) {
            $payload['slots'] = $this->schedule->daySlots($data['date'], $minimumHours);
        }

        return response()->json($payload);
    }

    public function quote(Request $request)
    {
        $data = $request->validate([
            'booking_kind' => ['required', Rule::in(['service', 'studio_time', 'studio_event', 'owner_event'])],
            'service_id' => ['required_if:booking_kind,service', 'nullable', 'integer', 'exists:services,id'],
            'hours' => ['required', 'integer', 'min:1', 'max:12'],
            'charge_unit' => ['nullable', Rule::in(['hour', 'day', 'project'])],
        ]);

        $rate = $this->rateFor($data['booking_kind'], $data['service_id'] ?? null, $data['charge_unit'] ?? 'hour');
        $flat = $this->flatService($data);
        if (! $flat && $data['hours'] < $rate['minimum_hours']) {
            return response()->json([
                'message' => 'This booking needs at least '.$rate['minimum_hours'].' hours.',
            ], 422);
        }

        $amount = $flat
            ? number_format($rate['hourly_rate'], 2, '.', '')
            : number_format($rate['hourly_rate'] * $data['hours'], 2, '.', '');

        return response()->json([
            'hourly_rate' => $rate['hourly_rate'],
            'currency' => $rate['currency'],
            'minimum_hours' => $rate['minimum_hours'],
            'hours' => $flat ? 1 : $data['hours'],
            'charge_unit' => $flat ? $data['charge_unit'] : 'hour',
            'amount' => $amount,
            'label' => $rate['label'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'booking_kind' => ['required', Rule::in(['service', 'studio_time', 'studio_event', 'owner_event'])],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'hours' => ['required', 'integer', 'min:1', 'max:12'],
            'charge_unit' => ['nullable', Rule::in(['hour', 'day', 'project'])],
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:180'],
            'event_title' => ['nullable', 'string', 'max:160'],
            'message' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($data['booking_kind'] === 'service' && empty($data['service_id'])) {
            return response()->json(['message' => 'Choose a service to book.'], 422);
        }

        $flat = $this->flatService($data);
        if ($flat) {
            $data = $this->applyDaySpan($data);
        }

        $rate = $this->rateFor($data['booking_kind'], $data['service_id'] ?? null, $data['charge_unit'] ?? 'hour');
        if (! $flat && $data['hours'] < $rate['minimum_hours']) {
            return response()->json([
                'message' => 'This booking needs at least '.$rate['minimum_hours'].' hours.',
            ], 422);
        }

        if (! $this->schedule->isAvailable(
            $data['booking_date'],
            $data['start_time'],
            (int) $data['hours'],
            $rate['minimum_hours']
        )) {
            return response()->json([
                'message' => 'That time is already booked or outside studio hours.',
            ], 422);
        }

        $start = Carbon::parse($data['booking_date'].' '.$data['start_time']);
        $end = $start->copy()->addHours((int) $data['hours']);
        $amount = $flat
            ? round($rate['hourly_rate'], 2)
            : round($rate['hourly_rate'] * $data['hours'], 2);
        $service = ! empty($data['service_id'])
            ? Service::query()->find($data['service_id'])
            : null;

        $draft = [
            'reference' => $this->reference(),
            'payment_reference' => $this->paymentReference(),
            'booking_kind' => $data['booking_kind'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'service_id' => $data['service_id'] ?? null,
            'service_slug' => $service?->slug,
            'project_type' => $rate['label'],
            'event_title' => $data['event_title'] ?? null,
            'booking_date' => $data['booking_date'],
            'start_time' => $start->format('H:i:s'),
            'end_time' => $end->format('H:i:s'),
            'hours' => (int) $data['hours'],
            'charge_unit' => $flat ? $data['charge_unit'] : ($data['charge_unit'] ?? 'hour'),
            'hourly_rate' => $rate['hourly_rate'],
            'amount' => $amount,
            'currency' => $rate['currency'],
            'message' => $data['message'] ?? '',
            'stripe_session_id' => null,
            'paypal_order_id' => null,
        ];
        CheckoutDraft::put($draft);

        return response()->json(CheckoutDraft::present($draft), 201);
    }

    public function inquire(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $service = Service::query()->where('is_active', true)->find($data['service_id']);
        if (! $service) {
            return response()->json(['message' => 'Choose a project type.'], 422);
        }

        Customer::query()->updateOrCreate(
            ['email' => $data['email']],
            ['name' => $data['full_name']]
        );

        $booking = Booking::create([
            'reference' => $this->reference(),
            'booking_kind' => 'project',
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'address' => '',
            'service_id' => $service->id,
            'project_type' => $service->title,
            'message' => $data['message'],
            'timeline' => now()->toDateString(),
            'budget' => 'inquiry',
            'hours' => 0,
            'hourly_rate' => 0,
            'amount' => 0,
            'status' => 'new',
        ]);

        return response()->json([
            'booking' => $booking->toPublicArray(),
            'mailed' => $this->notifyInquiry($booking),
        ], 201);
    }

    public function show(string $reference)
    {
        $booking = Booking::query()->with(['payment', 'service'])->where('reference', $reference)->first();
        if ($booking) {
            return response()->json([
                'booking' => $booking->toPublicArray(),
                'payment' => $booking->payment?->toPublicArray(),
            ]);
        }

        $draft = CheckoutDraft::get($reference);
        if (! $draft) {
            return response()->json(['message' => 'This booking could not be found.'], 404);
        }

        return response()->json(CheckoutDraft::present($draft));
    }

    public function pay(Request $request, string $reference)
    {
        $data = $request->validate([
            'method' => ['required', Rule::in(['stripe', 'paypal'])],
        ]);
        $booking = Booking::query()->with(['payment', 'service'])->where('reference', $reference)->first();
        if (! $booking) {
            $draft = CheckoutDraft::get($reference);
            if (! $draft) {
                return response()->json(['message' => 'This booking could not be found.'], 404);
            }

            return $data['method'] === 'paypal'
                ? $this->payDraftPaypal($draft)
                : $this->payDraftStripe($draft);
        }
        $payment = $booking->payment;

        if (! $payment) {
            return response()->json(['message' => 'This booking has no payment.'], 422);
        }

        if ($payment->status === 'paid') {
            return response()->json([
                'message' => 'Payment has already been received.',
                'paid' => true,
            ]);
        }

        if ($data['method'] === 'paypal') {
            return $this->startPaypal($booking, $payment);
        }

        $secret = config('services.stripe.secret');
        if (! $secret) {
            return response()->json(['message' => 'Online payments are not configured. Please contact the studio.'], 503);
        }

        if ($payment->stripe_session_id) {
            try {
                $existing = Http::withOptions(['verify' => true])
                    ->withBasicAuth($secret, '')
                    ->get('https://api.stripe.com/v1/checkout/sessions/'.$payment->stripe_session_id);
            } catch (ConnectionException $exception) {
                report($exception);

                return response()->json(['message' => 'Unable to connect to Stripe. Please try again.'], 502);
            }

            if (! $existing->successful()) {
                report(new RuntimeException('Stripe could not retrieve checkout session '.$payment->stripe_session_id));

                return response()->json(['message' => 'Unable to open Stripe checkout. Please try again.'], 502);
            }

            $session = $existing->json();
            if (($session['status'] ?? null) === 'complete') {
                if (! $this->sessionMatchesPayment($session, $payment, $booking)) {
                    return response()->json(['message' => 'Stripe payment details do not match this booking.'], 422);
                }

                if (($session['payment_status'] ?? null) === 'paid') {
                    $this->markPaymentPaid($payment, $booking);

                    return response()->json([
                        'message' => 'Payment has already been received.',
                        'paid' => true,
                    ]);
                }

                return response()->json(['message' => 'Payment is still processing. Please wait before trying again.'], 409);
            }

            if (($session['status'] ?? null) === 'open' && ! empty($session['url'])) {
                return response()->json(['checkout_url' => $session['url']]);
            }
        }

        $baseUrl = rtrim(config('app.url'), '/');
        try {
            $response = Http::withOptions(['verify' => true])
                ->withBasicAuth($secret, '')
                ->asForm()
                ->post('https://api.stripe.com/v1/checkout/sessions', [
                    'mode' => 'payment',
                    'success_url' => $baseUrl.'/booked/'.$booking->reference.'?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => $baseUrl.'/pay/'.$booking->reference.'?cancelled=1',
                    'customer_email' => $booking->email,
                    'client_reference_id' => $payment->reference,
                    'metadata[payment_id]' => (string) $payment->id,
                    'metadata[booking_reference]' => $booking->reference,
                    'payment_intent_data[metadata][payment_id]' => (string) $payment->id,
                    'payment_intent_data[metadata][booking_reference]' => $booking->reference,
                    'line_items[0][price_data][currency]' => strtolower($payment->currency),
                    'line_items[0][price_data][product_data][name]' => $booking->project_type,
                    'line_items[0][price_data][unit_amount]' => (int) round((float) $payment->amount * 100),
                    'line_items[0][quantity]' => 1,
                ]);
        } catch (ConnectionException $exception) {
            report($exception);

            return response()->json(['message' => 'Unable to connect to Stripe. Please try again.'], 502);
        }

        if (! $response->successful() || empty($response->json('id')) || empty($response->json('url'))) {
            report(new RuntimeException('Stripe could not create a checkout session: '.$response->body()));

            return response()->json(['message' => 'Unable to start Stripe checkout. Please try again.'], 502);
        }

        $session = $response->json();
        $payment->update(['stripe_session_id' => $session['id']]);

        return response()->json([
            'checkout_url' => $session['url'],
        ]);
    }

    public function verifyPayment(Request $request, string $reference)
    {
        $data = $request->validate([
            'session_id' => ['required_without:paypal_order', 'nullable', 'string', 'max:255'],
            'paypal_order' => ['required_without:session_id', 'nullable', 'string', 'max:255'],
        ]);

        if (! empty($data['paypal_order'])) {
            return $this->verifyPaypal($data['paypal_order'], $reference);
        }

        $booking = Booking::query()->with(['payment', 'service'])->where('reference', $reference)->first();
        if (! $booking) {
            return $this->verifyDraftStripe($reference, $data['session_id']);
        }
        $payment = $booking->payment;

        if (! $payment || ! hash_equals((string) $payment->stripe_session_id, $data['session_id'])) {
            return response()->json(['message' => 'This Stripe session does not match the booking.'], 403);
        }

        if ($payment->status !== 'paid') {
            $secret = config('services.stripe.secret');
            if (! $secret) {
                return response()->json(['message' => 'Online payments are not configured. Please contact the studio.'], 503);
            }

            try {
                $response = Http::withOptions(['verify' => config('services.stripe.ca_bundle') ?: true])
                    ->withBasicAuth($secret, '')
                    ->get('https://api.stripe.com/v1/checkout/sessions/'.$data['session_id']);
            } catch (ConnectionException $exception) {
                report($exception);

                return response()->json(['message' => 'Unable to connect to Stripe. Please try again.'], 502);
            }

            if (! $response->successful()) {
                report(new RuntimeException('Stripe could not verify checkout session '.$data['session_id']));

                return response()->json(['message' => 'Unable to verify payment with Stripe. Please try again.'], 502);
            }

            $session = $response->json();
            if (! $this->sessionMatchesPayment($session, $payment, $booking)) {
                return response()->json(['message' => 'Stripe payment details do not match this booking.'], 422);
            }

            if (($session['payment_status'] ?? null) === 'paid') {
                $this->markPaymentPaid($payment, $booking);
            }
        }

        $booking->load(['payment', 'service']);

        return response()->json([
            'booking' => $booking->toPublicArray(),
            'payment' => $booking->payment?->toPublicArray(),
        ]);
    }

    public function stripeWebhook(Request $request)
    {
        $webhookSecret = config('services.stripe.webhook_secret');
        if (! $webhookSecret) {
            return response()->json(['message' => 'Stripe webhooks are not configured.'], 503);
        }

        $signatureHeader = $request->header('Stripe-Signature', '');
        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $signatureHeader) as $part) {
            $values = explode('=', trim($part), 2);
            if (count($values) !== 2) {
                continue;
            }
            if ($values[0] === 't') {
                $timestamp = $values[1];
            } elseif ($values[0] === 'v1') {
                $signatures[] = $values[1];
            }
        }
        $payload = $request->getContent();

        if (! $timestamp || ! $signatures || abs(now()->timestamp - (int) $timestamp) > 300) {
            return response()->json(['message' => 'Invalid Stripe signature.'], 400);
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $webhookSecret);
        if (! collect($signatures)->contains(fn (string $signature) => hash_equals($expected, $signature))) {
            return response()->json(['message' => 'Invalid Stripe signature.'], 400);
        }

        $event = json_decode($payload, true);
        if (! is_array($event)) {
            return response()->json(['message' => 'Invalid Stripe event.'], 400);
        }

        if (in_array($event['type'] ?? null, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            $session = $event['data']['object'] ?? [];
            $paymentId = $session['metadata']['payment_id'] ?? null;
            if ($paymentId && ($session['payment_status'] ?? null) === 'paid') {
                $payment = Payment::query()->with('booking')->find($paymentId);
                if ($payment && $payment->booking && $this->sessionMatchesPayment($session, $payment, $payment->booking)) {
                    $this->markPaymentPaid($payment, $payment->booking);
                }
            }

            $reference = $session['metadata']['booking_reference'] ?? null;
            if (is_string($reference) && $reference !== '' && ($session['payment_status'] ?? null) === 'paid') {
                $draft = CheckoutDraft::get($reference);
                if ($draft && $this->draftMatchesSession($session, $draft)) {
                    $this->completeDraft($draft, 'stripe', [
                        'stripe_session_id' => $session['id'] ?? $draft['stripe_session_id'],
                    ]);
                }
            }
        }

        return response()->json(['received' => true]);
    }

    private function sessionMatchesPayment(array $session, Payment $payment, Booking $booking): bool
    {
        return ($session['id'] ?? null) === $payment->stripe_session_id
            && ($session['mode'] ?? null) === 'payment'
            && (string) ($session['metadata']['payment_id'] ?? '') === (string) $payment->id
            && ($session['metadata']['booking_reference'] ?? null) === $booking->reference
            && strtolower((string) ($session['currency'] ?? '')) === strtolower($payment->currency)
            && (int) ($session['amount_total'] ?? 0) === (int) round((float) $payment->amount * 100);
    }

    private function startPaypal(Booking $booking, Payment $payment)
    {
        if (! $this->paypal->configured()) {
            return response()->json(['message' => 'PayPal is not configured. Please contact the studio.'], 503);
        }

        try {
            if ($payment->paypal_order_id) {
                $existing = $this->paypal->order($payment->paypal_order_id);
                if ($this->paypal->isPaid($existing, $payment)) {
                    $this->markPaymentPaid($payment, $booking, 'paypal');

                    return response()->json([
                        'message' => 'Payment has already been received.',
                        'paid' => true,
                    ]);
                }

                if (($existing['status'] ?? null) === 'APPROVED') {
                    $captured = $this->paypal->capture($payment->paypal_order_id);
                    if ($this->paypal->isPaid($captured, $payment)) {
                        $this->markPaymentPaid($payment, $booking, 'paypal');

                        return response()->json([
                            'message' => 'Payment has already been received.',
                            'paid' => true,
                        ]);
                    }
                }

                $url = $this->paypal->approveUrl($existing);
                if ($url && in_array($existing['status'] ?? null, ['CREATED', 'PAYER_ACTION_REQUIRED', 'SAVED'], true)) {
                    return response()->json(['checkout_url' => $url]);
                }
            }

            $created = $this->paypal->createOrder($payment, $booking);
            $payment->update(['paypal_order_id' => $created['id']]);
        } catch (ConnectionException $exception) {
            report($exception);

            return response()->json(['message' => 'Unable to connect to PayPal. Please try again.'], 502);
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json(['message' => 'Unable to start PayPal checkout. Please try again.'], 502);
        }

        return response()->json([
            'checkout_url' => $created['url'],
        ]);
    }

    private function verifyPaypal(string $orderId, string $reference)
    {
        $booking = Booking::query()->with(['payment', 'service'])->where('reference', $reference)->first();
        if (! $booking) {
            return $this->verifyDraftPaypal($orderId, $reference);
        }
        $payment = $booking->payment;

        if (! $payment || ! hash_equals((string) $payment->paypal_order_id, $orderId)) {
            return response()->json(['message' => 'This PayPal order does not match the booking.'], 403);
        }

        if ($payment->status !== 'paid') {
            if (! $this->paypal->configured()) {
                return response()->json(['message' => 'PayPal is not configured. Please contact the studio.'], 503);
            }

            try {
                $order = $this->paypal->capture($orderId);
            } catch (ConnectionException $exception) {
                report($exception);

                return response()->json(['message' => 'Unable to connect to PayPal. Please try again.'], 502);
            } catch (RuntimeException $exception) {
                report($exception);

                return response()->json(['message' => 'Unable to verify payment with PayPal. Please try again.'], 502);
            }

            if ($this->paypal->isPaid($order, $payment)) {
                $this->markPaymentPaid($payment, $booking, 'paypal');
            }
        }

        $booking->load(['payment', 'service']);

        return response()->json([
            'booking' => $booking->toPublicArray(),
            'payment' => $booking->payment?->toPublicArray(),
        ]);
    }

    private function markPaymentPaid(Payment $payment, Booking $booking, string $method = 'stripe'): void
    {
        if ($payment->status === 'paid') {
            return;
        }

        $payment->update([
            'status' => 'paid',
            'method' => $method,
            'paid_at' => now(),
        ]);
        $booking->update(['status' => 'confirmed']);
        $this->google->push($booking->fresh());

        try {
            $this->notify($booking->fresh(['payment', 'service']));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function notify(Booking $booking): void
    {
        Mail::to($booking->email)->send(new BookingConfirmed($booking, 'customer'));

        $owners = array_values(array_unique(array_filter([
            SiteSetting::getValue('owner_email', 'studiofivestarmtl@gmail.com'),
            SiteSetting::getValue('owner_email_secondary', 'vladimirphilogene@gmail.com'),
        ])));

        if ($owners !== []) {
            Mail::to($owners)->send(new BookingConfirmed($booking, 'owner'));
        }
    }

    private function notifyInquiry(Booking $booking): bool
    {
        $sent = true;

        try {
            Mail::to($booking->email)->send(new ProjectRequest($booking, 'customer'));
        } catch (\Throwable $exception) {
            report($exception);
            $sent = false;
        }

        $owners = array_values(array_unique(array_filter([
            SiteSetting::getValue('owner_email', 'studiofivestarmtl@gmail.com'),
            SiteSetting::getValue('owner_email_secondary', 'vladimirphilogene@gmail.com'),
        ])));

        if ($owners === []) {
            return false;
        }

        try {
            Mail::to($owners)->send(new ProjectRequest($booking, 'owner'));
        } catch (\Throwable $exception) {
            report($exception);
            $sent = false;
        }

        return $sent;
    }

    private function flatService(array $data): bool
    {
        return ($data['booking_kind'] ?? '') === 'service'
            && in_array($data['charge_unit'] ?? 'hour', ['day', 'project'], true);
    }

    private function applyDaySpan(array $data): array
    {
        $window = $this->schedule->window();
        $data['start_time'] = $window['open'];
        $data['hours'] = max(1, count($this->schedule->slotStarts()));

        return $data;
    }

    private function rateFor(string $kind, ?int $serviceId, string $unit = 'hour'): array
    {
        $currency = SiteSetting::getValue('currency', 'CAD');
        $minimum = max(1, (int) SiteSetting::getValue('minimum_hours', '1'));

        if ($kind === 'service') {
            $service = Service::query()->where('is_active', true)->findOrFail($serviceId);
            $price = match ($unit) {
                'day' => (float) $service->day_charge,
                'project' => (float) $service->project_charge,
                default => (float) $service->charge,
            };

            return [
                'hourly_rate' => $price,
                'currency' => $service->currency ?: $currency,
                'minimum_hours' => max(1, (int) ($service->minimum_hours ?: $minimum)),
                'label' => $service->title,
            ];
        }

        $key = match ($kind) {
            'studio_event' => 'event_hourly_rate',
            'owner_event' => 'owner_hourly_rate',
            default => 'studio_hourly_rate',
        };
        $label = match ($kind) {
            'studio_event' => 'Studio for an event',
            'owner_event' => 'Book the owner',
            default => 'Studio time',
        };

        return [
            'hourly_rate' => (float) SiteSetting::getValue($key, $kind === 'studio_time' ? '75' : '150'),
            'currency' => $currency,
            'minimum_hours' => $kind === 'studio_time'
                ? max(1, (int) SiteSetting::getValue('studio_minimum_hours', '1'))
                : $minimum,
            'label' => $label,
        ];
    }

    private function reference(): string
    {
        do {
            $reference = 'FSH-'.strtoupper(Str::random(8));
        } while (Booking::query()->where('reference', $reference)->exists() || CheckoutDraft::exists($reference));

        return $reference;
    }

    private function paymentReference(): string
    {
        do {
            $reference = 'PAY-'.strtoupper(Str::random(8));
        } while (Payment::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function payDraftStripe(array $draft)
    {
        $secret = config('services.stripe.secret');
        if (! $secret) {
            return response()->json(['message' => 'Online payments are not configured. Please contact the studio.'], 503);
        }

        if (! empty($draft['stripe_session_id'])) {
            try {
                $existing = Http::withOptions(['verify' => true])
                    ->withBasicAuth($secret, '')
                    ->get('https://api.stripe.com/v1/checkout/sessions/'.$draft['stripe_session_id']);
            } catch (ConnectionException $exception) {
                report($exception);

                return response()->json(['message' => 'Unable to connect to Stripe. Please try again.'], 502);
            }

            if ($existing->successful()) {
                $session = $existing->json();
                if (($session['payment_status'] ?? null) === 'paid' && $this->draftMatchesSession($session, $draft)) {
                    $this->completeDraft($draft, 'stripe', ['stripe_session_id' => $session['id']]);

                    return response()->json([
                        'message' => 'Payment has already been received.',
                        'paid' => true,
                    ]);
                }

                if (($session['status'] ?? null) === 'open' && ! empty($session['url'])) {
                    return response()->json(['checkout_url' => $session['url']]);
                }
            }
        }

        $baseUrl = rtrim(config('app.url'), '/');
        try {
            $response = Http::withOptions(['verify' => true])
                ->withBasicAuth($secret, '')
                ->asForm()
                ->post('https://api.stripe.com/v1/checkout/sessions', [
                    'mode' => 'payment',
                    'success_url' => $baseUrl.'/booked/'.$draft['reference'].'?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => $baseUrl.'/pay/'.$draft['reference'].'?cancelled=1',
                    'customer_email' => $draft['email'],
                    'client_reference_id' => $draft['payment_reference'],
                    'metadata[booking_reference]' => $draft['reference'],
                    'payment_intent_data[metadata][booking_reference]' => $draft['reference'],
                    'line_items[0][price_data][currency]' => strtolower($draft['currency']),
                    'line_items[0][price_data][product_data][name]' => $draft['project_type'],
                    'line_items[0][price_data][unit_amount]' => (int) round((float) $draft['amount'] * 100),
                    'line_items[0][quantity]' => 1,
                ]);
        } catch (ConnectionException $exception) {
            report($exception);

            return response()->json(['message' => 'Unable to connect to Stripe. Please try again.'], 502);
        }

        if (! $response->successful() || empty($response->json('id')) || empty($response->json('url'))) {
            report(new RuntimeException('Stripe could not create a checkout session: '.$response->body()));

            return response()->json(['message' => 'Unable to start Stripe checkout. Please try again.'], 502);
        }

        CheckoutDraft::merge($draft['reference'], ['stripe_session_id' => $response->json('id')]);

        return response()->json([
            'checkout_url' => $response->json('url'),
        ]);
    }

    private function payDraftPaypal(array $draft)
    {
        if (! $this->paypal->configured()) {
            return response()->json(['message' => 'PayPal is not configured. Please contact the studio.'], 503);
        }

        $payment = $this->draftPayment($draft);
        $booking = $this->draftBooking($draft);

        try {
            if (! empty($draft['paypal_order_id'])) {
                $payment->paypal_order_id = $draft['paypal_order_id'];
                $existing = $this->paypal->order($draft['paypal_order_id']);
                if ($this->paypal->isPaid($existing, $payment)) {
                    $this->completeDraft($draft, 'paypal', ['paypal_order_id' => $draft['paypal_order_id']]);

                    return response()->json([
                        'message' => 'Payment has already been received.',
                        'paid' => true,
                    ]);
                }

                if (($existing['status'] ?? null) === 'APPROVED') {
                    $captured = $this->paypal->capture($draft['paypal_order_id']);
                    if ($this->paypal->isPaid($captured, $payment)) {
                        $this->completeDraft($draft, 'paypal', ['paypal_order_id' => $draft['paypal_order_id']]);

                        return response()->json([
                            'message' => 'Payment has already been received.',
                            'paid' => true,
                        ]);
                    }
                }

                $url = $this->paypal->approveUrl($existing);
                if ($url && in_array($existing['status'] ?? null, ['CREATED', 'PAYER_ACTION_REQUIRED', 'SAVED'], true)) {
                    return response()->json(['checkout_url' => $url]);
                }
            }

            $created = $this->paypal->createOrder($payment, $booking);
            CheckoutDraft::merge($draft['reference'], ['paypal_order_id' => $created['id']]);
        } catch (ConnectionException $exception) {
            report($exception);

            return response()->json(['message' => 'Unable to connect to PayPal. Please try again.'], 502);
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json(['message' => 'Unable to start PayPal checkout. Please try again.'], 502);
        }

        return response()->json([
            'checkout_url' => $created['url'],
        ]);
    }

    private function verifyDraftStripe(string $reference, string $sessionId)
    {
        $draft = CheckoutDraft::get($reference);
        $booking = Booking::query()->with(['payment', 'service'])->where('reference', $reference)->first();
        if ($booking?->payment?->status === 'paid') {
            return response()->json([
                'booking' => $booking->toPublicArray(),
                'payment' => $booking->payment->toPublicArray(),
            ]);
        }

        if (! $draft || empty($draft['stripe_session_id']) || ! hash_equals((string) $draft['stripe_session_id'], $sessionId)) {
            return response()->json(['message' => 'This Stripe session does not match the booking.'], 403);
        }

        $secret = config('services.stripe.secret');
        if (! $secret) {
            return response()->json(['message' => 'Online payments are not configured. Please contact the studio.'], 503);
        }

        try {
            $response = Http::withOptions(['verify' => config('services.stripe.ca_bundle') ?: true])
                ->withBasicAuth($secret, '')
                ->get('https://api.stripe.com/v1/checkout/sessions/'.$sessionId);
        } catch (ConnectionException $exception) {
            report($exception);

            return response()->json(['message' => 'Unable to connect to Stripe. Please try again.'], 502);
        }

        if (! $response->successful()) {
            report(new RuntimeException('Stripe could not verify checkout session '.$sessionId));

            return response()->json(['message' => 'Unable to verify payment with Stripe. Please try again.'], 502);
        }

        $session = $response->json();
        if (! $this->draftMatchesSession($session, $draft)) {
            return response()->json(['message' => 'Stripe payment details do not match this booking.'], 422);
        }

        if (($session['payment_status'] ?? null) !== 'paid') {
            return response()->json(CheckoutDraft::present($draft));
        }

        $booking = $this->completeDraft($draft, 'stripe', ['stripe_session_id' => $session['id']]);

        return response()->json([
            'booking' => $booking->toPublicArray(),
            'payment' => $booking->payment?->toPublicArray(),
        ]);
    }

    private function verifyDraftPaypal(string $orderId, string $reference)
    {
        $draft = CheckoutDraft::get($reference);
        $booking = Booking::query()->with(['payment', 'service'])->where('reference', $reference)->first();
        if ($booking?->payment?->status === 'paid') {
            return response()->json([
                'booking' => $booking->toPublicArray(),
                'payment' => $booking->payment->toPublicArray(),
            ]);
        }

        if (! $draft || empty($draft['paypal_order_id']) || ! hash_equals((string) $draft['paypal_order_id'], $orderId)) {
            return response()->json(['message' => 'This PayPal order does not match the booking.'], 403);
        }

        if (! $this->paypal->configured()) {
            return response()->json(['message' => 'PayPal is not configured. Please contact the studio.'], 503);
        }

        $payment = $this->draftPayment($draft);
        $payment->paypal_order_id = $orderId;

        try {
            $order = $this->paypal->capture($orderId);
        } catch (ConnectionException $exception) {
            report($exception);

            return response()->json(['message' => 'Unable to connect to PayPal. Please try again.'], 502);
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json(['message' => 'Unable to verify payment with PayPal. Please try again.'], 502);
        }

        if (! $this->paypal->isPaid($order, $payment)) {
            return response()->json(CheckoutDraft::present($draft));
        }

        $booking = $this->completeDraft($draft, 'paypal', ['paypal_order_id' => $orderId]);

        return response()->json([
            'booking' => $booking->toPublicArray(),
            'payment' => $booking->payment?->toPublicArray(),
        ]);
    }

    private function draftMatchesSession(array $session, array $draft): bool
    {
        return ($session['id'] ?? null) === ($draft['stripe_session_id'] ?? null)
            && ($session['mode'] ?? null) === 'payment'
            && ($session['metadata']['booking_reference'] ?? null) === $draft['reference']
            && strtolower((string) ($session['currency'] ?? '')) === strtolower((string) $draft['currency'])
            && (int) ($session['amount_total'] ?? 0) === (int) round((float) $draft['amount'] * 100);
    }

    private function draftBooking(array $draft): Booking
    {
        $booking = new Booking($draft);
        $booking->reference = $draft['reference'];

        return $booking;
    }

    private function draftPayment(array $draft): Payment
    {
        return new Payment([
            'reference' => $draft['payment_reference'],
            'amount' => $draft['amount'],
            'currency' => $draft['currency'],
            'status' => 'pending',
            'method' => 'online',
            'paypal_order_id' => $draft['paypal_order_id'] ?? null,
            'stripe_session_id' => $draft['stripe_session_id'] ?? null,
        ]);
    }

    private function completeDraft(array $draft, string $method, array $ids = []): Booking
    {
        $reference = $draft['reference'];

        return Cache::lock('checkout-complete:'.$reference, 20)->block(10, function () use ($draft, $method, $ids, $reference) {
            $existing = Booking::query()->with(['payment', 'service'])->where('reference', $reference)->first();
            if ($existing) {
                if ($existing->payment && $existing->payment->status !== 'paid') {
                    $this->markPaymentPaid($existing->payment, $existing, $method);
                    $existing->load(['payment', 'service']);
                }

                CheckoutDraft::forget($reference);

                return $existing;
            }

            $saved = DB::transaction(function () use ($draft, $method, $ids) {
                Customer::query()->updateOrCreate(
                    ['email' => $draft['email']],
                    [
                        'name' => $draft['full_name'],
                        'phone' => $draft['phone'] ?? null,
                        'address' => $draft['address'] ?? null,
                    ]
                );

                try {
                    $booking = Booking::create([
                        'reference' => $draft['reference'],
                        'booking_kind' => $draft['booking_kind'],
                        'full_name' => $draft['full_name'],
                        'email' => $draft['email'],
                        'phone' => $draft['phone'] ?? null,
                        'address' => $draft['address'] ?? null,
                        'service_id' => $draft['service_id'] ?? null,
                        'project_type' => $draft['project_type'],
                        'event_title' => $draft['event_title'] ?? null,
                        'booking_date' => $draft['booking_date'],
                        'start_time' => $draft['start_time'],
                        'end_time' => $draft['end_time'],
                        'hours' => $draft['hours'],
                        'charge_unit' => $draft['charge_unit'] ?? 'hour',
                        'hourly_rate' => $draft['hourly_rate'],
                        'amount' => $draft['amount'],
                        'timeline' => $draft['booking_date'],
                        'budget' => (string) $draft['amount'],
                        'message' => $draft['message'] ?? '',
                        'status' => 'confirmed',
                    ]);
                } catch (UniqueConstraintViolationException) {
                    return Booking::query()->where('reference', $draft['reference'])->first();
                }

                Payment::create([
                    'booking_id' => $booking->id,
                    'reference' => $draft['payment_reference'],
                    'amount' => $draft['amount'],
                    'currency' => $draft['currency'],
                    'status' => 'paid',
                    'method' => $method,
                    'stripe_session_id' => $ids['stripe_session_id'] ?? $draft['stripe_session_id'] ?? null,
                    'paypal_order_id' => $ids['paypal_order_id'] ?? $draft['paypal_order_id'] ?? null,
                    'paid_at' => now(),
                ]);

                return $booking;
            });

            CheckoutDraft::forget($reference);
            if (! $saved) {
                $saved = Booking::query()->with(['payment', 'service'])->where('reference', $reference)->firstOrFail();
            }

            $created = $saved->wasRecentlyCreated;
            $saved->load(['payment', 'service']);
            if ($created) {
                $this->google->push($saved);

                try {
                    $this->notify($saved);
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }

            return $saved;
        });
    }
}
