<?php

use App\Enums\PaymentStatus;
use App\Enums\ProductionStatus;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ProductionStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Milestone 3: Production State Machine FSM & Workflow Unit Tests
|--------------------------------------------------------------------------
| Specification Source: docs/PRD.md FEAT-104, docs/business-requirements.md §5,
|                       docs/database-schema.md Table 11
*/

describe('Milestone 3: Production FSM - 8-State Happy Path Workflow', function () {

    it('T3-FSM-01: executes full 8-state sequential lifecycle from received to completed', function () {
        $fsmService = app(ProductionStatusService::class);
        $operator = User::factory()->create(['role' => 'operator']);

        // Intake: order initialized at RECEIVED, fully paid
        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::RECEIVED,
            'payment_status' => PaymentStatus::PAID,
            'total_amount' => 50000.00,
            'paid_amount' => 50000.00,
            'outstanding_balance' => 0.00,
            'staging_rack' => null,
            'rewash_count' => 0,
        ]);

        expect($transaction->production_status)->toBe(ProductionStatus::RECEIVED);

        // 1. received -> waiting
        $fsmService->transition($transaction, ProductionStatus::WAITING, $operator, 'Dimasukkan ke antrean cuci');
        expect($transaction->production_status)->toBe(ProductionStatus::WAITING);

        // 2. waiting -> washing
        $fsmService->transition($transaction, ProductionStatus::WASHING, $operator, 'Mulai proses pencucian mesin #3');
        expect($transaction->production_status)->toBe(ProductionStatus::WASHING);

        // 3. washing -> drying
        $fsmService->transition($transaction, ProductionStatus::DRYING, $operator, 'Pindah ke mesin pengering');
        expect($transaction->production_status)->toBe(ProductionStatus::DRYING);

        // 4. drying -> ironing
        $fsmService->transition($transaction, ProductionStatus::IRONING, $operator, 'Masuk ke meja setrika uap');
        expect($transaction->production_status)->toBe(ProductionStatus::IRONING);

        // 5. ironing -> qc
        $fsmService->transition($transaction, ProductionStatus::QC, $operator, 'Pemeriksaan kebersihan dan kerapian');
        expect($transaction->production_status)->toBe(ProductionStatus::QC);

        // 6. qc -> ready_for_pickup (requires staging rack coordinate)
        $fsmService->transition(
            $transaction,
            ProductionStatus::READY_FOR_PICKUP,
            $operator,
            'Packing selesai, diletakkan di rak',
            ['staging_rack' => 'RACK-B-05']
        );
        expect($transaction->production_status)->toBe(ProductionStatus::READY_FOR_PICKUP)
            ->and($transaction->staging_rack)->toBe('RACK-B-05');

        // 7. ready_for_pickup -> completed (order is paid)
        $fsmService->transition($transaction, ProductionStatus::COMPLETED, $operator, 'Serah terima ke pelanggan');
        expect($transaction->production_status)->toBe(ProductionStatus::COMPLETED)
            ->and($transaction->actual_completion_at)->not->toBeNull()
            ->and($transaction->picked_up_at)->not->toBeNull();

        // Verify full immutable audit history chain (7 transitions)
        expect($transaction->statusHistories()->count())->toBe(7);
    });
});

