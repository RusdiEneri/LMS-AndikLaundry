<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isOwner() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string', 'max:100'],
            'settings.*.value' => ['nullable'],
        ];
    }

    /**
     * Configure the validator instance with domain boundary rules.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $settings = $this->input('settings');
            if (! is_array($settings)) {
                return;
            }

            foreach ($settings as $index => $item) {
                if (! is_array($item) || ! isset($item['key'])) {
                    continue;
                }

                $key = $item['key'];
                $value = $item['value'] ?? null;

                // Operational constraints
                if ($key === 'operational.default_min_weight') {
                    if ($value === null || ! is_numeric($value) || (float) $value < 0) {
                        $validator->errors()->add("settings.{$index}.value", 'Batas minimum cas kiloan tidak boleh bernilai negatif.');
                    }
                }

                if ($key === 'operational.customer_credit_ceiling') {
                    if ($value !== null && (! is_numeric($value) || (float) $value < 0)) {
                        $validator->errors()->add("settings.{$index}.value", 'Plafon kredit pelanggan tidak boleh bernilai negatif.');
                    }
                }

                if ($key === 'operational.cashier_petty_cash_limit') {
                    if ($value !== null && (! is_numeric($value) || (float) $value < 0)) {
                        $validator->errors()->add("settings.{$index}.value", 'Batas kas kecil kasir tidak boleh bernilai negatif.');
                    }
                }

                if ($key === 'operational.default_debt_due_days') {
                    if ($value !== null && (! is_numeric($value) || (int) $value < 0)) {
                        $validator->errors()->add("settings.{$index}.value", 'Jatuh tempo piutang harus berupa bilangan bulat tidak negatif.');
                    }
                }

                if ($key === 'operational.max_rewash_limit') {
                    if ($value !== null && (! is_numeric($value) || (int) $value < 0)) {
                        $validator->errors()->add("settings.{$index}.value", 'Batas siklus cuci ulang harus berupa bilangan bulat tidak negatif.');
                    }
                }

                // Financial constraints
                if ($key === 'financial.tax_rate_percent') {
                    if ($value !== null && (! is_numeric($value) || (float) $value < 0 || (float) $value > 100)) {
                        $validator->errors()->add("settings.{$index}.value", 'Tarif pajak PPN harus di antara 0.00% dan 100.00%.');
                    }
                }

                // WhatsApp constraints
                if ($key === 'whatsapp.anti_ban_rate_limit') {
                    if ($value !== null && (! is_numeric($value) || (int) $value < 1)) {
                        $validator->errors()->add("settings.{$index}.value", 'Batas pengiriman WhatsApp minimal 1 pesan per menit.');
                    }
                }

                if ($key === 'whatsapp.mode') {
                    if ($value !== null && ! in_array($value, ['automatic', 'manual', 'gateway'])) {
                        $validator->errors()->add("settings.{$index}.value", 'Mode WhatsApp harus bernilai automatic atau manual.');
                    }
                }
            }
        });
    }

    /**
     * Custom validation messages in Indonesian.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'settings.required' => 'Daftar pengaturan wajib disertakan.',
            'settings.array' => 'Format pengaturan harus berupa larik data.',
            'settings.*.key.required' => 'Kunci pengaturan wajib diisi.',
            'settings.*.key.string' => 'Kunci pengaturan harus berupa teks.',
            'settings.*.key.max' => 'Kunci pengaturan tidak boleh lebih dari 100 karakter.',
        ];
    }
}
