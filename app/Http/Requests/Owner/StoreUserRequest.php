<?php

namespace App\Http\Requests\Owner;

use App\Support\PhoneFormatter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isOwner() ?? false;
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')],
            'role' => ['required', 'string', Rule::in(['owner', 'cashier', 'operator'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}
