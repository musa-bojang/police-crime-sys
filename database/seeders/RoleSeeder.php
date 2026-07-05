<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Seeds the three roles and one bootstrap admin.
 *
 * Requires spatie/laravel-permission. Add `use HasRoles;` to App\Models\User.
 * Register in DatabaseSeeder and run: php artisan db:seed --class=RoleSeeder
 *
 * IMPORTANT: change the admin password immediately after first login.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['officer', 'supervisor', 'admin'] as $role) {
            Role::findOrCreate($role);
        }

        $admin = User::firstOrCreate(
            ['service_number' => 'ADMIN-001'],
            [
                'name'      => 'System Administrator',
                'is_active' => true,
                'password' => 'change-me-now',
            ],
        );

        $admin->assignRole('admin');
    }
}
