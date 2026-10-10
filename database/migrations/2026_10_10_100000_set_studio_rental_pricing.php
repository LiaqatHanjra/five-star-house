<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'studio_hourly_rate'],
            ['value' => '75', 'updated_at' => $now, 'created_at' => $now]
        );
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'studio_minimum_hours'],
            ['value' => '1', 'updated_at' => $now, 'created_at' => $now]
        );
    }

    public function down(): void
    {
        DB::table('site_settings')->where('key', 'studio_hourly_rate')->update(['value' => '150']);
        DB::table('site_settings')->where('key', 'studio_minimum_hours')->delete();
    }
};
