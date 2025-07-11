<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => $this->isMethod('post') ? 'required|email|unique:users,email' : 'required|email',
            'password' => $this->isMethod('post') ? 'required|string|min:6' : 'nullable|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'division_id' => 'nullable|exists:divisions,id',
        ];
    }
}
