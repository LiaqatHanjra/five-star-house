<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\SiteSetting;
use App\Services\GoogleCalendar;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudioSchedule
{
    public function __construct(private GoogleCalendar $google)
    {
    }

    public function window(): array
    {
        return [
            'open' => substr((string) SiteSetting::getValue('open_time', '09:00'), 0, 5),
            'close' => substr((string) SiteSetting::getValue('close_time', '19:00'), 0, 5),
            'minimum_hours' => max(1, (int) SiteSetting::getValue('minimum_hours', '1')),
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
        return array_values(array_unique(array_merge(
            $this->hoursCovered($this->blockingBookings($date), $date),
            $this->google->busyHours($date),
            CheckoutDraft::hoursOn($date),
        )));
    }

    public function daySlots(string $date, ?int $minimumHours = null): array
    {
        $bookings = $this->blockingBookings($date);
        $booked = $this->bookedStarts($date);
        $ranges = $this->google->rangesOn($date);
        $minimum = $minimumHours ?? $this->window()['minimum_hours'];

        return array_map(function (string $time) use ($date, $booked, $minimum, $bookings, $ranges) {
            $covering = $bookings
                ->filter(fn (Booking $booking) => $this->coversHour($booking, $date, $time))
                ->map(fn (Booking $booking) => $this->calendarSummary($booking))
                ->values()
                ->all();

            if ($covering === []) {
                foreach ($ranges as $range) {
                    if ($time >= $range['start'] && $time < $range['end']) {
                        $covering[] = $this->busyMark($date, $range);
                        break;
                    }
                }
            }

            if ($covering === []) {
                foreach (CheckoutDraft::marksOn($date) as $mark) {
                    if ($time >= $mark['start'] && $time < $mark['end']) {
                        $covering[] = $mark;
                        break;
                    }
                }
            }

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

        $marks = Booking::query()
            ->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('start_time')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn (Booking $booking) => $booking->booking_date->toDateString())
            ->map(fn (Collection $rows) => $rows->map(fn (Booking $booking) => $this->calendarSummary($booking))->values()->all())
            ->all();

        foreach ($this->google->rangesForMonth($month) as $date => $ranges) {
            foreach ($ranges as $range) {
                if ($this->coveredByLocal($marks[$date] ?? [], $range)) {
                    continue;
                }
                $marks[$date][] = $this->busyMark($date, $range);
            }
        }

        foreach (CheckoutDraft::marksForMonth($month) as $date => $held) {
            foreach ($held as $mark) {
                if ($this->coveredByLocal($marks[$date] ?? [], $mark)) {
                    continue;
                }
                $marks[$date][] = $mark;
            }
        }

        return $marks;
    }

    public function freeHours(string $month, ?array $marks = null): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $marks ??= $this->monthMarks($month);
        $slots = $this->slotStarts();
        $now = Carbon::now();
        $free = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $iso = $day->toDateString();
            $taken = $this->hoursFromMarks($marks[$iso] ?? []);
            $count = 0;
            foreach ($slots as $time) {
                if (in_array($time, $taken, true)) {
                    continue;
                }
                if (Carbon::parse($iso.' '.$time)->lt($now)) {
                    continue;
                }
                $count++;
            }
            $free[$iso] = $count;
        }

        return $free;
    }

    private function hoursFromMarks(array $marks): array
    {
        $hours = [];
        foreach ($marks as $mark) {
            $start = substr((string) ($mark['start'] ?? ''), 0, 5);
            $end = substr((string) ($mark['end'] ?? ''), 0, 5);
            if ($start === '' || $end === '') {
                continue;
            }
            $cursor = Carbon::createFromFormat('H:i', $start);
            $close = Carbon::createFromFormat('H:i', $end);
            while ($cursor->lt($close)) {
                $hours[] = $cursor->format('H:i');
                $cursor->addHour();
            }
        }

        return array_values(array_unique($hours));
    }

    private function coveredByLocal(array $marks, array $range): bool
    {
        foreach ($marks as $mark) {
            if (($mark['start'] ?? '') < $range['end'] && ($mark['end'] ?? '') > $range['start']) {
                return true;
            }
        }

        return false;
    }

    private function busyMark(string $date, array $range): array
    {
        return [
            'reference' => 'google-'.$date.'-'.$range['start'],
            'name' => 'Unavailable',
            'title' => 'Unavailable',
            'start' => $range['start'],
            'end' => $range['end'],
            'hours' => (int) $range['hours'],
            'status' => 'busy',
        ];
    }

    public function isAvailable(string $date, string $start, int $hours, ?int $minimumHours = null): bool
    {
        $window = $this->window();
        if ($hours < ($minimumHours ?? $window['minimum_hours'])) {
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
