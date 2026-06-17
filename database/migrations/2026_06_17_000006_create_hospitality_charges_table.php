<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospitality_charges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('purchase_id');
            $table->string('paid_to_type', 20); // supplier | petty_cash
            $table->unsignedBigInteger('paid_to_id')->nullable();
            $table->string('paid_to_name', 200)->nullable();
            $table->decimal('amount', 14, 4);
            $table->string('currency', 8)->default('USD');
            $table->decimal('exchange_rate', 14, 6)->default(1);
            $table->decimal('amount_base', 14, 4)->nullable();
            $table->date('entry_date');
            $table->string('description', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'purchase_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitality_charges');
    }
};
