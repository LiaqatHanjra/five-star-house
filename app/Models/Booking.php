<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $fillable = [
        'reference',
        'booking_kind',
        'full_name',
        'email',
        'phone',
        'address',
        'service_id',
        'project_type',
        'event_title',
        'booking_date',
        'start_time',
        'end_time',
        'hours',
        'hourly_rate',
        'amount',
        'timeline',
        'budget',
        'message',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'timeline' => 'date',
            'booking_date' => 'date',
            'hours' => 'integer',
            'hourly_rate' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'booking_kind' => $this->booking_kind,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'service_id' => $this->service_id,
            'project_type' => $this->project_type,
            'event_title' => $this->event_title,
            'booking_date' => optional($this->booking_date)->toDateString(),
            'start_time' => $this->start_time ? substr((string) $this->start_time, 0, 5) : null,
            'end_time' => $this->end_time ? substr((string) $this->end_time, 0, 5) : null,
            'hours' => $this->hours,
            'hourly_rate' => $this->hourly_rate,
            'amount' => $this->amount,
            'message' => $this->message,
            'status' => $this->status,
            'payment_status' => $this->relationLoaded('payment') ? $this->payment?->status : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
