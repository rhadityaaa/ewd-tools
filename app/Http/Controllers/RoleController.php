<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoleController extends Controller 
{
    protected $roleService;
    
    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function index()
    {
        $roles = $this->roleService->getAllRoles();

        return Inertia::render('role/Index', [
            'roles' => RoleResource::collection($roles)->resolve(),
        ]);
    }

    public function create()
    {
        return Inertia::render('role/Create');
    }

    public function store(Request $request)
    {
        $this->roleService->createRole($request->validate());

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function show($id)
    {
        $role = $this->roleService->getRoleById($id);

        return Inertia::render('role/Show', [
            'role' => new RoleResource($role),
        ]);
    }

    public function edit($id)
    {
        $role = $this->roleService->getRoleById($id);

        return Inertia::render('role/Edit', [
            'role' => new RoleResource($role),
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->roleService->updateRole($id, $request->validate());

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    } 

    public function destroy($id)
    {
        $this->roleService->deleteRole($id);

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }
}
