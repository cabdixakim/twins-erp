<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_trucks', function (Blueprint $table) {
            $table->unique(['nomination_id', 'truck_reg'], 'import_trucks_nomination_truck_reg_unique');
        });
    }

    public function down(): void
    {
        Schema::table('import_trucks', function (Blueprint $table) {
            $table->dropUnique('import_trucks_nomination_truck_reg_unique');
        });
    }
};
