<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->boolean('is_reconciled')->default(false)->after('void_reason');
            $table->timestamp('reconciled_at')->nullable()->after('is_reconciled');
            $table->unsignedBigInteger('reconciled_by')->nullable()->after('reconciled_at');
            $table->string('statement_ref', 100)->nullable()->after('reconciled_by');
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropColumn(['is_reconciled', 'reconciled_at', 'reconciled_by', 'statement_ref']);
        });
    }
};
