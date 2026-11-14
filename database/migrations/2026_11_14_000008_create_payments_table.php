<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('shift_id')->nullable()->index();

            $table->string('payment_number', 32)->unique();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->string('payment_method', 30); // cash, bank_transfer, qris, e_wallet / ewallet

            $table->decimal('amount', 12, 2);
            $table->decimal('tender_amount', 12, 2);
            $table->decimal('change_amount', 12, 2)->default(0.00);

            $table->string('payment_status', 30)->default('completed'); // completed, pending, failed
            $table->string('transaction_reference', 100)->nullable();
            $table->timestamp('paid_at');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['transaction_id', 'payment_status']);
            $table->index('cashier_id');
            $table->index('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
