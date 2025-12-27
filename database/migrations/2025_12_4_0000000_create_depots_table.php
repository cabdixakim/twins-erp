<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depots', function (Blueprint $table) {
            $table->id();

            // ✅ ADDITION: company scope
            $table->foreignId('company_id')
                  ->constrained('companies')
                  ->cascadeOnDelete();

            $table->string('name');
            $table->string('city')->nullable();
            $table->decimal('storage_fee_per_1000_l', 12, 4)->default(0);
            $table->decimal('default_shrinkage_pct', 5, 4)->default(0.3000); // 0.3% default
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            // ✅ ADDITION: fast lookups per company
            $table->index(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depots');
    }
};