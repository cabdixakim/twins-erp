<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_nominations', function (Blueprint $table) {
            $table->dropColumn(['hospitality_rate', 'hospitality_currency']);
        });
    }

    public function down(): void
    {
        Schema::table('import_nominations', function (Blueprint $table) {
            $table->decimal('hospitality_rate', 12, 4)->default(0)->after('short_charge_currency');
            $table->string('hospitality_currency', 8)->default('USD')->after('hospitality_rate');
        });
    }
};
