<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function getAllUsers(): Collection
    {
        $users = User::with(['role', 'division'])->get();

        return $users;
    }

    public function getUserById($id): User
    {
        $user = User::with(['role', 'division'])->findOrFail($id);

        return $user;
    }

    public function createUser(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return $user;
    }

    public function updateUser(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return $user;
    }

    public function deleteUser(User $user): void
    {
        $user->delete();
    }
}