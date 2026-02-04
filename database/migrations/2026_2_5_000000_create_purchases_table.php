<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();

            // Multi-company scope
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Wizard type
            $table->string('type', 24)->default('import'); // import|local_depot

            // Supplier optional
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();

            // Product
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Batch created on confirm
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();

            // Core purchase fields
            $table->date('purchase_date')->nullable();
            $table->decimal('qty', 18, 3)->default(0);          // in product base_uom
            $table->decimal('unit_price', 18, 6)->default(0);
            $table->string('currency', 8)->default('USD');

            // Status pipeline
            $table->string('status', 24)->default('draft'); // draft|confirmed|cancelled

            $table->text('notes')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'product_id']);

            // one purchase -> one batch (nullable unique is ok)
            $table->unique(['company_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};