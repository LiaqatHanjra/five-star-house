<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'minimum_hours'],
            ['value' => '1', 'updated_at' => $now, 'created_at' => $now]
        );
        DB::table('services')->where('minimum_hours', 2)->update([
            'minimum_hours' => 1,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $now = now();
        DB::table('site_settings')->where('key', 'minimum_hours')->update([
            'value' => '2',
            'updated_at' => $now,
        ]);
        DB::table('services')->where('minimum_hours', 1)->update([
            'minimum_hours' => 2,
            'updated_at' => $now,
        ]);
    }
};
