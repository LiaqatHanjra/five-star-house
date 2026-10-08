<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteImage;
use App\Support\UploadedImage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiteImageController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'page' => ['required', Rule::in(['home', 'services', 'booking'])],
        ]);

        $images = SiteImage::query()
            ->where('page', $data['page'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map->toPublicArray();

        return response()->json(['images' => $images]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['image_url'] = UploadedImage::replace($request->file('image'), null, $data['image_url'] ?? null);

        if (! $data['image_url']) {
            return response()->json([
                'message' => 'An image file or image URL is required.',
                'errors' => ['image' => ['An image file or image URL is required.']],
            ], 422);
        }

        $image = SiteImage::create($data);

        return response()->json(['image' => $image->toPublicArray()], 201);
    }

    public function update(Request $request, SiteImage $siteImage)
    {
        $data = $this->validated($request, false);
        $data['image_url'] = UploadedImage::replace(
            $request->file('image'),
            $siteImage->image_url,
            $data['image_url'] ?? $siteImage->image_url
        );

        $siteImage->update($data);

        return response()->json(['image' => $siteImage->fresh()->toPublicArray()]);
    }

    public function destroy(SiteImage $siteImage)
    {
        if ($siteImage->slot !== 'gallery') {
            return response()->json([
                'message' => 'This image is part of the page layout. Replace it instead of deleting it.',
            ], 422);
        }

        $siteImage->delete();

        return response()->json(['message' => 'Image removed.']);
    }

    private function validated(Request $request, bool $creating = true): array
    {
        $data = $request->validate([
            'page' => ['required', Rule::in(['home', 'services', 'booking'])],
            'slot' => ['required', Rule::in(['hero', 'gallery', 'sidebar', 'banner'])],
            'title' => ['nullable', 'string', 'max:160'],
            'category' => ['nullable', 'string', 'max:160'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'alt' => ['nullable', 'string', 'max:180'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'col_class' => ['nullable', 'string', 'max:80'],
            'aspect' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ], [
            'image.uploaded' => 'This image is too large. Use a JPG, PNG, or WebP under 20 MB.',
            'image.max' => 'This image is too large. Use a JPG, PNG, or WebP under 20 MB.',
            'image.image' => 'Choose a JPG, PNG, or WebP image.',
            'image.mimes' => 'Choose a JPG, PNG, or WebP image.',
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active', $creating);
        unset($data['image']);

        return $data;
    }
}
