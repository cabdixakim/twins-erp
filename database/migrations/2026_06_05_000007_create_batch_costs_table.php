<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
            $table->foreignId('nomination_id')->nullable()->constrained('import_nominations')->nullOnDelete();

            $table->string('category', 40);
            // freight | duty | border_charge | hospitality | storage | penalty | other

            $table->string('description', 500);
            $table->decimal('amount', 15, 4);
            $table->string('currency', 8)->default('USD');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->decimal('amount_base', 15, 4)->default(0);
            // amount × exchange_rate in company base currency

            $table->boolean('is_included_in_cost')->default(false);
            // when true, this cost is rolled into the batch unit_cost

            $table->date('entry_date');

            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'batch_id']);
            $table->index(['company_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_costs');
    }
};
