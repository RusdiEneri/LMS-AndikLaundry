<?php

namespace App\Http\Requests\Customer;

use App\Support\PhoneFormatter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role === 'owner';
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone') && is_string($this->phone)) {
            $this->merge([
                'phone_normalized' => PhoneFormatter::normalizeToE164($this->phone),
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
            'phone' => [
                'required',
                'string',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! PhoneFormatter::isValid((string) $value)) {
                        $fail('Nomor WhatsApp/telepon tidak valid untuk format Indonesia (minimal 10 digit).');
                    }
                },
            ],
            'phone_normalized' => [
                'nullable',
                'string',
                Rule::unique('customers', 'phone_normalized'),
            ],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'whatsapp_consent' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Custom validation error messages in Indonesian.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama pelanggan wajib diisi.',
            'phone.required' => 'Nomor telepon/WhatsApp pelanggan wajib diisi.',
            'phone_normalized.unique' => 'Nomor WhatsApp sudah terdaftar untuk pelanggan lain.',
            'email.email' => 'Format email tidak valid.',
        ];
    }
}
