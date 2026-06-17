<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_nominations', function (Blueprint $table) {
            $table->string('default_duty_vendor_type', 30)->nullable()->after('notes');
            $table->unsignedBigInteger('default_duty_vendor_id')->nullable()->after('default_duty_vendor_type');
            $table->decimal('default_duty_rate_per_1000l', 14, 4)->nullable()->after('default_duty_vendor_id');
            $table->string('default_duty_currency', 8)->nullable()->after('default_duty_rate_per_1000l');
        });
    }

    public function down(): void
    {
        Schema::table('import_nominations', function (Blueprint $table) {
            $table->dropColumn([
                'default_duty_vendor_type',
                'default_duty_vendor_id',
                'default_duty_rate_per_1000l',
                'default_duty_currency',
            ]);
        });
    }
};
