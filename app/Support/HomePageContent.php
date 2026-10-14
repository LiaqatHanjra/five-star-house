<?php

namespace App\Support;

use App\Models\SiteSetting;

class HomePageContent
{
    public const LOCALES = ['en', 'fr'];

    public static function defaults(): array
    {
        return [
            'en' => [
                'heading' => "FIVE STAR\nHOUSE",
                'description' => 'Production, direction and visual creation under one roof. We craft provocative cinematic films, commercial campaigns, and bespoke editorial imagery for global brands and independent visionaries.',
                'tags' => [
                    'Film',
                    'Photo',
                    'Creative production',
                ],
                'button' => 'View work',
                'button_secondary' => 'Start a project',
            ],
            'fr' => [
                'heading' => "FIVE STAR\nHOUSE",
                'description' => 'Production, réalisation et création visuelle sous un même toit. Nous concevons des films cinématographiques audacieux, des campagnes publicitaires et des images éditoriales sur mesure pour des marques internationales et des visionnaires indépendants.',
                'tags' => [
                    'Film',
                    'Photo',
                    'Production créative',
                ],
                'button' => 'Voir les réalisations',
                'button_secondary' => 'Lancer un projet',
            ],
        ];
    }

    public static function resolved(): array
    {
        $content = self::defaults();

        foreach (self::LOCALES as $locale) {
            $saved = json_decode((string) SiteSetting::getValue('home_page_'.$locale, ''), true);
            if (! is_array($saved)) {
                continue;
            }

            $heading = ServicesPageContent::heading((string) ($saved['heading'] ?? ''));
            if ($heading !== '') {
                $content[$locale]['heading'] = $heading;
            }

            $description = trim((string) ($saved['description'] ?? ''));
            if ($description !== '') {
                $content[$locale]['description'] = $description;
            }

            $tags = ServicesPageContent::tags($saved['tags'] ?? []);
            if ($tags !== []) {
                $content[$locale]['tags'] = $tags;
            }

            $button = trim((string) ($saved['button'] ?? ''));
            if ($button !== '') {
                $content[$locale]['button'] = $button;
            }

            $secondary = trim((string) ($saved['button_secondary'] ?? ''));
            if ($secondary !== '') {
                $content[$locale]['button_secondary'] = $secondary;
            }
        }

        return $content;
    }
}
