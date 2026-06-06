<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('transporter_id')->nullable()->after('supplier_id')
                  ->constrained('transporters')->nullOnDelete();
            $table->decimal('freight_amount', 14, 2)->nullable()->after('transporter_id');
            $table->string('freight_currency', 8)->nullable()->after('freight_amount');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['transporter_id']);
            $table->dropColumn(['transporter_id', 'freight_amount', 'freight_currency']);
        });
    }
};
