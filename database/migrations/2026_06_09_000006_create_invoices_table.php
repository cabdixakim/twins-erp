<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();

            $table->string('invoice_number', 60)->unique();
            $table->unsignedBigInteger('sequence_no')->default(1);

            // draft | sent | paid | void | overdue
            $table->string('status', 20)->default('sent');

            $table->string('currency', 8)->default('USD');
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->decimal('paid_amount', 18, 2)->default(0);

            $table->date('issued_date');
            $table->date('due_date');

            $table->text('notes')->nullable();
            $table->text('footer_text')->nullable();
            $table->text('bank_details')->nullable();
            $table->string('payment_terms', 100)->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['company_id', 'client_id', 'status']);
            $table->index(['company_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
