<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $settings = [
            'owner_email' => 'studiofivestarmtl@gmail.com',
            'owner_email_secondary' => 'vladimirphilogene@gmail.com',
        ];

        foreach ($settings as $key => $value) {
            DB::table('site_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        DB::table('site_settings')->where('key', 'owner_email')->update([
            'value' => 'admin@fivestarhouse.com',
            'updated_at' => now(),
        ]);
        DB::table('site_settings')->where('key', 'owner_email_secondary')->delete();
    }
};
