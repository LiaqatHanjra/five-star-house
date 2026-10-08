<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\SiteSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudioSchedule
{
    public function window(): array
    {
        return [
            'open' => substr((string) SiteSetting::getValue('open_time', '09:00'), 0, 5),
            'close' => substr((string) SiteSetting::getValue('close_time', '19:00'), 0, 5),
            'minimum_hours' => max(2, (int) SiteSetting::getValue('minimum_hours', '2')),
            'currency' => SiteSetting::getValue('currency', 'CAD'),
        ];
    }

    public function slotStarts(): array
    {
        $window = $this->window();
        $cursor = Carbon::createFromFormat('H:i', $window['open']);
        $close = Carbon::createFromFormat('H:i', $window['close']);
        $slots = [];

        while ($cursor->lt($close)) {
            $slots[] = $cursor->format('H:i');
            $cursor->addHour();
        }

        return $slots;
    }

    public function blockingBookings(string $date): Collection
    {
        return Booking::query()
            ->whereDate('booking_date', $date)
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('start_time')
            ->get();
    }

    public function bookedStarts(string $date): array
    {
        return $this->hoursCovered($this->blockingBookings($date), $date);
    }

    public function daySlots(string $date): array
    {
        $bookings = $this->blockingBookings($date);
        $booked = $this->hoursCovered($bookings, $date);
        $minimum = $this->window()['minimum_hours'];

        return array_map(function (string $time) use ($date, $booked, $minimum, $bookings) {
            $covering = $bookings
                ->filter(fn (Booking $booking) => $this->coversHour($booking, $date, $time))
                ->map(fn (Booking $booking) => $this->calendarSummary($booking))
                ->values()
                ->all();

            return [
                'time' => $time,
                'booked' => $covering !== [],
                'can_start' => $this->consecutiveFree($date, $time, $booked) >= $minimum,
                'bookings' => $covering,
            ];
        }, $this->slotStarts());
    }

    public function monthMarks(string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return Booking::query()
            ->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('start_time')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn (Booking $booking) => $booking->booking_date->toDateString())
            ->map(fn (Collection $rows) => $rows->map(fn (Booking $booking) => $this->calendarSummary($booking))->values()->all())
            ->all();
    }

    public function isAvailable(string $date, string $start, int $hours): bool
    {
        $window = $this->window();
        if ($hours < $window['minimum_hours']) {
            return false;
        }

        $startAt = Carbon::parse($date.' '.$start);
        $endAt = $startAt->copy()->addHours($hours);
        $open = Carbon::parse($date.' '.$window['open']);
        $close = Carbon::parse($date.' '.$window['close']);

        if ($startAt->lt($open) || $endAt->gt($close)) {
            return false;
        }

        if ($startAt->lt(Carbon::now())) {
            return false;
        }

        $booked = $this->bookedStarts($date);
        $cursor = $startAt->copy();
        while ($cursor->lt($endAt)) {
            if (in_array($cursor->format('H:i'), $booked, true)) {
                return false;
            }
            $cursor->addHour();
        }

        return true;
    }

    private function hoursCovered(Collection $bookings, string $date): array
    {
        $booked = [];
        foreach ($bookings as $booking) {
            $cursor = Carbon::parse($date.' '.$booking->start_time);
            $end = Carbon::parse($date.' '.$booking->end_time);
            while ($cursor->lt($end)) {
                $booked[] = $cursor->format('H:i');
                $cursor->addHour();
            }
        }

        return array_values(array_unique($booked));
    }

    private function coversHour(Booking $booking, string $date, string $time): bool
    {
        $hour = Carbon::parse($date.' '.$time);
        $start = Carbon::parse($date.' '.$booking->start_time);
        $end = Carbon::parse($date.' '.$booking->end_time);

        return $hour->gte($start) && $hour->lt($end);
    }

    private function calendarSummary(Booking $booking): array
    {
        return [
            'reference' => $booking->reference,
            'name' => $booking->full_name,
            'title' => $booking->event_title ?: $booking->project_type,
            'start' => substr((string) $booking->start_time, 0, 5),
            'end' => substr((string) $booking->end_time, 0, 5),
            'hours' => $booking->hours,
            'status' => $booking->status,
        ];
    }

    private function consecutiveFree(string $date, string $start, array $booked): int
    {
        $window = $this->window();
        $cursor = Carbon::parse($date.' '.$start);
        $close = Carbon::parse($date.' '.$window['close']);
        $count = 0;

        while ($cursor->lt($close) && ! in_array($cursor->format('H:i'), $booked, true)) {
            $count++;
            $cursor->addHour();
        }

        return $count;
    }
}
