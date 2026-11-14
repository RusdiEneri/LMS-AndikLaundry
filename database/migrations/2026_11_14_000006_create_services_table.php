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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('service_categories')->nullOnDelete();
            $table->string('code', 20)->unique('uq_services_code');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('unit', 10)->default('kg');
            $table->decimal('base_price', 12, 2);
            $table->decimal('min_weight', 8, 2)->default(1.00);
            $table->unsignedInteger('estimated_duration_hours')->default(48);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['unit', 'is_active'], 'idx_services_unit_active');
        });

        Schema::create('service_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('tier_name', 50);
            $table->string('tier_code', 20);
            $table->decimal('multiplier_factor', 4, 2)->default(1.00);
            $table->decimal('flat_surcharge', 12, 2)->default(0.00);
            $table->unsignedInteger('duration_hours');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['service_id', 'tier_code'], 'uq_service_tier_code');
            $table->index(['service_id', 'is_active'], 'idx_service_tiers_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_tiers');
        Schema::dropIfExists('services');
    }
};
