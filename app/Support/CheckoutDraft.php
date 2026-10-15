<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CheckoutDraft
{
    private const INDEX = 'checkout-drafts';

    public static function put(array $draft): void
    {
        $reference = (string) $draft['reference'];
        Cache::put(self::key($reference), $draft, now()->addDay());

        $index = self::index();
        $index[$reference] = true;
        Cache::put(self::INDEX, $index, now()->addDays(2));
    }

    public static function get(string $reference): ?array
    {
        $draft = Cache::get(self::key($reference));

        return is_array($draft) ? $draft : null;
    }

    public static function merge(string $reference, array $changes): ?array
    {
        $draft = self::get($reference);
        if (! $draft) {
            return null;
        }

        $draft = array_merge($draft, $changes);
        self::put($draft);

        return $draft;
    }

    public static function forget(string $reference): void
    {
        Cache::forget(self::key($reference));
        $index = self::index();
        unset($index[$reference]);
        Cache::put(self::INDEX, $index, now()->addDays(2));
    }

    public static function exists(string $reference): bool
    {
        return self::get($reference) !== null;
    }

    public static function hoursOn(string $date): array
    {
        $hours = [];
        foreach (self::marksOn($date) as $mark) {
            $cursor = Carbon::createFromFormat('H:i', $mark['start']);
            $end = Carbon::createFromFormat('H:i', $mark['end']);
            while ($cursor->lt($end)) {
                $hours[] = $cursor->format('H:i');
                $cursor->addHour();
            }
        }

        return array_values(array_unique($hours));
    }

    public static function marksOn(string $date): array
    {
        $marks = [];
        foreach (self::all() as $draft) {
            if (($draft['booking_date'] ?? '') !== $date || empty($draft['start_time']) || empty($draft['end_time'])) {
                continue;
            }
            $marks[] = self::mark($draft);
        }

        return $marks;
    }

    public static function marksForMonth(string $month): array
    {
        $grouped = [];
        foreach (self::all() as $draft) {
            $date = (string) ($draft['booking_date'] ?? '');
            if (! str_starts_with($date, $month) || empty($draft['start_time'])) {
                continue;
            }
            $grouped[$date][] = self::mark($draft);
        }

        return $grouped;
    }

    public static function present(array $draft): array
    {
        return [
            'booking' => [
                'reference' => $draft['reference'],
                'booking_kind' => $draft['booking_kind'],
                'full_name' => $draft['full_name'],
                'email' => $draft['email'],
                'phone' => $draft['phone'] ?? null,
                'address' => $draft['address'] ?? null,
                'service_id' => $draft['service_id'] ?? null,
                'service_slug' => $draft['service_slug'] ?? null,
                'project_type' => $draft['project_type'],
                'event_title' => $draft['event_title'] ?? null,
                'booking_date' => $draft['booking_date'],
                'start_time' => substr((string) $draft['start_time'], 0, 5),
                'end_time' => substr((string) $draft['end_time'], 0, 5),
                'hours' => (int) $draft['hours'],
                'hourly_rate' => $draft['hourly_rate'],
                'amount' => $draft['amount'],
                'message' => $draft['message'] ?? '',
                'status' => 'pending',
                'payment_status' => 'pending',
            ],
            'payment' => [
                'reference' => $draft['payment_reference'],
                'amount' => $draft['amount'],
                'currency' => $draft['currency'],
                'status' => 'pending',
                'method' => 'online',
                'paid_at' => null,
            ],
        ];
    }

    private static function mark(array $draft): array
    {
        return [
            'reference' => $draft['reference'],
            'name' => 'Unavailable',
            'title' => 'Unavailable',
            'start' => substr((string) $draft['start_time'], 0, 5),
            'end' => substr((string) $draft['end_time'], 0, 5),
            'hours' => (int) ($draft['hours'] ?? 0),
            'status' => 'busy',
        ];
    }

    private static function all(): array
    {
        $index = self::index();
        $drafts = [];
        $changed = false;

        foreach (array_keys($index) as $reference) {
            $draft = self::get((string) $reference);
            if (! $draft) {
                unset($index[$reference]);
                $changed = true;
                continue;
            }
            $drafts[] = $draft;
        }

        if ($changed) {
            Cache::put(self::INDEX, $index, now()->addDays(2));
        }

        return $drafts;
    }

    private static function index(): array
    {
        $index = Cache::get(self::INDEX, []);

        return is_array($index) ? $index : [];
    }

    private static function key(string $reference): string
    {
        return 'checkout-draft:'.$reference;
    }
}
