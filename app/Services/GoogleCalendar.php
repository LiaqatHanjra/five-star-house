<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\GoogleCalendarAccount;
use App\Models\SiteSetting;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleCalendar
{
    public const CALENDAR_NAME = 'FIVE STAR CALENDAR';

    public function configured(): bool
    {
        return (bool) config('services.google.client_id') && (bool) config('services.google.client_secret');
    }

    public function account(): ?GoogleCalendarAccount
    {
        return GoogleCalendarAccount::query()->first();
    }

    public function redirectUri(): string
    {
        return (string) config('services.google.redirect');
    }

    public function authorizationUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => implode(' ', [
                'openid',
                'email',
                'https://www.googleapis.com/auth/calendar',
            ]),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.$query;
    }

    public function connect(string $code): GoogleCalendarAccount
    {
        $token = $this->tokenRequest([
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri(),
        ]);

        if (! empty($token['scope']) && ! str_contains($token['scope'], 'auth/calendar')) {
            throw new RuntimeException('Google did not allow calendar access. On the Google screen, click Continue, then Allow. Also enable the Google Calendar API and add the Calendar scope on the OAuth consent screen.');
        }

        $account = $this->account() ?? new GoogleCalendarAccount;
        $account->fill([
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'] ?? $account->refresh_token,
            'expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 3600)),
        ]);
        $account->google_email = $this->email($account);
        $calendar = $this->studioCalendar($account);
        $account->calendar_id = $calendar['id'];
        $account->calendar_name = $calendar['summary'] ?? self::CALENDAR_NAME;
        $account->save();

        $this->syncUpcoming($account);

        return $account->fresh();
    }

    public function disconnect(): void
    {
        $account = $this->account();
        if ($account) {
            $this->forgetBusy($account);
            $account->delete();
        }
    }

    public function syncUpcoming(?GoogleCalendarAccount $account = null): GoogleCalendarAccount
    {
        $account = $account ?? $this->account();
        if (! $account) {
            throw new RuntimeException('Google Calendar is not connected.');
        }

        $bookings = Booking::query()
            ->whereNotNull('booking_date')
            ->where('status', '!=', 'cancelled')
            ->whereDate('booking_date', '>=', now()->toDateString())
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();

        foreach ($bookings as $booking) {
            $this->push($booking, $account);
        }

        $latest = $bookings->max(fn (Booking $booking) => $booking->booking_date?->toDateString());
        $horizon = now()->addMonths(6)->toDateString();
        $account->sync_through = $latest && $latest > $horizon ? $latest : $horizon;
        $account->last_synced_at = now();
        $account->save();
        $this->forgetBusy($account);

        return $account->fresh();
    }

    public function push(Booking $booking, ?GoogleCalendarAccount $account = null): void
    {
        $account = $account ?? $this->account();
        if (! $account || ! $booking->booking_date || ! $booking->start_time) {
            return;
        }

        try {
            if ($booking->status === 'cancelled') {
                $this->deleteEvent($booking, $account);
                $this->forgetDate($account, $booking);

                return;
            }

            $payload = $this->eventPayload($booking);
            if ($booking->google_event_id) {
                $response = $this->authorized($account)->put($this->withUpdates($this->eventUrl($account, $booking->google_event_id)), $payload);
                if ($response->status() !== 404) {
                    $response->throw();
                    $this->forgetDate($account, $booking);

                    return;
                }
            }

            $created = $this->authorized($account)
                ->post($this->withUpdates($this->eventsUrl($account)), $payload)
                ->throw()
                ->json();
            $booking->update(['google_event_id' => $created['id'] ?? null]);
            $this->forgetDate($account, $booking);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function busyHours(string $date): array
    {
        $hours = [];
        foreach ($this->rangesOn($date) as $range) {
            $cursor = Carbon::parse($date.' '.$range['start']);
            $end = Carbon::parse($date.' '.$range['end']);
            while ($cursor->lt($end)) {
                $hours[] = $cursor->format('H:i');
                $cursor->addHour();
            }
        }

        return array_values(array_unique($hours));
    }

    public function rangesOn(string $date): array
    {
        $month = substr($date, 0, 7);

        return $this->rangesForMonth($month)[$date] ?? [];
    }

    public function rangesForMonth(string $month): array
    {
        $account = $this->account();
        if (! $account || ! $account->calendar_id) {
            return [];
        }

        $key = 'google-busy-'.$account->id.'-'.$month;
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $ranges = $this->fetchRanges($account, $month);
        } catch (\Throwable $exception) {
            report($exception);

            return [];
        }

        Cache::put($key, $ranges, now()->addMinutes(5));

        return $ranges;
    }

    private function fetchRanges(GoogleCalendarAccount $account, string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();
        $zone = config('app.timezone', 'America/Toronto');
        $response = $this->authorized($account)->post('https://www.googleapis.com/calendar/v3/freeBusy', [
            'timeMin' => $start->toIso8601String(),
            'timeMax' => $end->toIso8601String(),
            'timeZone' => $zone,
            'items' => [['id' => $account->calendar_id]],
        ])->throw()->json();

        $busy = $response['calendars'][$account->calendar_id]['busy'] ?? [];
        $hours = [];
        foreach ($busy as $block) {
            $this->collectHours($hours, Carbon::parse($block['start'])->timezone($zone), Carbon::parse($block['end'])->timezone($zone));
        }

        $ranges = [];
        foreach ($hours as $date => $list) {
            $ranges[$date] = $this->compressHours($date, $list);
        }

        return $ranges;
    }

    private function collectHours(array &$hours, Carbon $start, Carbon $end): void
    {
        $window = $this->studioWindow();
        $cursor = $start->copy();
        while ($cursor->lt($end)) {
            $sliceEnd = $end->lt($cursor->copy()->startOfDay()->addDay()) ? $end->copy() : $cursor->copy()->startOfDay()->addDay();
            $open = Carbon::parse($cursor->toDateString().' '.$window['open']);
            $close = Carbon::parse($cursor->toDateString().' '.$window['close']);
            $slot = $open->copy();
            while ($slot->lt($close)) {
                $slotEnd = $slot->copy()->addHour();
                if ($slot->lt($sliceEnd) && $slotEnd->gt($cursor) && $slotEnd->gt($start)) {
                    $hours[$cursor->toDateString()][] = $slot->format('H:i');
                }
                $slot->addHour();
            }
            $cursor = $sliceEnd;
        }
    }

    private function compressHours(string $date, array $list): array
    {
        $list = array_values(array_unique($list));
        sort($list);
        $ranges = [];
        $rangeStart = null;
        $previous = null;
        foreach ($list as $time) {
            $hour = Carbon::parse($date.' '.$time);
            if ($rangeStart === null) {
                $rangeStart = $hour;
                $previous = $hour;
                continue;
            }
            if ($previous->copy()->addHour()->ne($hour)) {
                $ranges[] = $this->range($rangeStart, $previous->copy()->addHour());
                $rangeStart = $hour;
            }
            $previous = $hour;
        }
        if ($rangeStart && $previous) {
            $ranges[] = $this->range($rangeStart, $previous->copy()->addHour());
        }

        return $ranges;
    }

    private function range(Carbon $start, Carbon $end): array
    {
        return [
            'start' => $start->format('H:i'),
            'end' => $end->format('H:i'),
            'hours' => (int) round($start->diffInHours($end)),
        ];
    }

    private function studioWindow(): array
    {
        return [
            'open' => substr((string) SiteSetting::getValue('open_time', '09:00'), 0, 5),
            'close' => substr((string) SiteSetting::getValue('close_time', '19:00'), 0, 5),
        ];
    }

    private function deleteEvent(Booking $booking, GoogleCalendarAccount $account): void
    {
        if (! $booking->google_event_id) {
            return;
        }
        $response = $this->authorized($account)->delete($this->withUpdates($this->eventUrl($account, $booking->google_event_id)));
        if (! $response->successful() && $response->status() !== 404 && $response->status() !== 410) {
            $response->throw();
        }
        $booking->update(['google_event_id' => null]);
    }

    private function eventPayload(Booking $booking): array
    {
        $zone = config('app.timezone', 'America/Toronto');
        $start = Carbon::parse($booking->booking_date->toDateString().' '.$booking->start_time, $zone);
        $end = Carbon::parse($booking->booking_date->toDateString().' '.$booking->end_time, $zone);
        $title = $booking->event_title ?: $booking->project_type;

        return [
            'summary' => 'FIVE STAR HOUSE · '.$title,
            'description' => trim(implode("\n", array_filter([
                $booking->reference,
                $booking->full_name.' · '.$booking->email,
                $booking->phone,
                'Status: '.$booking->status,
                $booking->message,
            ]))),
            'start' => ['dateTime' => $start->format('Y-m-d\TH:i:s'), 'timeZone' => $zone],
            'end' => ['dateTime' => $end->format('Y-m-d\TH:i:s'), 'timeZone' => $zone],
            'attendees' => [[
                'email' => $booking->email,
                'displayName' => $booking->full_name,
            ]],
            'guestsCanModify' => false,
            'guestsCanInviteOthers' => false,
            'guestsCanSeeOtherGuests' => false,
        ];
    }

    private function studioCalendar(GoogleCalendarAccount $account): array
    {
        $list = $this->authorized($account)
            ->get('https://www.googleapis.com/calendar/v3/users/me/calendarList')
            ->throw()
            ->json('items', []);

        foreach ($list as $calendar) {
            if (($calendar['summary'] ?? '') === self::CALENDAR_NAME) {
                return $calendar;
            }
        }

        return $this->authorized($account)
            ->post('https://www.googleapis.com/calendar/v3/calendars', [
                'summary' => self::CALENDAR_NAME,
                'timeZone' => config('app.timezone', 'America/Toronto'),
            ])
            ->throw()
            ->json();
    }

    private function email(GoogleCalendarAccount $account): ?string
    {
        return $this->authorized($account)
            ->get('https://www.googleapis.com/oauth2/v2/userinfo')
            ->throw()
            ->json('email');
    }

    private function authorized(GoogleCalendarAccount $account): PendingRequest
    {
        $this->refreshIfNeeded($account);

        return $this->http()->withToken($account->access_token);
    }

    private function refreshIfNeeded(GoogleCalendarAccount $account): void
    {
        if ($account->expires_at && $account->expires_at->gt(now()->addMinute())) {
            return;
        }
        if (! $account->refresh_token) {
            throw new RuntimeException('Google Calendar needs to be connected again.');
        }

        $token = $this->tokenRequest([
            'grant_type' => 'refresh_token',
            'refresh_token' => $account->refresh_token,
        ]);
        $account->access_token = $token['access_token'];
        $account->expires_at = now()->addSeconds((int) ($token['expires_in'] ?? 3600));
        if (! empty($token['refresh_token'])) {
            $account->refresh_token = $token['refresh_token'];
        }
        $account->save();
    }

    private function tokenRequest(array $payload): array
    {
        $response = $this->http()->asForm()->post('https://oauth2.googleapis.com/token', array_merge($payload, [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
        ]));
        if (! $response->successful()) {
            throw new RuntimeException($response->json('error_description') ?: 'Google could not authorize this connection.');
        }

        return $response->json();
    }

    private function forgetDate(GoogleCalendarAccount $account, Booking $booking): void
    {
        if ($booking->booking_date) {
            Cache::forget('google-busy-'.$account->id.'-'.$booking->booking_date->format('Y-m'));
        }
    }

    private function forgetBusy(GoogleCalendarAccount $account): void
    {
        $cursor = now()->startOfMonth()->subMonth();
        for ($i = 0; $i < 8; $i++) {
            Cache::forget('google-busy-'.$account->id.'-'.$cursor->format('Y-m'));
            $cursor->addMonth();
        }
    }

    private function withUpdates(string $url): string
    {
        return $url.(str_contains($url, '?') ? '&' : '?').'sendUpdates=all';
    }

    private function eventsUrl(GoogleCalendarAccount $account): string
    {
        return 'https://www.googleapis.com/calendar/v3/calendars/'.rawurlencode($account->calendar_id).'/events';
    }

    private function eventUrl(GoogleCalendarAccount $account, string $eventId): string
    {
        return $this->eventsUrl($account).'/'.rawurlencode($eventId);
    }

    private function http(): PendingRequest
    {
        return Http::withOptions([
            'verify' => config('services.stripe.ca_bundle') ?: true,
        ])->acceptJson()->timeout(20);
    }
}
