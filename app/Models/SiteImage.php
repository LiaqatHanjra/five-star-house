<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteImage extends Model
{
    protected $fillable = [
        'page',
        'slot',
        'title',
        'category',
        'caption',
        'alt',
        'image_url',
        'col_class',
        'aspect',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'page' => $this->page,
            'slot' => $this->slot,
            'title' => $this->title,
            'category' => $this->category,
            'caption' => $this->caption,
            'alt' => $this->alt,
            'image_url' => $this->image_url,
            'col_class' => $this->col_class,
            'aspect' => $this->aspect,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
