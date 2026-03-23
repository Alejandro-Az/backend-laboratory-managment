<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'clients.view',
            'clients.create',
            'clients.update',
            'clients.delete',
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.delete',
            'dashboard.view',
            'samples.view',
            'samples.create',
            'samples.update',
            'samples.delete',
            'samples.restore',
            'samples.change_status',
            'samples.change_priority',
            'samples.add_result',
            'samples.view_events',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $analystRole = Role::firstOrCreate(['name' => 'analyst', 'guard_name' => 'api']);

        $adminRole->syncPermissions($permissions);
        $analystRole->syncPermissions([
            'clients.view',
            'projects.view',
            'dashboard.view',
            'samples.view',
            'samples.change_status',
            'samples.add_result',
            'samples.view_events',
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@laboratory.local'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );

        $analyst = User::firstOrCreate(
            ['email' => 'analyst@laboratory.local'],
            [
                'name' => 'Analyst User',
                'password' => Hash::make('password'),
            ]
        );

        $admin->syncRoles([$adminRole]);
        $analyst->syncRoles([$analystRole]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
