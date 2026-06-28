<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_adjustments')) {
            return;
        }

        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('period_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('depot_id');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('inventory_movement_id');
            $table->enum('reason_type', [
                'depot_shrinkage',
                'write_off',
                'meter_variance',
                'stock_count_correction',
                'transit_loss',
            ]);
            $table->decimal('qty', 15, 4);
            $table->decimal('unit_cost', 15, 6)->default(0);
            $table->decimal('total_value', 15, 4)->default(0);
            $table->string('ref_type', 100)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('period_id')->references('id')->on('inventory_periods');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('depot_id')->references('id')->on('depots');
            $table->foreign('inventory_movement_id')->references('id')->on('inventory_movements')->cascadeOnDelete();

            $table->index(['company_id', 'period_id']);
            $table->index(['company_id', 'depot_id', 'product_id']);
            $table->index(['ref_type', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
