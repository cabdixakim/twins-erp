<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('import_trucks', function (Blueprint $table) {
            if (! Schema::hasColumn('import_trucks', 'arrived_at_border_at')) {
                $table->timestamp('arrived_at_border_at')->nullable()->after('border_post');
            }
        });
    }

    public function down(): void
    {
        Schema::table('import_trucks', function (Blueprint $table) {
            if (Schema::hasColumn('import_trucks', 'arrived_at_border_at')) {
                $table->dropColumn('arrived_at_border_at');
            }
        });
    }
};