describe('Milestone 3: Production FSM - Invalid Transition Prevention', function () {

    it('T3-FSM-02: prevents illegal jumps across production stages', function () {
        $fsmService = app(ProductionStatusService::class);
        $operator = User::factory()->create(['role' => 'operator']);

        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::RECEIVED,
        ]);

        // Attempt jump: received -> ironing (illegal)
        expect(function () use ($fsmService, $transaction, $operator) {
            $fsmService->transition($transaction, ProductionStatus::IRONING, $operator);
        })->toThrow(InvalidArgumentException::class, "Invalid production status transition from 'received' to 'ironing'.");

        // Attempt jump: received -> completed (illegal)
        expect(function () use ($fsmService, $transaction, $operator) {
            $fsmService->transition($transaction, ProductionStatus::COMPLETED, $operator);
        })->toThrow(InvalidArgumentException::class, "Invalid production status transition from 'received' to 'completed'.");

        // Advance to waiting
        $fsmService->transition($transaction, ProductionStatus::WAITING, $operator);

        // Attempt jump: waiting -> qc (illegal)
        expect(function () use ($fsmService, $transaction, $operator) {
            $fsmService->transition($transaction, ProductionStatus::QC, $operator);
        })->toThrow(InvalidArgumentException::class, "Invalid production status transition from 'waiting' to 'qc'.");
    });

    it('T3-FSM-03: terminal states cannot transition further', function () {
        $fsmService = app(ProductionStatusService::class);
        $operator = User::factory()->create(['role' => 'operator']);

        // COMPLETED is terminal
        $completedTx = Transaction::factory()->create([
            'production_status' => ProductionStatus::COMPLETED,
        ]);

        expect(function () use ($fsmService, $completedTx, $operator) {
            $fsmService->transition($completedTx, ProductionStatus::WASHING, $operator);
        })->toThrow(InvalidArgumentException::class);

        // CANCELLED is terminal
        $cancelledTx = Transaction::factory()->create([
            'production_status' => ProductionStatus::CANCELLED,
        ]);

        expect(function () use ($fsmService, $cancelledTx, $operator) {
            $fsmService->transition($cancelledTx, ProductionStatus::WAITING, $operator);
        })->toThrow(InvalidArgumentException::class);
    });
});

describe('Milestone 3: Production FSM - QC Rewash Loop & Limits', function () {

    it('T3-REWASH-01: requires reason notes when routing garment back to washing', function () {
        $fsmService = app(ProductionStatusService::class);
        $operator = User::factory()->create(['role' => 'operator']);

        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::QC,
            'rewash_count' => 0,
        ]);

        // Missing reason/notes for rewash
        expect(function () use ($fsmService, $transaction, $operator) {
            $fsmService->transition($transaction, ProductionStatus::WASHING, $operator, null);
        })->toThrow(InvalidArgumentException::class, 'Rewash requires a reason code or notes.');
    });

    it('T3-REWASH-02: permits rewash up to 2 cycles and blocks 3rd rewash attempt', function () {
        $fsmService = app(ProductionStatusService::class);
        $operator = User::factory()->create(['role' => 'operator']);

        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::QC,
            'rewash_count' => 0,
        ]);

        // Rewash Cycle 1: qc -> washing
        $fsmService->transition($transaction, ProductionStatus::WASHING, $operator, 'Noda minyak belum hilang sempurna');
        expect($transaction->production_status)->toBe(ProductionStatus::WASHING)
            ->and($transaction->rewash_count)->toBe(1);

        // Progress back to QC
        $fsmService->transition($transaction, ProductionStatus::DRYING, $operator);
        $fsmService->transition($transaction, ProductionStatus::IRONING, $operator);
        $fsmService->transition($transaction, ProductionStatus::QC, $operator);

        // Rewash Cycle 2: qc -> washing
        $fsmService->transition($transaction, ProductionStatus::WASHING, $operator, 'Bilas ulang noda kerah');
        expect($transaction->production_status)->toBe(ProductionStatus::WASHING)
            ->and($transaction->rewash_count)->toBe(2);

        // Progress back to QC again
        $fsmService->transition($transaction, ProductionStatus::DRYING, $operator);
        $fsmService->transition($transaction, ProductionStatus::IRONING, $operator);
        $fsmService->transition($transaction, ProductionStatus::QC, $operator);

        // Rewash Cycle 3: qc -> washing must be BLOCKED (capped at 2 cycles)
        expect(function () use ($fsmService, $transaction, $operator) {
            $fsmService->transition($transaction, ProductionStatus::WASHING, $operator, 'Minta cuci ulang ketiga');
        })->toThrow(InvalidArgumentException::class, 'Maximum rewash limit reached (2 cycles). Order cannot undergo further rewash cycles.');

        expect($transaction->production_status)->toBe(ProductionStatus::QC)
            ->and($transaction->rewash_count)->toBe(2);
    });

    it('T3-REWASH-03: supports rewash loop from ironing back to washing', function () {
        $fsmService = app(ProductionStatusService::class);
        $operator = User::factory()->create(['role' => 'operator']);

        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::IRONING,
            'rewash_count' => 0,
        ]);

        $fsmService->transition($transaction, ProductionStatus::WASHING, $operator, 'Ditemukan bercak saat setrika');
        expect($transaction->production_status)->toBe(ProductionStatus::WASHING)
            ->and($transaction->rewash_count)->toBe(1);
    });

    it('T3-REWASH-04: rejects rewash transition when notes or reason contain only whitespace', function () {
        $fsmService = app(ProductionStatusService::class);
        $operator = User::factory()->create(['role' => 'operator']);

        // 1. qc -> washing with whitespace-only notes
        $txQc = Transaction::factory()->create([
            'production_status' => ProductionStatus::QC,
            'rewash_count' => 0,
        ]);

        expect(function () use ($fsmService, $txQc, $operator) {
            $fsmService->transition($txQc, ProductionStatus::WASHING, $operator, '   ');
        })->toThrow(InvalidArgumentException::class, 'Rewash requires a reason code or notes.');

        // 2. qc -> washing with whitespace-only metadata reason
        expect(function () use ($fsmService, $txQc, $operator) {
            $fsmService->transition($txQc, ProductionStatus::WASHING, $operator, null, ['reason' => "  \t \n "]);
        })->toThrow(InvalidArgumentException::class, 'Rewash requires a reason code or notes.');

        // 3. ironing -> washing with whitespace-only notes
        $txIroning = Transaction::factory()->create([
            'production_status' => ProductionStatus::IRONING,
            'rewash_count' => 0,
        ]);

        expect(function () use ($fsmService, $txIroning, $operator) {
            $fsmService->transition($txIroning, ProductionStatus::WASHING, $operator, "   \n\t  ");
        })->toThrow(InvalidArgumentException::class, 'Rewash requires a reason code or notes.');
    });
});

