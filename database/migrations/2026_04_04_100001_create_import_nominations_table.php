<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_nominations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transporter_id')->nullable()->constrained('transporters')->nullOnDelete();
            $table->string('currency', 8)->default('USD');
            $table->decimal('rate_per_1000l', 18, 4)->default(0);
            $table->decimal('allowed_loss_pct', 8, 4)->default(0.30);
            $table->decimal('short_charge_rate', 18, 4)->default(0);
            $table->string('short_charge_currency', 8)->default('USD');
            $table->decimal('advances', 18, 2)->default(0);
            $table->string('advances_currency', 8)->default('USD');
            $table->text('notes')->nullable();
            $table->string('status', 24)->default('active');
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'purchase_id']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_nominations');
    }
};
