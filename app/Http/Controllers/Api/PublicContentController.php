<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\SiteImage;
use App\Support\BookingPageContent;
use App\Support\HomePageContent;
use App\Support\ServicesPageContent;

class PublicContentController extends Controller
{
    public function homeBanner()
    {
        $hero = SiteImage::query()
            ->where('page', 'home')
            ->where('slot', 'hero')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();

        return response()->json([
            'hero' => $hero?->toPublicArray(),
            'page' => HomePageContent::resolved(),
        ]);
    }

    public function home()
    {
        $works = SiteImage::query()
            ->where('page', 'home')
            ->where('slot', 'gallery')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'works' => $works->map->toPublicArray()->values(),
            'services' => $this->activeServices(),
        ]);
    }

    public function servicesBanner()
    {
        $banner = SiteImage::query()
            ->where('page', 'services')
            ->where('slot', 'hero')
            ->where('is_active', true)
            ->first();

        return response()->json([
            'banner' => $banner?->toPublicArray(),
            'page' => ServicesPageContent::resolved(),
        ]);
    }

    public function services()
    {
        return response()->json([
            'services' => $this->activeServices(),
        ]);
    }

    public function service(string $slug)
    {
        $service = Service::query()->where('is_active', true)->where('slug', $slug)->firstOrFail();

        return response()->json(['service' => $service->toPublicArray()]);
    }

    public function bookingBanner()
    {
        $images = SiteImage::query()
            ->where('page', 'booking')
            ->where('is_active', true)
            ->whereIn('slot', ['sidebar', 'banner'])
            ->orderBy('sort_order')
            ->get()
            ->keyBy('slot');

        $content = BookingPageContent::resolved();
        $page = [];
        foreach (BookingPageContent::LOCALES as $locale) {
            $page[$locale] = [
                'kicker' => $content[$locale]['kicker'] ?? '',
                'title' => $content[$locale]['title'] ?? '',
                'lead' => $content[$locale]['lead'] ?? '',
            ];
        }

        return response()->json([
            'page' => $page,
            'sidebar' => $images->get('sidebar')?->toPublicArray(),
            'banner' => $images->get('banner')?->toPublicArray(),
        ]);
    }

    public function booking()
    {
        return response()->json([
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
