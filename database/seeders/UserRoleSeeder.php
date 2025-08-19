<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Division;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Get divisions
        $divisions = Division::all();
        $businessDivision = $divisions->where('name', 'like', '%bisnis%')->first() ?? $divisions->first();
        $riskDivision = $divisions->where('name', 'like', '%risk%')->first() ?? $divisions->skip(1)->first();

        $users = [
            [
                'name' => 'Super Administrator',
                'email' => 'admin@ewd.test',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'division_id' => null
            ],
            [
                'name' => 'Unit Bisnis Staff',
                'email' => 'unitbisnis@ewd.test',
                'password' => Hash::make('password'),
                'role' => 'unit_bisnis',
                'division_id' => $businessDivision?->id
            ],
            [
                'name' => 'Risk Analyst',
                'email' => 'riskanalyst@ewd.test',
                'password' => Hash::make('password'),
                'role' => 'risk_analyst',
                'division_id' => $riskDivision?->id
            ],
            [
                'name' => 'Kepala Departemen Bisnis',
                'email' => 'kadeptbisnis@ewd.test',
                'password' => Hash::make('password'),
                'role' => 'kadept_bisnis',
                'division_id' => $businessDivision?->id
            ],
            [
                'name' => 'Kepala Departemen Risk',
                'email' => 'kadeptrisk@ewd.test',
                'password' => Hash::make('password'),
                'role' => 'kadept_risk',
                'division_id' => $riskDivision?->id
            ]
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                    'division_id' => $userData['division_id']
                ]
            );

            // Assign role using Spatie Permission
            $role = Role::where('name', $userData['role'])->first();
            if ($role) {
                $user->assignRole($role);
            }
        }

        $this->command->info('Users with roles created successfully!');
    }
}