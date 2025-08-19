<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions first
        $permissions = [
            // Dashboard permissions
            'view_dashboard',
            'view_statistics',
            
            // Borrower management
            'view_borrowers',
            'create_borrowers',
            'edit_borrowers',
            'delete_borrowers',
            
            // Report management
            'view_reports',
            'create_reports',
            'edit_reports',
            'delete_reports',
            'submit_reports',
            'approve_reports',
            'reject_reports',
            'override_reports',
            
            // Template management
            'view_templates',
            'create_templates',
            'edit_templates',
            'delete_templates',
            
            // Aspect management
            'view_aspects',
            'create_aspects',
            'edit_aspects',
            'delete_aspects',
            
            // User management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            
            // Role management
            'view_roles',
            'create_roles',
            'edit_roles',
            'delete_roles',
            
            // Division management
            'view_divisions',
            'create_divisions',
            'edit_divisions',
            'delete_divisions',
            
            // Period management
            'view_periods',
            'create_periods',
            'edit_periods',
            'delete_periods',
            'start_periods',
            'end_periods',
            
            // Watchlist management
            'view_watchlist',
            'create_watchlist',
            'edit_watchlist',
            'resolve_watchlist',
            
            // Monitoring notes
            'view_monitoring_notes',
            'create_monitoring_notes',
            'edit_monitoring_notes',
            'submit_monitoring_notes',
            
            // System settings
            'view_settings',
            'edit_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Create roles with specific permissions
        $roles = [
            [
                'name' => 'super_admin',
                // 'display_name' => 'Super Administrator',
                'description' => 'Full system access and management',
                'permissions' => $permissions // All permissions
            ],
            [
                'name' => 'unit_bisnis',
                // 'display_name' => 'Unit Bisnis',
                'description' => 'Business unit staff - can create and submit reports',
                'permissions' => [
                    'view_dashboard',
                    'view_statistics',
                    'view_borrowers',
                    'create_borrowers',
                    'edit_borrowers',
                    'view_reports',
                    'create_reports',
                    'edit_reports',
                    'submit_reports',
                    'view_templates',
                    'view_aspects',
                    'view_periods',
                    'view_watchlist',
                    'view_monitoring_notes',
                    'create_monitoring_notes',
                    'edit_monitoring_notes',
                ]
            ],
            [
                'name' => 'risk_analyst',
                // 'display_name' => 'Risk Analyst',
                'description' => 'Risk analysis specialist - reviews and analyzes reports',
                'permissions' => [
                    'view_dashboard',
                    'view_statistics',
                    'view_borrowers',
                    'view_reports',
                    'edit_reports',
                    'approve_reports',
                    'reject_reports',
                    'view_templates',
                    'view_aspects',
                    'view_periods',
                    'view_watchlist',
                    'create_watchlist',
                    'edit_watchlist',
                    'view_monitoring_notes',
                    'create_monitoring_notes',
                    'edit_monitoring_notes',
                ]
            ],
            [
                'name' => 'kadept_bisnis',
                // 'display_name' => 'Kepala Departemen Bisnis',
                'description' => 'Head of Business Department - approves business decisions',
                'permissions' => [
                    'view_dashboard',
                    'view_statistics',
                    'view_borrowers',
                    'create_borrowers',
                    'edit_borrowers',
                    'view_reports',
                    'edit_reports',
                    'approve_reports',
                    'reject_reports',
                    'override_reports',
                    'view_templates',
                    'create_templates',
                    'edit_templates',
                    'view_aspects',
                    'create_aspects',
                    'edit_aspects',
                    'view_periods',
                    'view_users',
                    'view_divisions',
                    'view_watchlist',
                    'create_watchlist',
                    'edit_watchlist',
                    'resolve_watchlist',
                    'view_monitoring_notes',
                    'create_monitoring_notes',
                    'edit_monitoring_notes',
                    'submit_monitoring_notes',
                ]
            ],
            [
                'name' => 'kadept_risk',
                // 'display_name' => 'Kepala Departemen Risk',
                'description' => 'Head of Risk Department - final approval authority',
                'permissions' => [
                    'view_dashboard',
                    'view_statistics',
                    'view_borrowers',
                    'view_reports',
                    'edit_reports',
                    'approve_reports',
                    'reject_reports',
                    'override_reports',
                    'view_templates',
                    'create_templates',
                    'edit_templates',
                    'view_aspects',
                    'create_aspects',
                    'edit_aspects',
                    'view_periods',
                    'create_periods',
                    'edit_periods',
                    'start_periods',
                    'end_periods',
                    'view_users',
                    'view_divisions',
                    'view_watchlist',
                    'create_watchlist',
                    'edit_watchlist',
                    'resolve_watchlist',
                    'view_monitoring_notes',
                    'create_monitoring_notes',
                    'edit_monitoring_notes',
                    'submit_monitoring_notes',
                ]
            ]
        ];

        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleData['name']],
                [
                    'guard_name' => 'web',
                    // 'display_name' => $roleData['display_name'],
                    // 'description' => $roleData['description']
                ]
            );

            // Assign permissions to role
            $role->syncPermissions($roleData['permissions']);
        }

        $this->command->info('Roles and permissions created successfully!');
    }
}
