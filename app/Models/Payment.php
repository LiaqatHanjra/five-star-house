<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'booking_id',
        'reference',
        'stripe_session_id',
        'amount',
        'currency',
        'status',
        'method',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'reference' => $this->reference,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'method' => $this->method,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'booking' => $this->relationLoaded('booking') ? $this->booking?->toPublicArray() : null,
        ];
    }
}
