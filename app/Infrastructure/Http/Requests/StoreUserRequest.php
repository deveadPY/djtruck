<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|string|exists:roles,name',
            'activo'   => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'        => 'Ya existe un usuario con ese email.',
            'password.confirmed'  => 'Las contraseñas no coinciden.',
            'role.exists'         => 'El rol seleccionado no es válido.',
        ];
    }
}
