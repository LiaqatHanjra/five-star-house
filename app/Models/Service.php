<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'number',
        'title',
        'slug',
        'summary',
        'description',
        'eyebrow',
        'badge',
        'charge',
        'currency',
        'charge_unit',
        'minimum_hours',
        'image_url',
        'tags',
        'cta_label',
        'image_side',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'charge' => 'decimal:2',
            'minimum_hours' => 'integer',
            'tags' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'description' => $this->description,
            'eyebrow' => $this->eyebrow,
            'badge' => $this->badge,
            'charge' => $this->charge,
            'currency' => $this->currency,
            'charge_unit' => $this->charge_unit,
            'minimum_hours' => $this->minimum_hours ?: 2,
            'image_url' => $this->image_url,
            'tags' => $this->tags ?? [],
            'cta_label' => $this->cta_label,
            'image_side' => $this->image_side,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
