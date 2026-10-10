<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Support\StudioSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class BookingController extends Controller
{
    public function __construct(private StudioSchedule $schedule)
    {
    }

    public function studio()
    {
        $pairs = SiteSetting::allPairs();
        $public = [
            'owner_name', 'phone', 'address', 'open_time', 'close_time',
            'minimum_hours', 'studio_minimum_hours', 'currency',
            'studio_hourly_rate', 'event_hourly_rate', 'owner_hourly_rate',
        ];

        return response()->json([
            'settings' => array_intersect_key($pairs, array_flip($public)),
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
            $payload['marks'] = $this->schedule->monthMarks($data['month']);
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
        ]);

        $rate = $this->rateFor($data['booking_kind'], $data['service_id'] ?? null);
        if ($data['hours'] < $rate['minimum_hours']) {
            return response()->json([
                'message' => 'This booking needs at least '.$rate['minimum_hours'].' hours.',
            ], 422);
        }

        return response()->json([
            'hourly_rate' => $rate['hourly_rate'],
            'currency' => $rate['currency'],
            'minimum_hours' => $rate['minimum_hours'],
            'hours' => $data['hours'],
            'amount' => number_format($rate['hourly_rate'] * $data['hours'], 2, '.', ''),
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

        $rate = $this->rateFor($data['booking_kind'], $data['service_id'] ?? null);
        if ($data['hours'] < $rate['minimum_hours']) {
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
        $amount = round($rate['hourly_rate'] * $data['hours'], 2);

        Customer::query()->updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['full_name'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
            ]
        );

        $booking = Booking::create([
            'reference' => $this->reference(),
            'booking_kind' => $data['booking_kind'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'service_id' => $data['service_id'] ?? null,
            'project_type' => $rate['label'],
            'event_title' => $data['event_title'] ?? null,
            'booking_date' => $data['booking_date'],
            'start_time' => $start->format('H:i:s'),
            'end_time' => $end->format('H:i:s'),
            'hours' => $data['hours'],
            'hourly_rate' => $rate['hourly_rate'],
            'amount' => $amount,
            'timeline' => $data['booking_date'],
            'budget' => (string) $amount,
            'message' => $data['message'] ?? '',
            'status' => 'pending',
        ]);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'reference' => 'PAY-'.strtoupper(Str::random(8)),
            'amount' => $amount,
            'currency' => $rate['currency'],
            'status' => 'pending',
            'method' => 'online',
        ]);

        $booking->load('payment');

        return response()->json([
            'booking' => $booking->toPublicArray(),
            'payment' => $payment->toPublicArray(),
        ], 201);
    }

    public function show(string $reference)
    {
        $booking = Booking::query()->with(['payment', 'service'])->where('reference', $reference)->firstOrFail();

        return response()->json([
            'booking' => $booking->toPublicArray(),
            'payment' => $booking->payment?->toPublicArray(),
        ]);
    }

    public function pay(string $reference)
    {
        $booking = Booking::query()->with(['payment', 'service'])->where('reference', $reference)->firstOrFail();
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

        $secret = config('services.stripe.secret');
        if (! $secret) {
            return response()->json(['message' => 'Online payments are not configured. Please contact the studio.'], 503);
        }

        if ($payment->stripe_session_id) {
            try {
                $existing = Http::withOptions(['verify' => config('services.stripe.ca_bundle') ?: true])
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
            $response = Http::withOptions(['verify' => config('services.stripe.ca_bundle') ?: true])
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
            'session_id' => ['required', 'string', 'max:255'],
        ]);
        $booking = Booking::query()->with(['payment', 'service'])->where('reference', $reference)->firstOrFail();
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

    private function markPaymentPaid(Payment $payment, Booking $booking): void
    {
        if ($payment->status === 'paid') {
            return;
        }

        $payment->update([
            'status' => 'paid',
            'method' => 'stripe',
            'paid_at' => now(),
        ]);
        $booking->update(['status' => 'confirmed']);

        try {
            $this->notify($booking->fresh(['payment', 'service']));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function notify(Booking $booking): void
    {
        $owner = SiteSetting::getValue('owner_email', 'admin@fivestarhouse.com');
        Mail::to($booking->email)->send(new BookingConfirmed($booking, 'customer'));
        if ($owner) {
            Mail::to($owner)->send(new BookingConfirmed($booking, 'owner'));
        }
    }

    private function rateFor(string $kind, ?int $serviceId): array
    {
        $currency = SiteSetting::getValue('currency', 'CAD');
        $minimum = max(2, (int) SiteSetting::getValue('minimum_hours', '2'));

        if ($kind === 'service') {
            $service = Service::query()->where('is_active', true)->findOrFail($serviceId);

            return [
                'hourly_rate' => (float) $service->charge,
                'currency' => $service->currency ?: $currency,
                'minimum_hours' => max(2, (int) ($service->minimum_hours ?: $minimum)),
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
        } while (Booking::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
