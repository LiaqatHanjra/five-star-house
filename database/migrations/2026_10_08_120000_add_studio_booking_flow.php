<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedTinyInteger('minimum_hours')->default(2);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('reference', 40)->nullable()->unique();
            $table->string('booking_kind', 40)->default('service');
            $table->date('booking_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedSmallInteger('hours')->default(2);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('event_title')->nullable();
            if (! Schema::hasColumn('bookings', 'address')) {
                $table->string('address')->nullable();
            }
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 40)->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 8)->default('CAD');
            $table->string('status')->default('pending');
            $table->string('method')->default('online');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $now = now();
        $settings = [
            'owner_name' => 'Studio Admin',
            'owner_email' => 'admin@fivestarhouse.com',
            'phone' => '+1 514 555 0188',
            'address' => '4th Floor, Mile-Ex / Petite Italie, Montreal, QC',
            'open_time' => '09:00',
            'close_time' => '19:00',
            'minimum_hours' => '2',
            'currency' => 'CAD',
            'studio_hourly_rate' => '150',
            'event_hourly_rate' => '250',
            'owner_hourly_rate' => '300',
        ];
        foreach ($settings as $key => $value) {
            DB::table('site_settings')->insert([
                'key' => $key,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('services')->update([
            'minimum_hours' => 2,
            'charge_unit' => 'hour',
        ]);

        DB::table('services')->where('slug', 'film-production')->update([
            'title' => 'SHOT VIDEO',
            'slug' => 'shot-video',
            'summary' => 'Camera, crew, and a finished video. Book the studio by the hour for commercials, reels, and short films.',
            'charge' => 180,
            'cta_label' => 'BOOK THIS SERVICE',
            'eyebrow' => 'VIDEO • COMMERCIAL',
        ]);
        DB::table('services')->where('slug', 'photography')->update([
            'title' => 'PHOTOSHOOT',
            'slug' => 'photoshoot',
            'summary' => 'Portraits, products, and campaign stills on the cyclorama or on location.',
            'charge' => 150,
            'cta_label' => 'BOOK THIS SERVICE',
            'eyebrow' => 'STILLS • EDITORIAL',
        ]);
        DB::table('services')->where('slug', 'creative-direction')->update([
            'title' => 'SONG SESSION',
            'slug' => 'song-session',
            'summary' => 'Record a song, a vocal, or a live session with the room, the engineer, and the owner on the clock.',
            'charge' => 200,
            'cta_label' => 'BOOK THIS SERVICE',
            'eyebrow' => 'MUSIC • RECORDING',
        ]);
        DB::table('services')->where('slug', 'studio-rental')->update([
            'title' => 'STUDIO FOR AN EVENT',
            'slug' => 'studio-event',
            'summary' => 'Hold the studio for a launch, a listening session, a dinner, or a private screening.',
            'charge' => 250,
            'cta_label' => 'BOOK THIS SERVICE',
            'eyebrow' => 'EVENTS • PRIVATE HIRE',
        ]);

        if (! DB::table('services')->where('slug', 'book-the-owner')->exists()) {
            $image = DB::table('services')->value('image_url');
            DB::table('services')->insert([
                'number' => '05',
                'title' => 'BOOK THE OWNER',
                'slug' => 'book-the-owner',
                'summary' => 'Hire the studio owner to direct, shoot, or host your event.',
                'description' => 'The owner comes with the booking: direction on set, hosting at your event, or leading the shoot from the first hour to the last.',
                'eyebrow' => 'OWNER • ON CALL',
                'badge' => 'OWNER // ON SET',
                'charge' => 300,
                'currency' => 'CAD',
                'charge_unit' => 'hour',
                'minimum_hours' => 2,
                'image_url' => $image,
                'tags' => json_encode(['Direction', 'Hosting', 'On-location', 'Studio days']),
                'cta_label' => 'BOOK THIS SERVICE',
                'image_side' => 'left',
                'sort_order' => 5,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('site_settings');
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('minimum_hours');
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'reference', 'booking_kind', 'booking_date', 'start_time', 'end_time',
                'hours', 'hourly_rate', 'amount', 'event_title',
            ]);
        });
    }
};
