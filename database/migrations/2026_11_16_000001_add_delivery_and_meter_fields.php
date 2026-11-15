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
        Schema::table('transactions', function (Blueprint $table): void {
            $table->string('delivery_option', 20)->default('pickup')->after('cancellation_reason');
        });

        Schema::table('transaction_items', function (Blueprint $table): void {
            $table->decimal('length', 8, 2)->nullable()->after('special_instructions');
            $table->decimal('width', 8, 2)->nullable()->after('length');
            $table->decimal('calculated_area', 10, 4)->nullable()->after('width');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropColumn('delivery_option');
        });

        Schema::table('transaction_items', function (Blueprint $table): void {
            $table->dropColumn(['length', 'width', 'calculated_area']);
        });
    }
};