describe('Milestone 3: Production FSM - Physical Rack Staging Coordinate', function () {

    it('T3-RACK-01: transition to ready_for_pickup requires valid staging rack coordinate', function () {
        $fsmService = app(ProductionStatusService::class);
        $operator = User::factory()->create(['role' => 'operator']);

        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::QC,
            'staging_rack' => null,
        ]);

        // Attempt transition without staging rack
        expect(function () use ($fsmService, $transaction, $operator) {
            $fsmService->transition($transaction, ProductionStatus::READY_FOR_PICKUP, $operator, 'Siap ambil');
        })->toThrow(InvalidArgumentException::class, 'A valid staging rack coordinate is required when marking order ready for pickup.');

        // Transition with staging rack in metadata succeeds
        $fsmService->transition(
            $transaction,
            ProductionStatus::READY_FOR_PICKUP,
            $operator,
            'Disimpan di rak',
            ['staging_rack' => 'RACK-C-12']
        );

        expect($transaction->production_status)->toBe(ProductionStatus::READY_FOR_PICKUP)
            ->and($transaction->staging_rack)->toBe('RACK-C-12');
    });

    it('T3-RACK-02: transition to ready_for_pickup rejects whitespace-only staging rack coordinate and trims valid input', function () {
        $fsmService = app(ProductionStatusService::class);
        $operator = User::factory()->create(['role' => 'operator']);

        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::QC,
            'staging_rack' => null,
        ]);

        // Attempt transition with whitespace staging rack in metadata
        expect(function () use ($fsmService, $transaction, $operator) {
            $fsmService->transition(
                $transaction,
                ProductionStatus::READY_FOR_PICKUP,
                $operator,
                'Siap ambil',
                ['staging_rack' => '   ']
            );
        })->toThrow(InvalidArgumentException::class, 'A valid staging rack coordinate is required when marking order ready for pickup.');

        expect($transaction->production_status)->toBe(ProductionStatus::QC)
            ->and($transaction->staging_rack)->toBeNull();

        // Valid coordinate with surrounding whitespace should be trimmed
        $fsmService->transition(
            $transaction,
            ProductionStatus::READY_FOR_PICKUP,
            $operator,
            'Disimpan di rak',
            ['staging_rack' => '  RACK-D-99  ']
        );

        expect($transaction->production_status)->toBe(ProductionStatus::READY_FOR_PICKUP)
            ->and($transaction->staging_rack)->toBe('RACK-D-99');
    });
});

