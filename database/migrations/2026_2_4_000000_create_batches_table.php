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

            /**
             * Batch identity
             * - Code is user-friendly reference (e.g. "BATCH-2026-0007")
             * - You can keep it nullable for now and auto-generate later.
             */
            $table->string('code', 64)->nullable();
            $table->string('name', 160)->nullable(); // optional label like "Tanzania Dec 2025"

            /**
             * Source + type
             * - local: bought already in a depot (ownership change, no shrinkage applied again)
             * - import: shipment that goes through nomination/loads/offloads/TR8 pipeline
             */
            $table->string('source_type', 24)->default('import'); // import|local
            $table->string('source_ref', 120)->nullable();        // invoice / supplier ref / etc

            // Optional: link supplier if relevant
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();

            /**
             * Product + quantities
             * - Use liters as your base unit for AGO
             */
            $table->decimal('qty_purchased', 18, 3)->default(0);   // original purchased qty
            $table->decimal('qty_received', 18, 3)->default(0);    // total received into depots (derived but stored for speed)
            $table->decimal('qty_remaining', 18, 3)->default(0);   // remaining not yet consumed (derived but stored for speed)

            /**
             * Landed cost
             * - total_cost: total landed cost for the batch (supplier + transport + depot fees etc)
             * - unit_cost: derived total_cost/qty_purchased (store for speed; recalc as needed)
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

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'source_type']);
            $table->unique(['company_id', 'code']); // ok even if code nullable (MySQL allows multiple nulls)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};