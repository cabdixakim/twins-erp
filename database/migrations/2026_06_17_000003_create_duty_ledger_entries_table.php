<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('duty_ledger_entries')) {
            return;
        }
        Schema::create('duty_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('duty_vendor_id');
            $table->string('type', 30);
            $table->decimal('amount', 14, 4);
            $table->string('currency', 8)->default('USD');
            $table->string('description', 500)->nullable();
            $table->date('entry_date');
            $table->string('ref_type', 150)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'duty_vendor_id', 'entry_date']);
            $table->index(['ref_type', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_ledger_entries');
    }
};
