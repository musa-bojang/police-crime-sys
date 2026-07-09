<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A sighting is recorded when an officer's plate check MATCHES a wanted vehicle.
 * This turns the watchlist from a passive list into a tracking tool: where and
 * when a wanted vehicle was last seen, and by whom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sightings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('watchlist_vehicle_id')
                  ->constrained('watchlist_vehicles')
                  ->cascadeOnDelete();

            $table->foreignId('officer_id')->constrained('users');

            $table->string('plate_checked')->nullable(); // what the officer typed
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('device_id')->nullable();
            $table->dateTime('sighted_at');

            $table->timestamps();

            $table->index('watchlist_vehicle_id');
            $table->index('officer_id');
            $table->index('sighted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sightings');
    }
};
