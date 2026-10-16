<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class UploadedImage
{
    public static function replace(?UploadedFile $file, ?string $current, ?string $fallback = null): ?string
    {
        if (! $file) {
            return $fallback !== null && $fallback !== '' ? $fallback : $current;
        }

        $disk = Storage::disk('public');
        $path = $file->storePublicly('uploads', 'public');
        if (! is_string($path) || $path === '') {
            throw new RuntimeException('The image could not be stored on the public disk.');
        }

        // if ($current) {
        //     $urlPrefix = rtrim($disk->url(''), '/').'/';
        //     if (str_starts_with($current, $urlPrefix)) {
        //         $disk->delete(substr($current, strlen($urlPrefix)));
        //     } elseif (str_starts_with($current, '/storage/uploads/')) {
        //         $disk->delete(substr($current, strlen('/storage/')));
        //     }
        // }

        return $disk->url($path);
    }

    public static function test(?UploadedFile $file, ?string $current, ?string $fallback = null): ?string{
            if (! $file) {
                return $fallback !== null && $fallback !== '' ? $fallback : $current;
            }
    
            if ($current && str_starts_with($current, '/storage/uploads/')) {
                Storage::disk('public')->delete(substr($current, strlen('/storage/')));
            } elseif ($current) {
                $spaces = Storage::disk('spaces');
                $urlPrefix = rtrim($spaces->url(''), '/').'/';
                if (str_starts_with($current, $urlPrefix)) {
                    $spaces->delete(substr($current, strlen($urlPrefix)));
                }
            $disk = Storage::disk('public');
            $path = $file->storePublicly('uploads', 'public');
            if (! is_string($path) || $path === '') {
                throw new RuntimeException('The image could not be stored on the public disk.');
            }
    
            $path = $file->storePublicly('uploads', 'spaces');
        
    
            return Storage::disk('spaces')->url($path);
        }
    }
    
}
