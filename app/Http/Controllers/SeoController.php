<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\SiteSetting;

class SeoController extends Controller
{
    public function show()
    {
        return response()->json([
            'title_en' => SiteSetting::getValue('seo_title_en', ''),
            'title_fr' => SiteSetting::getValue('seo_title_fr', ''),
            'description_en' => SiteSetting::getValue('seo_description_en', ''),
            'description_fr' => SiteSetting::getValue('seo_description_fr', ''),
        ]);
    }

    public function robots()
    {
        $sitemap = rtrim((string) config('app.url'), '/').'/sitemap.xml';
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /pay/',
            'Disallow: /booked/',
            'Disallow: /api/',
            'Disallow: /google/',
            '',
            'Sitemap: '.$sitemap,
            '',
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap()
    {
        $base = rtrim((string) config('app.url'), '/');
        $urls = [
            $this->url($base.'/', '1.0'),
            $this->url($base.'/services', '0.8'),
            $this->url($base.'/book', '0.8'),
            $this->url($base.'/book/studio-time', '0.7'),
            $this->url($base.'/book/studio-event', '0.7'),
            $this->url($base.'/book/owner-event', '0.7'),
        ];

        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['slug', 'updated_at']);

        foreach ($services as $service) {
            $updated = optional($service->updated_at)->toDateString();
            $urls[] = $this->url($base.'/services/'.$service->slug, '0.7', $updated);
            $urls[] = $this->url($base.'/book/service/'.$service->slug, '0.6', $updated);
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.$this->escape($url['loc'])."</loc>\n";
            if (! empty($url['lastmod'])) {
                $xml .= '    <lastmod>'.$this->escape($url['lastmod'])."</lastmod>\n";
            }
            $xml .= '    <priority>'.$this->escape($url['priority'])."</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= "</urlset>\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function url(string $loc, string $priority, ?string $lastmod = null): array
    {
        return ['loc' => $loc, 'priority' => $priority, 'lastmod' => $lastmod];
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
