<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['day_charge', 'project_charge']);
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('day_charge', 10, 2)->default(0)->after('charge');
            $table->decimal('project_charge', 10, 2)->default(0)->after('day_charge');
        });
    }
};
