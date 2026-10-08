<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_images', function (Blueprint $table) {
            $table->id();
            $table->string('page', 40);
            $table->string('slot', 40);
            $table->string('title')->nullable();
            $table->string('category')->nullable();
            $table->text('caption')->nullable();
            $table->string('alt')->nullable();
            $table->string('image_url', 2048);
            $table->string('col_class')->nullable();
            $table->string('aspect')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['page', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_images');
    }
};
