<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_ledger_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();

            // invoice | payment | credit_note | adjustment
            $table->string('type', 32);

            // positive = AR (we are owed), negative = reduces AR (payment / credit)
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('currency', 8)->default('USD');

            $table->string('description', 500)->nullable();

            // Polymorphic reference (e.g. App\Models\Sale : sale_id)
            $table->string('ref_type', 120)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();

            $table->date('entry_date');
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'client_id', 'entry_date']);
            $table->index(['ref_type', 'ref_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_ledger_entries');
    }
};
