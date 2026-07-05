<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only audit trail.
 *
 * Unlike model-change loggers, this also captures READS/EXPORTS of evidence
 * (image.viewed, image.exported) — essential for a police chain of custody.
 * There is deliberately no updated_at: rows are written once and never edited.
 * Block UPDATE/DELETE in the app (revoke those DB grants in production too,
 * or add a trigger, so the log can't be quietly rewritten).
 *
 * Example actions:
 *   offence.created, offence.updated, offence.viewed, offence.dismissed,
 *   image.viewed, image.exported, image.hash_mismatch,
 *   user.login, user.login_failed, user.role_changed, record.purged
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Actor — null for system or pre-authentication events.
            $table->foreignId('user_id')->nullable()->constrained('users');

            $table->string('action');

            // Polymorphic target. String id holds either a UUID or a bigint.
            $table->string('auditable_type')->nullable();
            $table->string('auditable_id')->nullable();

            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Request context.
            $table->string('ip_address', 45)->nullable();   // fits IPv6
            $table->string('user_agent')->nullable();
            $table->string('device_id')->nullable();
            $table->json('context')->nullable();

            // Append-only: created_at only.
            $table->dateTime('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('action');
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
