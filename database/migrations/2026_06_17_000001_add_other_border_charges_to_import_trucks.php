<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_trucks', function (Blueprint $table) {
            if (! Schema::hasColumn('import_trucks', 'other_border_charges')) {
                $table->decimal('other_border_charges', 14, 4)->nullable()->after('duty_notes');
            }
            if (! Schema::hasColumn('import_trucks', 'other_border_currency')) {
                $table->string('other_border_currency', 8)->nullable()->after('other_border_charges');
            }
            if (! Schema::hasColumn('import_trucks', 'other_border_notes')) {
                $table->string('other_border_notes', 500)->nullable()->after('other_border_currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('import_trucks', function (Blueprint $table) {
            $table->dropColumn(array_filter(
                ['other_border_charges', 'other_border_currency', 'other_border_notes'],
                fn($col) => Schema::hasColumn('import_trucks', $col)
            ));
        });
    }
};
