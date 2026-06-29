<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nomination_advances')) {
            return;
        }

        Schema::create('nomination_advances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('nomination_id');
            $table->unsignedBigInteger('transporter_id');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 8)->default('USD');
            $table->date('advance_date');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->timestamps();

            $table->index(['nomination_id', 'voided_at']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomination_advances');
    }
};
