<?php

namespace App\Support;

use App\Models\SiteSetting;

class BookingPageContent
{
    public const LOCALES = ['en', 'fr'];

    public const FIELDS = [
        'kicker',
        'title',
        'lead',
        'hint',
        'studio_kicker',
        'studio_title',
        'studio_copy',
        'event_kicker',
        'event_title',
        'event_copy',
        'owner_kicker',
        'owner_title',
        'owner_copy',
    ];

    public static function limit(string $field): int
    {
        return match ($field) {
            'kicker', 'studio_kicker', 'event_kicker', 'owner_kicker' => 80,
            'title', 'studio_title', 'event_title', 'owner_title' => 160,
            default => 600,
        };
    }

    public static function defaults(): array
    {
        return [
            'en' => [
                'kicker' => 'Book a project',
                'title' => "Choose a booking",
                'lead' => "Have a project in mind? Tell us about it. We'll get back to you as soon as possible.",
                'hint' => 'Looking for a listed service? Open it from the services page and use Book this service.',
                'studio_kicker' => 'Studio rental',
                'studio_title' => 'Rent the studio',
                'studio_copy' => 'Hold the room for a shoot, a session, or any open hours. Minimum {n} {unit}.',
                'event_kicker' => 'An event',
                'event_title' => 'Book the studio for an event',
                'event_copy' => 'A launch, a listening party, a dinner, or a private screening in the house. Minimum {n} {unit}.',
                'owner_kicker' => 'The owner',
                'owner_title' => 'Book the owner for an event',
                'owner_copy' => 'The owner directs, shoots, or hosts. Minimum {n} {unit}.',
            ],
            'fr' => [
                'kicker' => 'Réserver un projet',
                'title' => 'Travaillons ensemble',
                'lead' => 'Un projet en tête? Parlez-nous-en. Nous vous répondrons dès que possible.',
                'hint' => 'Vous cherchez un service au catalogue? Ouvrez-le depuis la page Services et utilisez Réserver ce service.',
                'studio_kicker' => 'Location de studio',
                'studio_title' => 'Louer le studio',
                'studio_copy' => 'Réservez la salle pour un tournage, une séance ou des heures libres. Minimum {n} {unit}.',
                'event_kicker' => 'Un événement',
                'event_title' => 'Réserver le studio pour un événement',
                'event_copy' => 'Un lancement, une écoute, un souper ou une projection privée dans la maison. Minimum {n} {unit}.',
                'owner_kicker' => 'Le propriétaire',
                'owner_title' => 'Réserver le propriétaire pour un événement',
                'owner_copy' => 'Le propriétaire réalise, filme ou anime. Minimum {n} {unit}.',
            ],
        ];
    }

    public static function resolved(): array
    {
        $content = self::defaults();

        foreach (self::LOCALES as $locale) {
            $saved = json_decode((string) SiteSetting::getValue('booking_page_'.$locale, ''), true);
            if (! is_array($saved)) {
                continue;
            }

            foreach (self::FIELDS as $field) {
                $value = trim((string) ($saved[$field] ?? ''));
                if ($value !== '') {
                    $content[$locale][$field] = $value;
                }
            }
        }

        return $content;
    }
}
