<?php

namespace App\Http\Requests\Owner;

use App\Enums\ExpenseCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:100', 'max:999999999.99'],
            'category' => ['required', 'string', Rule::enum(ExpenseCategory::class)],
            'description' => ['required', 'string', 'min:3', 'max:500'],
            'payment_method' => ['required', 'string', Rule::in(['cash', 'bank_transfer'])],
            'expense_date' => ['nullable', 'date'],
            'receipt_image' => ['nullable', 'image', 'max:5120'], // 5MB max
        ];
    }

    /**
     * Get custom attribute names for error messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'amount' => 'Nominal Pengeluaran',
            'category' => 'Kategori Pengeluaran',
            'description' => 'Keterangan Pengeluaran',
            'payment_method' => 'Metode Pembayaran',
            'expense_date' => 'Tanggal Pengeluaran',
            'receipt_image' => 'Foto Bukti/Nota',
        ];
    }
}
