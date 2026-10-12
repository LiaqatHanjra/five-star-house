<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleCalendarAccount extends Model
{
    protected $fillable = [
        'google_email',
        'calendar_id',
        'calendar_name',
        'access_token',
        'refresh_token',
        'expires_at',
        'sync_through',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'datetime',
            'sync_through' => 'date',
            'last_synced_at' => 'datetime',
        ];
    }

    public function toAdminArray(): array
    {
        return [
            'connected' => true,
            'email' => $this->google_email,
            'calendar_id' => $this->calendar_id,
            'calendar_name' => $this->calendar_name,
            'sync_through' => optional($this->sync_through)->toDateString(),
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
        ];
    }
}
