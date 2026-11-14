<?php

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Enums\ProductionStatus;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountingReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/**
 * Seeds one day of activity:
 * - Nota A: completed, 100.000, paid in cash today.
 * - Nota B: completed, 50.000, unpaid (receivable).
 * - Nota C: still washing, 40.000, paid upfront (25.000 QRIS + 15.000 transfer).
 * - Nota D: cancelled with a stale balance (must not count as receivable).
 * - A pending payment and a last-month expense that must be ignored.
 * - Expenses today: 30.000 cash + 20.000 transfer.
 */
function seedAccountingScenario(): array
{
    $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

    $notaA = Transaction::factory()->completed()->paid()->create(['total_amount' => 100000, 'subtotal_amount' => 100000]);
    $notaB = Transaction::factory()->completed()->create(['total_amount' => 50000, 'subtotal_amount' => 50000, 'outstanding_balance' => 50000]);
    $notaC = Transaction::factory()->withProductionStatus(ProductionStatus::WASHING)->paid()
        ->create(['total_amount' => 40000, 'subtotal_amount' => 40000]);
    Transaction::factory()->withProductionStatus(ProductionStatus::CANCELLED)
        ->create(['total_amount' => 70000, 'outstanding_balance' => 70000]);

    Payment::factory()->create(['transaction_id' => $notaA->id, 'payment_method' => PaymentMethod::CASH, 'amount' => 100000]);
    Payment::factory()->qris(25000)->create(['transaction_id' => $notaC->id]);
    Payment::factory()->create(['transaction_id' => $notaC->id, 'payment_method' => PaymentMethod::BANK_TRANSFER, 'amount' => 15000]);
    Payment::factory()->create(['transaction_id' => $notaB->id, 'amount' => 999999, 'payment_status' => 'pending']);

    Expense::factory()->create(['user_id' => $owner->id, 'amount' => 30000, 'payment_method' => 'cash', 'category' => ExpenseCategory::GAS_LPG->value]);
    Expense::factory()->create(['user_id' => $owner->id, 'amount' => 20000, 'payment_method' => 'bank_transfer', 'category' => ExpenseCategory::UTILITIES->value]);
    Expense::factory()->create(['user_id' => $owner->id, 'amount' => 500000, 'expense_date' => Carbon::today()->subMonthsNoOverflow(2)->toDateString()]);

    return compact('owner', 'notaA', 'notaB', 'notaC');
}

describe('AccountingReportService: cash basis vs accrual basis', function () {
    it('separates real cash flow from accrual profit and explains the gap', function () {
        seedAccountingScenario();

        $report = app(AccountingReportService::class)->getFinancialReport(Carbon::today(), Carbon::today());

        // Cash basis: only completed payments, split by method.
        expect($report['cash_flow']['cash_in'])->toMatchArray([
            'tunai' => 100000.0,
            'qris' => 25000.0,
            'transfer' => 15000.0,
            'total' => 140000.0,
        ]);
        expect($report['cash_flow']['cash_out'])->toBe(50000.0);
        expect($report['cash_flow']['net_cash_flow'])->toBe(90000.0);

        // Accrual basis: completed orders A + B regardless of payment; C is not yet earned.
        expect($report['accrual']['recognized_revenue'])->toBe(150000.0);
        expect($report['accrual']['operating_expenses'])->toBe(50000.0);
        expect($report['accrual']['net_operating_profit'])->toBe(100000.0);

        // Receivables exclude cancelled orders; gap = profit - net cash.
        expect($report['health']['total_unpaid_receivables'])->toBe(50000.0);
        expect($report['health']['cash_to_accrual_gap'])->toBe(10000.0);
    });

    it('ignores activity outside the selected range', function () {
        seedAccountingScenario();

        $lastYear = Carbon::today()->subYear();
        $report = app(AccountingReportService::class)->getFinancialReport($lastYear, $lastYear);

        expect($report['cash_flow']['cash_in']['total'])->toBe(0.0);
        expect($report['cash_flow']['cash_out'])->toBe(0.0);
        expect($report['accrual']['recognized_revenue'])->toBe(0.0);
    });
});

describe('Owner financial report access', function () {
    it('passes the financialReport prop to the owner dashboard for the requested range', function () {
        ['owner' => $owner] = seedAccountingScenario();
        $today = Carbon::today()->toDateString();

        $this->actingAs($owner)
            ->get("/dashboard?start_date={$today}&end_date={$today}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Owner/Dashboard')
                ->where('financialReport.period.start_date', $today)
                ->where('financialReport.cash_flow.net_cash_flow', 90000)
                ->where('financialReport.accrual.net_operating_profit', 100000)
                ->where('financialReport.health.cash_to_accrual_gap', 10000)
            );
    });

    it('defaults the range to the start of this month through today', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $this->actingAs($owner)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('financialReport.period.start_date', Carbon::today()->startOfMonth()->toDateString())
                ->where('financialReport.period.end_date', Carbon::today()->toDateString())
            );
    });

    it('rejects an end date before the start date', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $this->actingAs($owner)
            ->get('/dashboard?start_date=2026-11-10&end_date=2026-11-01')
            ->assertSessionHasErrors('end_date');
    });

    it('forbids cashier and operator from the dashboard and CSV export', function (string $role) {
        $user = User::factory()->create(['role' => $role, 'is_active' => true]);

        $this->actingAs($user)->get('/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/owner/reports/export-csv')->assertForbidden();
    })->with(['cashier', 'operator']);

    it('redirects guests to login', function () {
        $this->get('/owner/reports/export-csv')->assertRedirect('/login');
    });
});

describe('Transaction CSV export', function () {
    it('streams a text/csv download with one row per order', function () {
        ['owner' => $owner, 'notaA' => $notaA, 'notaB' => $notaB] = seedAccountingScenario();
        $notaB->customer->update(['name' => '=HYPERLINK("http://evil")']);
        $today = Carbon::today()->toDateString();

        $response = $this->actingAs($owner)->get("/owner/reports/export-csv?start_date={$today}&end_date={$today}");

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('text/csv');
        expect($response->headers->get('Content-Disposition'))->toContain("laporan-transaksi_{$today}_{$today}.csv");

        $lines = array_values(array_filter(explode("\n", ltrim($response->streamedContent(), "\xEF\xBB\xBF"))));
        expect($lines[0])->toBe('"No Nota",Tanggal,Pelanggan,"Status Produksi","Total Tagihan",Terbayar,"Sisa Piutang","Metode Pembayaran"');
        expect($lines)->toHaveCount(5); // header + 4 orders taken today

        $csv = implode("\n", $lines);
        expect($csv)->toContain($notaA->invoice_number)
            ->toContain('100000.00')
            ->toContain('Tunai (Cash)')
            ->toContain('Belum Bayar')
            ->toContain("'=HYPERLINK");
    });
});
