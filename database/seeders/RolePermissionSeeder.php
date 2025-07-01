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

        // Create permissions for API guard
        $permissions = [
            'view users',
            'create users',
            'edit users',
            'delete users',
            'view posts',
            'create posts',
            'edit posts',
            'delete posts',
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            'manage settings',
            'view reports',
        ];

        foreach ($permissions as $permission) {
            // Create permissions for both web and api guards
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
            Permission::create(['name' => $permission, 'guard_name' => 'api']);
        }

        // Create roles and assign permissions for WEB guard
        $this->createWebRoles();

        // Create roles and assign permissions for API guard
        $this->createApiRoles();

        // Create a super admin user (optional)
        $superAdminUser = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $superAdminUser->assignRole('super-admin'); // This will use the default guard from auth.php
    }

    private function createWebRoles()
    {
        // Super Admin - gets all permissions
        $superAdmin = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::where('guard_name', 'web')->get());

        // Admin - gets most permissions
        $admin = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::where('guard_name', 'web')->whereIn('name', [
            'view users',
            'create users',
            'edit users',
            'view posts',
            'create posts',
            'edit posts',
            'delete posts',
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            'view reports',
        ])->get());

        // Editor - can manage content
        $editor = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $editor->givePermissionTo(Permission::where('guard_name', 'web')->whereIn('name', [
            'view posts',
            'create posts',
            'edit posts',
            'view categories',
            'create categories',
            'edit categories',
        ])->get());

        // Author - can create and edit own content
        $author = Role::create(['name' => 'author', 'guard_name' => 'web']);
        $author->givePermissionTo(Permission::where('guard_name', 'web')->whereIn('name', [
            'view posts',
            'create posts',
            'edit posts',
        ])->get());

        // User - basic permissions
        $user = Role::create(['name' => 'user', 'guard_name' => 'web']);
        $user->givePermissionTo(Permission::where('guard_name', 'web')->whereIn('name', [
            'view posts',
            'view categories',
        ])->get());
    }

    private function createApiRoles()
    {
        // Super Admin - gets all permissions
        $superAdmin = Role::create(['name' => 'super-admin', 'guard_name' => 'api']);
        $superAdmin->givePermissionTo(Permission::where('guard_name', 'api')->get());

        // Admin - gets most permissions
        $admin = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $admin->givePermissionTo(Permission::where('guard_name', 'api')->whereIn('name', [
            'view users',
            'create users',
            'edit users',
            'view posts',
            'create posts',
            'edit posts',
            'delete posts',
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            'view reports',
        ])->get());

        // Editor - can manage content
        $editor = Role::create(['name' => 'editor', 'guard_name' => 'api']);
        $editor->givePermissionTo(Permission::where('guard_name', 'api')->whereIn('name', [
            'view posts',
            'create posts',
            'edit posts',
            'view categories',
            'create categories',
            'edit categories',
        ])->get());

        // Author - can create and edit own content
        $author = Role::create(['name' => 'author', 'guard_name' => 'api']);
        $author->givePermissionTo(Permission::where('guard_name', 'api')->whereIn('name', [
            'view posts',
            'create posts',
            'edit posts',
        ])->get());

        // User - basic permissions
        $user = Role::create(['name' => 'user', 'guard_name' => 'api']);
        $user->givePermissionTo(Permission::where('guard_name', 'api')->whereIn('name', [
            'view posts',
            'view categories',
        ])->get());
    }
}
