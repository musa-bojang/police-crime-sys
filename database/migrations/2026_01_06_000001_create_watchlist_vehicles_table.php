<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wanted vehicles. Created by supervisors in the back office, synced down to
 * officers' devices for offline plate checks.
 *
 * `plate_normalized` (uppercased, alphanumeric only) is the column we actually
 * match against, so "BJL 1234", "BJL-1234" and "bjl1234" all resolve to the
 * same vehicle. It's set automatically by the model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watchlist_vehicles', function (Blueprint $table) {
            $table->id();

            $table->string('plate');                 // as entered
            $table->string('plate_normalized')->index();

            $table->string('vehicle_make')->nullable();
            $table->string('vehicle_color')->nullable();
            $table->string('vehicle_type')->nullable();

            $table->text('reason');                  // why it's wanted
            $table->string('severity')->default('wanted');  // caution/wanted/dangerous
            $table->text('instructions')->nullable();       // what the officer should do
            $table->string('status')->default('active');    // active/cleared

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('cleared_by')->nullable()->constrained('users');
            $table->dateTime('cleared_at')->nullable();

            // If promoted from a fled-scene offence, link back to it.
            $table->foreignUuid('source_offence_id')
                  ->nullable()
                  ->constrained('offences')
                  ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();   // retention: cleared entries can be purged

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watchlist_vehicles');
    }
};
