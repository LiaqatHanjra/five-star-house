<?php

namespace App\Support;

use App\Models\SiteSetting;

class ServicesPageContent
{
    public const LOCALES = ['en', 'fr'];

    public static function defaults(): array
    {
        return [
            'en' => [
                'heading' => "Creative\nProduction\nSolutions",
                'description' => 'From concept to final cut, we handle every step of the process. Whether you need a full production, clean visuals, or an agile creative partner, we bring the right team, industry-grade tools, and seasoned experience to realize your vision.',
                'tags' => [
                    'Full-spectrum production',
                    'ARRI & RED ecosystem',
                    '1600 sq ft cyclorama',
                ],
            ],
            'fr' => [
                'heading' => "Solutions de\nproduction\ncréative",
                'description' => 'Du concept au montage final, nous prenons en charge chaque étape. Production complète, images épurées ou partenaire créatif agile : nous réunissons la bonne équipe, des outils de calibre professionnel et une expérience solide pour réaliser votre vision.',
                'tags' => [
                    'Production complète',
                    'Écosystème ARRI et RED',
                    'Cyclorama de 1 600 pi²',
                ],
            ],
        ];
    }

    public static function resolved(): array
    {
        $content = self::defaults();

        foreach (self::LOCALES as $locale) {
            $saved = json_decode((string) SiteSetting::getValue('services_page_'.$locale, ''), true);
            if (! is_array($saved)) {
                continue;
            }

            $heading = self::heading((string) ($saved['heading'] ?? ''));
            if ($heading !== '') {
                $content[$locale]['heading'] = $heading;
            }

            $description = trim((string) ($saved['description'] ?? ''));
            if ($description !== '') {
                $content[$locale]['description'] = $description;
            }

            $tags = self::tags($saved['tags'] ?? []);
            if ($tags !== []) {
                $content[$locale]['tags'] = $tags;
            }
        }

        return $content;
    }

    public static function heading(string $value): string
    {
        $lines = preg_split('/\R/u', $value) ?: [];
        $lines = array_values(array_filter(array_map(static fn ($line) => trim((string) $line), $lines), static fn ($line) => $line !== ''));

        return implode("\n", $lines);
    }

    public static function tags(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $tags = [];
        foreach ($value as $tag) {
            $tag = trim((string) $tag);
            if ($tag !== '') {
                $tags[] = $tag;
            }
        }

        return array_values($tags);
    }
}
