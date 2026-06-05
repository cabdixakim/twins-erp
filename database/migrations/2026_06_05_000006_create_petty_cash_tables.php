<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('currency', 8)->default('USD');
            $table->decimal('opening_balance', 15, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });

        Schema::create('petty_cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('petty_cash_accounts')->cascadeOnDelete();

            $table->string('type', 40);
            // bank_transfer_in | transporter_advance | driver_advance | operational_expense | recovery | adjustment

            $table->string('ref_type', 80)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();

            $table->decimal('amount', 15, 4);
            // positive = inflow, negative = outflow

            $table->string('currency', 8)->default('USD');
            $table->string('description', 500);
            $table->string('receipt_path', 500)->nullable();
            $table->date('transaction_date');

            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'account_id']);
            $table->index(['company_id', 'type']);
            $table->index(['ref_type', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_cash_transactions');
        Schema::dropIfExists('petty_cash_accounts');
    }
};
