<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('trip_advance', 15, 4)->nullable()->after('freight_currency');
            $table->decimal('fuel_advance',  15, 4)->nullable()->after('trip_advance');
            $table->string('advance_currency', 8)->nullable()->after('fuel_advance');
            $table->unsignedBigInteger('advance_account_id')->nullable()->after('advance_currency');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['trip_advance','fuel_advance','advance_currency','advance_account_id']);
        });
    }
};
