<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();

            // Multi-company scope
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            /**
             * Movement types
             * - receipt: batch received into a depot (from import/offload OR local purchase in depot)
             * - transfer: depot -> depot
             * - adjustment: manual correction (audit required)
             * - issue: stock issued out (usually for sale)
             */
            $table->string('type', 24); // receipt|transfer|adjustment|issue

            // Optional references
            $table->string('ref_type', 40)->nullable();  // e.g. "offload", "sale", "opening_balance"
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('reference', 120)->nullable(); // invoice / doc / internal ref

            // Batch aware
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();

            // From/To depots
            $table->foreignId('from_depot_id')->nullable()->references('id')->on('depots')->nullOnDelete();
            $table->foreignId('to_depot_id')->nullable()->references('id')->on('depots')->nullOnDelete();

            // Quantity & cost
            $table->decimal('qty', 18, 3);
            $table->decimal('unit_cost', 18, 6)->default(0); // snapshot for audit
            $table->decimal('total_cost', 18, 2)->default(0);

            // Notes
            $table->text('notes')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'batch_id']);
            $table->index(['company_id', 'to_depot_id']);
            $table->index(['company_id', 'from_depot_id']);
            $table->index(['company_id', 'ref_type', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};