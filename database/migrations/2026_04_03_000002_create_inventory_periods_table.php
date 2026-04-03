<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('costing_method');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->default('open');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_periods');
    }
};
