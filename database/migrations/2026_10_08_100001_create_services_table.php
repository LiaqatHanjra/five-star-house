<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('number', 8);
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary');
            $table->text('description');
            $table->string('eyebrow')->nullable();
            $table->string('badge')->nullable();
            $table->decimal('charge', 10, 2)->default(0);
            $table->string('currency', 8)->default('CAD');
            $table->string('charge_unit', 20)->default('project');
            $table->string('image_url', 2048)->nullable();
            $table->json('tags')->nullable();
            $table->string('cta_label')->default('REQUEST QUOTE');
            $table->string('image_side', 10)->default('left');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
