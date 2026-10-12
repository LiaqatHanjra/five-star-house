<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GoogleCalendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class GoogleCalendarController extends Controller
{
    public function __construct(private GoogleCalendar $google)
    {
    }

    public function show()
    {
        $account = $this->google->account();

        return response()->json([
            'configured' => $this->google->configured(),
            'redirect_uri' => $this->google->redirectUri(),
            'connected' => (bool) $account,
            'email' => $account?->google_email,
            'calendar_name' => $account?->calendar_name,
            'sync_through' => optional($account?->sync_through)->toDateString(),
            'last_synced_at' => $account?->last_synced_at?->toIso8601String(),
        ]);
    }

    public function connect(Request $request)
    {
        if (! $this->google->configured()) {
            return response()->json([
                'message' => 'Add the Google client ID and secret in the server environment before connecting.',
            ], 422);
        }

        $state = Crypt::encryptString(json_encode([
            'user' => $request->user()->id,
            'exp' => now()->addMinutes(15)->timestamp,
        ]));

        return response()->json(['url' => $this->google->authorizationUrl($state)]);
    }

    public function callback(Request $request)
    {
        $back = '/admin/calendar';
        if ($request->query('error')) {
            return redirect($back.'?google=error&message='.urlencode('Google Calendar connection was cancelled.'));
        }

        if (! $this->validState((string) $request->query('state'))) {
            return redirect($back.'?google=error&message='.urlencode('This Google Calendar connection expired. Try again.'));
        }

        try {
            $this->google->connect((string) $request->query('code'));
        } catch (\Throwable $exception) {
            report($exception);

            return redirect($back.'?google=error&message='.urlencode($exception->getMessage()));
        }

        return redirect($back.'?google=connected');
    }

    public function sync()
    {
        try {
            $account = $this->google->syncUpcoming();
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'connected' => true,
            'email' => $account->google_email,
            'calendar_name' => $account->calendar_name,
            'sync_through' => optional($account->sync_through)->toDateString(),
            'last_synced_at' => $account->last_synced_at?->toIso8601String(),
        ]);
    }

    public function disconnect()
    {
        $this->google->disconnect();

        return response()->json(['connected' => false]);
    }

    private function validState(string $state): bool
    {
        try {
            $payload = json_decode(Crypt::decryptString($state), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return false;
        }

        return is_array($payload)
            && ($payload['exp'] ?? 0) >= time()
            && User::query()->whereKey($payload['user'] ?? null)->exists();
    }
}
