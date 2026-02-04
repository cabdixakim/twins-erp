<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_consumptions', function (Blueprint $table) {
            $table->id();

            // Multi-company scope
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Product
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            /**
             * Consumption reason
             */
            $table->string('type', 24)->default('sale'); // sale|loss|internal

            // Where consumed from
            $table->foreignId('depot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();

            // Optional link to movement for traceability
            $table->foreignId('inventory_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();

            // Optional reference (sale invoice, etc)
            $table->string('ref_type', 40)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('reference', 120)->nullable();

            // Quantity & cost snapshot
            $table->decimal('qty', 18, 3);
            $table->decimal('unit_cost', 18, 6)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);

            $table->text('notes')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'product_id']);
            $table->index(['company_id', 'depot_id']);
            $table->index(['company_id', 'batch_id']);
            $table->index(['company_id', 'ref_type', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_consumptions');
    }
};