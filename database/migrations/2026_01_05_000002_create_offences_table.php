<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traffic offence records.
 *
 * The primary key is a client-generated UUID so the Flutter app can create
 * a record while offline (no round-trip to the server needed for an id).
 * `offence_type`, `driver_gender` and `status` are stored as plain strings
 * and validated in the app via PHP enums / casts, which keeps future value
 * changes out of the migration layer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offences', function (Blueprint $table) {
            // Generated on the device so records work fully offline.
            $table->uuid('id')->primary();

            // Human-friendly case number, assigned server-side on first sync
            // (e.g. OFF-2026-000123). Null until the record reaches the server.
            $table->string('reference_number')->nullable()->unique();

            // Who recorded it, and on which device (chain of custody).
            $table->foreignId('officer_id')->constrained('users');
            $table->string('device_id')->nullable();

            // Offence
            $table->string('offence_type');                    // validated by app enum
            $table->text('offence_description')->nullable();

            // Vehicle
            $table->string('vehicle_plate')->nullable();
            $table->string('vehicle_color')->nullable();
            $table->string('vehicle_make')->nullable();
            $table->string('vehicle_type')->nullable();        // car / motorcycle / truck...

            // Driver. Gender is kept as an identifier for a fleeing driver;
            // collect only when relevant (data minimisation under the PDPP).
            $table->string('driver_gender')->nullable();       // male / female / unknown
            $table->string('driver_name')->nullable();
            $table->boolean('driver_fled')->default(false);

            // Location of the stop.
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('location_description')->nullable();

            // Timing.
            $table->dateTime('occurred_at');                   // when the offence happened
            $table->dateTime('captured_at')->nullable();       // when logged on the device
            $table->dateTime('synced_at')->nullable();         // when the server received it

            // Review workflow.
            $table->string('status')->default('submitted');    // draft/submitted/under_review/confirmed/dismissed
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->dateTime('reviewed_at')->nullable();

            // Optimistic conflict handling during sync.
            $table->unsignedInteger('version')->default(1);

            // Room to grow without a schema change.
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();   // used only by the retention/admin path

            $table->index('vehicle_plate');
            $table->index('officer_id');
            $table->index('occurred_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offences');
    }
};
