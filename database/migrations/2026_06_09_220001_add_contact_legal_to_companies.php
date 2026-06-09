<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->text('address')->nullable()->after('country');
            $table->string('phone', 50)->nullable()->after('address');
            $table->string('email', 150)->nullable()->after('phone');
            $table->string('website', 255)->nullable()->after('email');
            $table->string('rccm', 100)->nullable()->after('website');
            $table->string('id_nat', 100)->nullable()->after('rccm');
            $table->string('nif', 100)->nullable()->after('id_nat');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['address', 'phone', 'email', 'website', 'rccm', 'id_nat', 'nif']);
        });
    }
};
