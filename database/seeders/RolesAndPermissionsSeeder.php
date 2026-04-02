<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Permissions ───────────────────────────────────────────────────
        $permissions = [
            // Tenders
            'view tenders',
            'create tenders',
            'edit tenders',
            'delete tenders',

            // Institutions
            'view institutions',
            'manage institutions',

            // Applications
            'view own applications',
            'view all applications',
            'evaluate applications',

            // Users
            'manage users',

            // Reports / Settings
            'view reports',
            'manage settings',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // ── Roles ─────────────────────────────────────────────────────────
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions(Permission::all());

        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->syncPermissions([
            'view tenders',
            'view own applications',
        ]);
    }
}
