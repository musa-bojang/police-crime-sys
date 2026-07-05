<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evidence photos attached to an offence.
 *
 * Chain of custody: the SHA-256 hash is computed on the device at capture.
 * The server re-computes it on receipt and only then sets `hash_verified_at`;
 * a mismatch flips `status` to `quarantined`. Once verified, rows are treated
 * as write-once — enforce that in a model observer/policy, not just here.
 *
 * File paths are null until the binary is actually uploaded, so the sync
 * layer can create the row first and attach the file afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offence_images', function (Blueprint $table) {
            $table->uuid('id')->primary();  // client-generated

            $table->foreignUuid('offence_id')
                  ->constrained('offences')
                  ->cascadeOnDelete();      // only fires on hard delete (retention purge)

            // Capturing actor/device (may differ from the offence's officer).
            $table->foreignId('officer_id')->constrained('users');
            $table->string('device_id')->nullable();

            // Storage — null until uploaded & verified.
            $table->string('file_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();   // bytes
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            // Integrity.
            $table->char('sha256_hash', 64);                       // hex digest from device
            $table->dateTime('hash_verified_at')->nullable();      // set after server re-hash

            // Where/when the photo was taken.
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->dateTime('captured_at');
            $table->dateTime('uploaded_at')->nullable();

            // Sync/integrity: pending, uploading, uploaded, verified, failed, quarantined
            $table->string('status')->default('pending');
            $table->unsignedInteger('version')->default(1);

            $table->timestamps();
            $table->softDeletes();

            $table->index('offence_id');
            $table->index('sha256_hash');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offence_images');
    }
};
