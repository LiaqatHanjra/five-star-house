<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPal
{
    public function configured(): bool
    {
        return filled(config('services.paypal.client_id')) && filled(config('services.paypal.secret'));
    }

    public function order(string $orderId): array
    {
        $response = $this->request()->get($this->base().'/v2/checkout/orders/'.$orderId);
        if (! $response->successful() || ! is_array($response->json())) {
            throw new RuntimeException('PayPal could not retrieve order '.$orderId.'.');
        }

        return $response->json();
    }

    public function createOrder(Payment $payment, Booking $booking): array
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $response = $this->request()->post($this->base().'/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $payment->reference,
                'custom_id' => (string) $payment->id,
                'description' => mb_substr((string) $booking->project_type, 0, 127),
                'amount' => [
                    'currency_code' => strtoupper($payment->currency),
                    'value' => $this->money($payment->amount),
                ],
            ]],
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                        'brand_name' => 'Five Star House',
                        'shipping_preference' => 'NO_SHIPPING',
                        'user_action' => 'PAY_NOW',
                        'return_url' => $baseUrl.'/booked/'.$booking->reference,
                        'cancel_url' => $baseUrl.'/pay/'.$booking->reference.'?cancelled=1',
                    ],
                ],
            ],
        ]);

        $order = $response->json();
        $url = is_array($order) ? $this->approveUrl($order) : null;
        if (! $response->successful() || empty($order['id']) || ! $url) {
            throw new RuntimeException('PayPal could not create an order.');
        }

        return ['id' => $order['id'], 'url' => $url];
    }

    public function capture(string $orderId): array
    {
        $response = $this->request()
            ->withHeaders(['PayPal-Request-Id' => 'capture-'.$orderId])
            ->withBody('{}', 'application/json')
            ->post($this->base().'/v2/checkout/orders/'.$orderId.'/capture');

        if ($response->status() === 422) {
            $issue = $response->json('details.0.issue');
            if ($issue === 'ORDER_ALREADY_CAPTURED') {
                return $this->order($orderId);
            }
        }

        if (! $response->successful() || ! is_array($response->json())) {
            throw new RuntimeException('PayPal could not capture order '.$orderId.'.');
        }

        return $response->json();
    }

    public function approveUrl(array $order): ?string
    {
        foreach ($order['links'] ?? [] as $link) {
            if (in_array($link['rel'] ?? '', ['payer-action', 'approve'], true) && ! empty($link['href'])) {
                return $link['href'];
            }
        }

        return null;
    }

    public function isPaid(array $order, Payment $payment): bool
    {
        if (($order['id'] ?? null) !== $payment->paypal_order_id) {
            return false;
        }

        $unit = $order['purchase_units'][0] ?? [];
        if ((string) ($unit['custom_id'] ?? '') !== (string) $payment->id) {
            return false;
        }

        $capture = $unit['payments']['captures'][0] ?? null;
        $amount = is_array($capture) ? ($capture['amount'] ?? []) : [];

        return ($capture['status'] ?? null) === 'COMPLETED'
            && strtoupper((string) ($amount['currency_code'] ?? '')) === strtoupper($payment->currency)
            && $this->money($amount['value'] ?? 0) === $this->money($payment->amount);
    }

    private function request(): PendingRequest
    {
        return Http::withOptions(['verify' => true])
            ->acceptJson()
            ->withToken($this->token());
    }

    private function token(): string
    {
        $response = Http::withOptions(['verify' => true])
            ->acceptJson()
            ->withBasicAuth((string) config('services.paypal.client_id'), (string) config('services.paypal.secret'))
            ->asForm()
            ->post($this->base().'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        $token = $response->json('access_token');
        if (! $response->successful() || ! is_string($token) || $token === '') {
            throw new RuntimeException('PayPal could not issue an access token.');
        }

        return $token;
    }

    private function base(): string
    {
        return config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function money(mixed $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
