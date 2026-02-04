<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('depot_stocks', function (Blueprint $table) {
            $table->id();

            // Multi-company scope
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Where the stock is
            $table->foreignId('depot_id')->constrained()->cascadeOnDelete();

            // Product (for filtering/reporting even though batch also implies product)
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Batch-aware inventory (FIFO ready)
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();

            // Quantities
            $table->decimal('qty_on_hand', 18, 3)->default(0);
            $table->decimal('qty_reserved', 18, 3)->default(0);

            // Cost snapshot
            $table->decimal('unit_cost', 18, 6)->default(0);

            // Audit
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            // One row per depot + product + batch (or batch null for non-batch stock if you ever allow)
            $table->unique(['company_id', 'depot_id', 'product_id', 'batch_id']);

            $table->index(['company_id', 'depot_id']);
            $table->index(['company_id', 'product_id']);
            $table->index(['company_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depot_stocks');
    }
};