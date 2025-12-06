<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transporters', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('type', 20)->nullable(); // 'intl', 'local', or null
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();

            $table->string('contact_person', 150)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();

            $table->string('default_currency', 3)->default('USD');
            $table->decimal('default_rate_per_1000_l', 12, 4)->nullable(); // freight rate baseline

            $table->string('payment_terms', 100)->nullable(); // e.g. "30 days", "per trip"

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transporters');
    }
};