<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\SiteImage;

class PublicContentController extends Controller
{
    public function home()
    {
        $images = SiteImage::query()
            ->where('page', 'home')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'hero' => $images->firstWhere('slot', 'hero')?->toPublicArray(),
            'works' => $images->where('slot', 'gallery')->values()->map->toPublicArray(),
            'services' => $this->activeServices(),
        ]);
    }

    public function services()
    {
        $banner = SiteImage::query()
            ->where('page', 'services')
            ->where('slot', 'hero')
            ->where('is_active', true)
            ->first();

        return response()->json([
            'banner' => $banner?->toPublicArray(),
            'services' => $this->activeServices(),
        ]);
    }

    public function service(string $slug)
    {
        $service = Service::query()->where('is_active', true)->where('slug', $slug)->firstOrFail();

        return response()->json(['service' => $service->toPublicArray()]);
    }

    public function booking()
    {
        $images = SiteImage::query()
            ->where('page', 'booking')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('slot');

        return response()->json([
            'sidebar' => $images->get('sidebar')?->toPublicArray(),
            'banner' => $images->get('banner')?->toPublicArray(),
            'services' => Service::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'title', 'slug', 'charge', 'currency', 'charge_unit'])
                ->map(fn (Service $service) => [
                    'id' => $service->id,
                    'title' => $service->title,
                    'slug' => $service->slug,
                    'charge' => $service->charge,
                    'currency' => $service->currency,
                    'charge_unit' => $service->charge_unit,
                ]),
        ]);
    }

    private function activeServices()
    {
        return Service::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map->toPublicArray()
            ->values();
    }
}
