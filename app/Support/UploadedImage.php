<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class UploadedImage
{
    public static function test(?UploadedFile $file, ?string $current, ?string $fallback = null): ?string
    {
        if (! $file) {
            return $fallback !== null && $fallback !== '' ? $fallback : $current;
        }

        $disk = Storage::disk('public');
        $path = $file->storePublicly('uploads', 'public');
        if (! is_string($path) || $path === '') {
            throw new RuntimeException('The image could not be stored on the public disk.');
        }

        return $disk->url($path);
    }

    public static function replace(
        ?UploadedFile $file,
        ?string $current,
        ?string $fallback = null
        ): ?string 
    {
        if (! $file) {
            return $fallback !== null && $fallback !== ''
                ? $fallback
                : $current;
        }

        // Delete the previous local file
        if ($current && str_starts_with($current, '/storage/uploads/')) {
            Storage::disk('public')->delete(
                substr($current, strlen('/storage/'))
            );
        } elseif ($current) {
            // Delete the previous DigitalOcean Spaces file
            $spaces = Storage::disk('spaces');

            $urlPrefix = rtrim($spaces->url(''), '/') . '/';

            if (str_starts_with($current, $urlPrefix)) {
                $oldPath = substr($current, strlen($urlPrefix));

                $spaces->delete($oldPath);
            }
        }

        // Upload the new file to DigitalOcean Spaces
        $path = $file->storePublicly('uploads', 'spaces');

        if (! $path) {
            throw new \RuntimeException('File upload failed.');
        }

        return Storage::disk('spaces')->url($path);
    }
    
}
