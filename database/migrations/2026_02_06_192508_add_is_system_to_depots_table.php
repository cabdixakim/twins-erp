<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('depots', function (Blueprint $table) {
            $table->boolean('is_system')
                ->default(false)
                ->after('is_active'); // adjust if your column is named differently
        });
    }

    public function down(): void
    {
        Schema::table('depots', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};