<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends Laravel's default `users` table with the fields the police
 * system needs. Officers are created in the back office (never offline),
 * so a normal auto-increment id is fine here.
 *
 * Roles/permissions are NOT defined here — they come from
 * spatie/laravel-permission (see RoleSeeder). Install it first:
 *   composer require spatie/laravel-permission
 *   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
 *   php artisan migrate
 *
 * Requires Laravel 11+ for the ->change() call (no doctrine/dbal needed).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Officers may log in by service number instead of email.
            $table->string('service_number')->nullable()->unique()->after('name');
            $table->string('rank')->nullable()->after('service_number');
            $table->string('station')->nullable()->after('rank');
            $table->string('phone')->nullable()->after('station');

            // Disable an account without deleting it (evidence stays linked).
            $table->boolean('is_active')->default(true)->after('password');
            $table->dateTime('last_login_at')->nullable()->after('is_active');

            // Keep users soft-deletable: never hard-remove someone whose id
            // is referenced by an offence or image record.
            $table->softDeletes();
        });

        // Email becomes optional since login can be by service_number.
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'service_number', 'rank', 'station',
                'phone', 'is_active', 'last_login_at', 'deleted_at',
            ]);
        });
    }
};
