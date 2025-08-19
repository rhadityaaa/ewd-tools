<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Http\Requests\UserRequest;
use App\Services\DivisionService;
use App\Services\RoleService;
use App\Services\UserService;
use Inertia\Inertia;

class UserController extends Controller
{
    protected $userService;
    protected $divisionService;
    protected $roleService;

    public function __construct(UserService $userService, DivisionService $divisionService, RoleService $roleService)
    {
        $this->userService = $userService;
        $this->divisionService = $divisionService;
        $this->roleService = $roleService;
    }

    public function index()
    {
        $users = $this->userService->getAllUsers();

        return Inertia::render('user/Index', [
            'users' => UserResource::collection($users)->resolve(),
        ]);
    }

    public function create()
    {
        $divisions = $this->divisionService->getAllDivisions();
        $roles = $this->roleService->getAllRoles();

        return Inertia::render('user/Create', [
            'divisions' => $divisions,
            'roles' => $roles,
        ]);
    }

    public function store(UserRequest $request)
    {
        $validated = $request->validated();

        $this->userService->createUser($validated);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function show(int $id)
    {
        $user = $this->userService->getUserById($id);
        
        return Inertia::render('user/Show', [
            'user' => new UserResource($user)->resolve(),
        ]);
    }

    public function edit(int $id)
    {
        $divisions = $this->divisionService->getAllDivisions();
        $roles = $this->roleService->getAllRoles();

        $this->userService->getUserById($id);

        return Inertia::render('user/Edit', [
            'user' => new UserResource($this->userService->getUserById($id)),
            'divisions' => $divisions,
            'roles' => $roles,
        ]);
    }

    public function update(UserRequest $request,int $id)
    {
        $user = $this->userService->getUserById($id);

        $validated = $request->validated();

        $this->userService->updateUser($user, $validated);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(int $id)
    {
        $user = $this->userService->getUserById($id);

        $this->userService->deleteUser($user);

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
