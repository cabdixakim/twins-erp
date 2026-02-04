<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();

            // Multi-company scope
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Product (ONE product per batch)
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            /**
             * Batch identity
             */
            $table->string('code', 64)->nullable();   // e.g. BATCH-2026-0007
            $table->string('name', 160)->nullable();  // optional label

            /**
             * Source + type
             * - local_depot: bought already in a depot (ownership change, shrinkage NOT applied again)
             * - import: shipment pipeline (nominations/loads/offloads/TR8 later)
             */
            $table->string('source_type', 24)->default('import'); // import|local_depot
            $table->string('source_ref', 120)->nullable();        // invoice / supplier ref / etc

            // Optional supplier
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();

            /**
             * Quantities (base_uom)
             */
            $table->decimal('qty_purchased', 18, 3)->default(0);
            $table->decimal('qty_received', 18, 3)->default(0);
            $table->decimal('qty_remaining', 18, 3)->default(0);

            /**
             * Landed cost
             */
            $table->decimal('total_cost', 18, 2)->default(0);
            $table->decimal('unit_cost', 18, 6)->default(0);

            /**
             * Status
             */
            $table->string('status', 24)->default('draft'); // draft|active|closed|cancelled
            $table->timestamp('purchased_at')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'source_type']);
            $table->index(['company_id', 'product_id']);
            $table->unique(['company_id', 'code']); // multiple NULL ok in MySQL
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};