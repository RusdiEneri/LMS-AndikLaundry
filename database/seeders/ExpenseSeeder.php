<?php

namespace Database\Seeders;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owner = User::where('role', 'owner')->first();
        $today = Carbon::today();

        $expenses = [
            [
                'amount' => 44000,
                'category' => ExpenseCategory::GAS_LPG->value,
                'description' => 'Beli 2 tabung Gas LPG 3kg di Toko Bu Joko',
                'payment_method' => 'cash',
                'expense_date' => $today->toDateString(),
            ],
            [
                'amount' => 75000,
                'category' => ExpenseCategory::DETERGENT_CHEMICALS->value,
                'description' => 'Beli Sabun Cair Matik 5L & Pewangi Sakura',
                'payment_method' => 'cash',
                'expense_date' => $today->toDateString(),
            ],
            [
                'amount' => 35000,
                'category' => ExpenseCategory::PACKAGING_PLASTIC->value,
                'description' => 'Plastik Kresek Jinjing & Plastik Roll Baju',
                'payment_method' => 'cash',
                'expense_date' => $today->toDateString(),
            ],
            [
                'amount' => 100000,
                'category' => ExpenseCategory::UTILITIES->value,
                'description' => 'Isi Token Listrik PLN Toko 100rb',
                'payment_method' => 'bank_transfer',
                'expense_date' => $today->toDateString(),
            ],
        ];

        foreach ($expenses as $idx => $data) {
            $date = Carbon::parse($data['expense_date']);
            $number = 'EXP-' . $date->format('Ymd') . '-' . str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT);

            Expense::updateOrCreate(
                ['expense_number' => $number],
                array_merge($data, [
                    'user_id' => $owner?->id,
                ])
            );
        }
    }
}

