<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            // Scope
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Where stock leaves from
            $table->foreignId('depot_id')->constrained()->cascadeOnDelete();

            // Single-product sale (fast now, upgrade to lines later)
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Client (until Customers module exists)
            $table->string('client_name')->nullable();

            // Reference sequencing (like purchases)
            $table->unsignedBigInteger('sequence_no')->default(0);
            $table->string('reference', 64);

            // Commercials
            $table->date('sale_date')->nullable();
            $table->decimal('qty', 18, 3)->default(0);
            $table->decimal('unit_price', 18, 6)->default(0);
            $table->string('currency', 8)->default('USD');

            // Posting outputs (filled when posted)
            $table->decimal('total', 18, 2)->default(0);
            $table->decimal('cogs_total', 18, 2)->default(0); // FIFO cost total
            $table->decimal('gross_profit', 18, 2)->default(0);

            // Status
            $table->string('status', 16)->default('draft'); // draft|posted|cancelled

            // Delivery context (truck/trailer live here for now)
            $table->string('delivery_mode', 16)->default('ex_depot'); // ex_depot|delivered
            $table->foreignId('transporter_id')->nullable()->constrained('transporters')->nullOnDelete();
            $table->string('truck_no', 32)->nullable();
            $table->string('trailer_no', 32)->nullable();
            $table->string('waybill_no', 64)->nullable();
            $table->text('delivery_notes')->nullable();

            // Link to movement once posted (optional but nice)
            $table->foreignId('inventory_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();

            // Posting audit
            $table->foreignId('posted_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'reference']);
            $table->index(['company_id', 'depot_id', 'status']);
            $table->index(['company_id', 'product_id']);
            $table->index(['company_id', 'sale_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};