<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles and assign permissions for WEB guard
        $this->createWebRoles();

        // Create roles and assign permissions for API guard
        $this->createApiRoles();

        // Create a super admin user (optional)
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ])->assignRole('super-admin');
    }

    private function createWebRoles()
    {
        // Super Admin - gets all permissions
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        // Admin - gets most permissions
        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        // Author - can create and edit own content
        Role::create(['name' => 'author', 'guard_name' => 'web']);

        // User - basic permissions
        Role::create(['name' => 'user', 'guard_name' => 'web']);
    }

    private function createApiRoles()
    {
        // Super Admin - gets all permissions
        Role::create(['name' => 'super-admin', 'guard_name' => 'api']);

        // Admin - gets most permissions
        Role::create(['name' => 'admin', 'guard_name' => 'api']);

        // Editor - can manage content
        Role::create(['name' => 'editor', 'guard_name' => 'api']);

        // Author - can create and edit own content
        Role::create(['name' => 'author', 'guard_name' => 'api']);

        // User - basic permissions
        Role::create(['name' => 'user', 'guard_name' => 'api']);
    }
}
