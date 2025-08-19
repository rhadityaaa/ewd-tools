<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

class RoleService
{
    public function getAllRoles(): Collection
    {
        $roles = Role::all();

        return $roles;
    }

    public function getRoleById(int $id): Role
    {
        $role = Role::findOrFail($id);

        return $role;
    }

    public function createRole(array $data): Role
    {
        $role = Role::create($data);

        return $role;
    }

    public function updateRole(Role $role, array $data): Role
    {
        $role->update($data);

        return $role;
    }

    public function deleteRole(Role $role): void
    {
        $role->delete();
    }
}