<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transporter_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transporter_id')->constrained('transporters')->cascadeOnDelete();

            $table->string('type', 32);
            // advance | recovery | payment | short_charge | freight_charge

            $table->string('ref_type', 80)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();

            $table->decimal('amount', 15, 4);
            // positive = debit (money owed to transporter), negative = credit (recovered / paid)

            $table->string('currency', 8)->default('USD');
            $table->string('description', 500);
            $table->date('entry_date');

            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'transporter_id']);
            $table->index(['company_id', 'type']);
            $table->index(['ref_type', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transporter_ledger_entries');
    }
};
