<?php

namespace App\Http\Requests\Pos;

use App\Models\Customer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
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
        if (empty($this->customer_id)) {
            $defaultCustomer = Customer::firstOrCreate(
                ['name' => 'Pelanggan Umum'],
                [
                    'phone' => '080000000000',
                    'phone_normalized' => '+6280000000000',
                    'address' => 'Pelanggan Walk-In',
                    'whatsapp_consent' => false,
                ]
            );

            $this->merge([
                'customer_id' => $defaultCustomer->id,
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
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['required', 'integer', 'exists:services,id'],
            'items.*.service_tier_id' => ['nullable', 'integer', 'exists:service_tiers,id'],
            'items.*.actual_quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.billed_quantity' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.perfume_variant' => ['nullable', 'string', 'max:100'],
            'items.*.item_condition_notes' => ['nullable', 'string', 'max:1000'],
            'items.*.special_instructions' => ['nullable', 'string', 'max:1000'],
            'items.*.length' => ['nullable', 'numeric', 'min:0'],
            'items.*.width' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'surcharge_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_status' => ['nullable', 'string', 'in:paid,unpaid'],
            'delivery_option' => ['nullable', 'string', 'in:pickup,delivery'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'target_completion_at' => ['nullable', 'date'],
            'staging_rack' => ['nullable', 'string', 'max:30'],
            'is_debt_approved' => ['nullable', 'boolean'],
            'debt_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Custom error messages in Indonesian.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Pelanggan wajib dipilih.',
            'customer_id.exists' => 'Pelanggan yang dipilih tidak terdaftar dalam sistem.',
            'items.required' => 'Keranjang transaksi tidak boleh kosong.',
            'items.min' => 'Transaksi minimal memiliki satu item layanan.',
            'items.*.service_id.required' => 'Layanan pada item wajib dipilih.',
            'items.*.service_id.exists' => 'Layanan yang dipilih tidak valid.',
            'items.*.actual_quantity.required' => 'Jumlah/berat aktual wajib diisi.',
            'items.*.actual_quantity.min' => 'Jumlah/berat aktual minimal 0.01.',
        ];
    }
}
