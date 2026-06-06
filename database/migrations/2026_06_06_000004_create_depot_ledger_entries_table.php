<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depot_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('depot_id')->constrained('depots')->cascadeOnDelete();

            // storage_charge | throughput_charge | loading_fee | other_charge | payment | adjustment
            $table->string('type', 40);

            // Positive = charge (we owe the depot); negative = payment (we paid the depot)
            $table->decimal('amount', 15, 4);
            $table->string('currency', 8)->default('USD');

            $table->string('description', 500)->nullable();
            $table->date('entry_date');

            $table->string('ref_type', 100)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();

            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'depot_id']);
            $table->index(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depot_ledger_entries');
    }
};
