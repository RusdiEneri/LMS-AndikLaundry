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
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('invoice_number', 32)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('shift_id')->nullable()->index();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('production_status', 30)->default('received');
            $table->string('payment_status', 30)->default('unpaid');

            $table->timestamp('intake_at')->useCurrent();
            $table->timestamp('target_completion_at')->nullable();
            $table->timestamp('actual_completion_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();

            $table->decimal('subtotal_amount', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('surcharge_amount', 12, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->decimal('paid_amount', 12, 2)->default(0.00);
            $table->decimal('change_amount', 12, 2)->default(0.00);
            $table->decimal('outstanding_balance', 12, 2)->default(0.00);

            $table->string('staging_rack', 30)->nullable();
            $table->unsignedInteger('rewash_count')->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_debt_approved')->default(false);
            $table->text('debt_notes')->nullable();
            $table->string('cancellation_reason', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'production_status']);
            $table->index(['payment_status', 'outstanding_balance']);
            $table->index('cashier_id');
            $table->index('created_at');
            $table->index('intake_at');
        });

        Schema::create('transaction_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('service_tier_id')->nullable()->constrained('service_tiers')->nullOnDelete();

            $table->string('item_name_snapshot', 150);
            $table->string('pricing_unit_snapshot', 20)->default('kg');
            $table->decimal('unit_price_snapshot', 12, 2);

            $table->decimal('actual_quantity', 8, 2);
            $table->decimal('billed_quantity', 8, 2);

            $table->decimal('discount_amount_snapshot', 12, 2)->default(0.00);
            $table->decimal('subtotal_snapshot', 12, 2);

            $table->string('perfume_variant', 100)->nullable();
            $table->text('item_condition_notes')->nullable();
            $table->text('special_instructions')->nullable();

            $table->timestamps();

            $table->index('transaction_id');
            $table->index('service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');
    }
};
