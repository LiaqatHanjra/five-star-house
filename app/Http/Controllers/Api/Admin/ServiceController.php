<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Support\UploadedImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map->toPublicArray();

        return response()->json(['services' => $services]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['image_url'] = UploadedImage::replace($request->file('image'), null, $data['image_url'] ?? null);
        $service = Service::create($data);

        return response()->json(['service' => $service->toPublicArray()], 201);
    }

    public function update(Request $request, Service $service)
    {
        $data = $this->validated($request, $service);
        $data['image_url'] = UploadedImage::replace(
            $request->file('image'),
            $service->image_url,
            $data['image_url'] ?? $service->image_url
        );
        $service->update($data);

        return response()->json(['service' => $service->fresh()->toPublicArray()]);
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return response()->json(['message' => 'Service removed.']);
    }

    private function validated(Request $request, ?Service $service = null): array
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:8'],
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('services', 'slug')->ignore($service?->id)],
            'summary' => ['required', 'string', 'max:1000'],
            'description' => ['required', 'string', 'max:5000'],
            'eyebrow' => ['nullable', 'string', 'max:160'],
            'badge' => ['nullable', 'string', 'max:160'],
            'charge' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'currency' => ['required', 'string', 'size:3'],
            'charge_unit' => ['required', Rule::in(['hour', 'project', 'day'])],
            'minimum_hours' => ['nullable', 'integer', 'min:2', 'max:12'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'cta_label' => ['required', 'string', 'max:80'],
            'image_side' => ['required', Rule::in(['left', 'right'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ], [
            'image.uploaded' => 'This image is too large. Use a JPG, PNG, or WebP under 20 MB.',
            'image.max' => 'This image is too large. Use a JPG, PNG, or WebP under 20 MB.',
            'image.image' => 'Choose a JPG, PNG, or WebP image.',
            'image.mimes' => 'Choose a JPG, PNG, or WebP image.',
        ]);

        $slug = Str::slug($data['slug'] ?: $data['title']);
        if ($slug === '') {
            $slug = 'service';
        }

        $data['slug'] = $this->uniqueSlug($slug, $service?->id);
        $data['tags'] = $this->tags($data['tags'] ?? '');
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active', $service === null);
        $data['currency'] = strtoupper($data['currency']);
        $data['minimum_hours'] = $data['minimum_hours'] ?? 2;
        unset($data['image']);

        return $data;
    }

    private function tags(?string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }

    private function uniqueSlug(string $slug, ?int $ignoreId): string
    {
        $base = $slug;
        $i = 2;

        while (Service::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
