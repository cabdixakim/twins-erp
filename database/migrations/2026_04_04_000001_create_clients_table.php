<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                  ->constrained('companies')
                  ->cascadeOnDelete();

            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->string('type', 50)->nullable(); // e.g. 'government', 'private', 'retail'

            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();

            $table->string('contact_person', 150)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();

            $table->string('currency', 3)->default('USD');
            $table->decimal('credit_limit', 18, 2)->default(0);
            $table->boolean('is_active')->default(true);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'name']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
