<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depots', function (Blueprint $table) {
            $table->string('default_currency', 8)->default('USD')->after('city');
            $table->string('contact_person', 120)->nullable()->after('default_currency');
            $table->string('phone', 40)->nullable()->after('contact_person');
        });
    }

    public function down(): void
    {
        Schema::table('depots', function (Blueprint $table) {
            $table->dropColumn(['default_currency', 'contact_person', 'phone']);
        });
    }
};
