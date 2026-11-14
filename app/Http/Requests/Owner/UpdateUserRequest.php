<?php

namespace App\Http\Requests\Owner;

use App\Models\User;
use App\Support\PhoneFormatter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Enforces self-demotion and self-deactivation protection guards with HTTP 403.
     */
    public function authorize(): bool
    {
        $currentUser = $this->user();
        if (! $currentUser?->isOwner()) {
            return false;
        }

        $targetRouteUser = $this->route('user');
        $targetUserId = $targetRouteUser instanceof User ? $targetRouteUser->id : (int) $targetRouteUser;

        if ($currentUser->id === $targetUserId) {
            // Guard against self-demotion
            if ($this->has('role') && $this->input('role') !== 'owner') {
                return false;
            }

            // Guard against self-deactivation
            if ($this->has('is_active') && ! filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Prepare inputs for validation: normalize phone number to E.164.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone') && is_string($this->phone)) {
            $this->merge([
                'phone' => PhoneFormatter::normalize($this->phone),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $targetRouteUser = $this->route('user');
        $targetUserId = $targetRouteUser instanceof User ? $targetRouteUser->id : (int) $targetRouteUser;

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($targetUserId)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($targetUserId)],
            'role' => ['required', 'string', Rule::in(['owner', 'cashier', 'operator'])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama pengguna wajib diisi.',
            'name.max' => 'Nama pengguna maksimal 100 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar dalam sistem.',
            'phone.required' => 'Nomor WhatsApp/telepon wajib diisi.',
            'phone.unique' => 'Nomor telepon sudah terdaftar dalam sistem.',
            'role.required' => 'Peran pengguna wajib dipilih.',
            'role.in' => 'Peran yang dipilih tidak valid.',
            'password.min' => 'Password minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}
