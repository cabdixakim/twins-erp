<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('import_trucks', function (Blueprint $table) {
            if (! Schema::hasColumn('import_trucks', 'border_post')) {
                $table->string('border_post', 120)->nullable()->after('border_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('import_trucks', function (Blueprint $table) {
            if (Schema::hasColumn('import_trucks', 'border_post')) {
                $table->dropColumn('border_post');
            }
        });
    }
};
