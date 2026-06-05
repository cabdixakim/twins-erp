<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('client_name')
                  ->constrained('clients')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->after('client_id')
                  ->constrained('batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['batch_id']);
            $table->dropColumn(['client_id', 'batch_id']);
        });
    }
};
