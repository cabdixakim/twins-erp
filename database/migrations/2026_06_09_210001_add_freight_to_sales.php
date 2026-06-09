<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('freight_amount', 12, 4)->nullable()->after('delivery_notes');
            $table->string('freight_currency', 8)->nullable()->after('freight_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['freight_amount', 'freight_currency']);
        });
    }
};