describe('Milestone 3: Counter Release Guard', function () {

    it('T3-GUARD-01: blocks release to completed if order is unpaid and debt is not approved', function () {
        $fsmService = app(ProductionStatusService::class);
        $cashier = User::factory()->create(['role' => 'cashier']);

        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::READY_FOR_PICKUP,
            'payment_status' => PaymentStatus::UNPAID,
            'total_amount' => 60000.00,
            'paid_amount' => 0.00,
            'outstanding_balance' => 60000.00,
            'is_debt_approved' => false,
        ]);

        expect($fsmService->canRelease($transaction))->toBeFalse();

        expect(function () use ($fsmService, $transaction, $cashier) {
            $fsmService->transition($transaction, ProductionStatus::COMPLETED, $cashier, 'Mau diambil');
        })->toThrow(InvalidArgumentException::class, 'Cannot release order: payment has not been settled and credit/debt is not approved.');

        expect($transaction->production_status)->toBe(ProductionStatus::READY_FOR_PICKUP);
    });

    it('T3-GUARD-02: allows release to completed if unpaid order has authorized debt approval', function () {
        $fsmService = app(ProductionStatusService::class);
        $owner = User::factory()->create(['role' => 'owner']);

        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::READY_FOR_PICKUP,
            'payment_status' => PaymentStatus::PARTIALLY_PAID,
            'total_amount' => 100000.00,
            'paid_amount' => 30000.00,
            'outstanding_balance' => 70000.00,
            'is_debt_approved' => true,
            'debt_notes' => 'Disetujui ambil dulu oleh Owner (langganan)',
        ]);

        expect($fsmService->canRelease($transaction))->toBeTrue();

        $fsmService->transition($transaction, ProductionStatus::COMPLETED, $owner, 'Serah terima via izin piutang');
        expect($transaction->production_status)->toBe(ProductionStatus::COMPLETED)
            ->and($transaction->actual_completion_at)->not->toBeNull()
            ->and($transaction->picked_up_at)->not->toBeNull();
    });

    it('T3-GUARD-03: allows release to completed when order is fully paid', function () {
        $fsmService = app(ProductionStatusService::class);
        $cashier = User::factory()->create(['role' => 'cashier']);

        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::READY_FOR_PICKUP,
            'payment_status' => PaymentStatus::PAID,
            'total_amount' => 50000.00,
            'paid_amount' => 50000.00,
            'outstanding_balance' => 0.00,
            'is_debt_approved' => false,
        ]);

        expect($fsmService->canRelease($transaction))->toBeTrue();

        $fsmService->transition($transaction, ProductionStatus::COMPLETED, $cashier, 'Pengambilan lunas');
        expect($transaction->production_status)->toBe(ProductionStatus::COMPLETED);
    });
});

describe('Milestone 3: Production FSM - Audit Logging & Cancellation', function () {

    it('T3-AUDIT-01: records immutable audit history entries on each transition', function () {
        $fsmService = app(ProductionStatusService::class);
        $operator = User::factory()->create(['role' => 'operator']);

        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::RECEIVED,
        ]);

        $fsmService->transition(
            $transaction,
            ProductionStatus::WAITING,
            $operator,
            'Catatan transisi pengujian',
            ['machine' => 'M01', 'shift' => 1]
        );

        $history = $transaction->statusHistories()->latest()->first();

        expect($history)->not->toBeNull()
            ->and($history->user_id)->toBe($operator->id)
            ->and($history->from_status)->toBe(ProductionStatus::RECEIVED)
            ->and($history->to_status)->toBe(ProductionStatus::WAITING)
            ->and($history->notes)->toBe('Catatan transisi pengujian')
            ->and($history->metadata)->toBe(['machine' => 'M01', 'shift' => 1])
            ->and($history->created_at)->not->toBeNull();
    });

    it('T3-AUDIT-02: allows cancellation from any active state and records reason', function () {
        $fsmService = app(ProductionStatusService::class);
        $cashier = User::factory()->create(['role' => 'cashier']);

        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::WAITING,
        ]);

        $fsmService->transition(
            $transaction,
            ProductionStatus::CANCELLED,
            $cashier,
            'Pelanggan membatalkan pesanan karena baju mau dipakai mendadak',
            ['reason' => 'Customer withdrawal']
        );

        expect($transaction->production_status)->toBe(ProductionStatus::CANCELLED)
            ->and($transaction->cancellation_reason)->toBe('Pelanggan membatalkan pesanan karena baju mau dipakai mendadak');

        $history = $transaction->statusHistories()->latest()->first();
        expect($history->to_status)->toBe(ProductionStatus::CANCELLED);
    });
});
