<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadedImage
{
    public static function replace(?UploadedFile $file, ?string $current, ?string $fallback = null): ?string
    {
        if (! $file) {
            return $fallback !== null && $fallback !== '' ? $fallback : $current;
        }

        if ($current && str_starts_with($current, '/storage/uploads/')) {
            Storage::disk('public')->delete(substr($current, strlen('/storage/')));
        }

        return '/storage/'.$file->store('uploads', 'public');
    }
}
