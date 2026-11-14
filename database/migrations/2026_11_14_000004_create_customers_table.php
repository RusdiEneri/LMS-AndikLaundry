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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('phone', 20);
            $table->string('phone_normalized', 20)->unique('uq_customers_phone_normalized');
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->unsignedInteger('total_orders_count')->default(0);
            $table->decimal('total_spend', 12, 2)->default(0.00);
            $table->decimal('outstanding_receivables', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->boolean('whatsapp_consent')->default(true);
            $table->timestamp('consent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name', 'idx_customers_name');
            $table->index('outstanding_receivables', 'idx_customers_receivables');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
