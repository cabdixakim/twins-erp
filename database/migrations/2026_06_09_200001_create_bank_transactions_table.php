<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('bank_account_id');
            $table->string('type', 24); // deposit | withdrawal | transfer_in | transfer_out
            $table->decimal('amount', 15, 4);
            $table->string('currency', 8)->default('USD');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->string('reference', 80)->nullable();
            $table->string('description', 500)->nullable();
            $table->date('entry_date');
            $table->unsignedBigInteger('transfer_account_id')->nullable();
            $table->unsignedBigInteger('transfer_transaction_id')->nullable();
            $table->string('ref_type', 100)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->string('void_reason', 300)->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->cascadeOnDelete();

            $table->index(['company_id', 'bank_account_id', 'entry_date']);
            $table->index(['ref_type', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
