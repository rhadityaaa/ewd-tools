<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'super admin',
                'guard_name' => 'web',
            ],
            [
                'name' => 'bisnis',
                'guard_name' => 'web',
            ],
            [
                'name' => 'reviewer',
                'guard_name' => 'web',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                ['guard_name' => $role['guard_name']]
            );
        }
    }
}
