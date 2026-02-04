<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Multi-company scope
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Identity
            $table->string('name', 120);          // e.g. AGO, PMS, Jet A-1
            $table->string('code', 32)->nullable(); // optional short code e.g. AGO/PMS
            $table->string('category', 32)->nullable(); // fuel, lubricant, etc

            // Units
            $table->string('base_uom', 16)->default('L'); // L, KG, etc (for you: L)

            // Status
            $table->boolean('is_active')->default(true);

            // Audit
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['company_id', 'is_active']);
            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};