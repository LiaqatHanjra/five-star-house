<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Service;
use App\Models\SiteImage;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Booking::query()->delete();
        User::query()->delete();

        User::create([
            'name' => 'Studio Admin',
            'email' => 'admin@fivestarhouse.com',
            'password' => 'FiveStar@2026',
        ]);

        SiteImage::query()->delete();
        Service::query()->delete();

        $this->call(ContentSeeder::class);
    }
}
