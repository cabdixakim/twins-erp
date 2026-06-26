<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'valid_from')) {
                $table->date('valid_from')->nullable()->after('category');
            }
            if (! Schema::hasColumn('documents', 'valid_until')) {
                $table->date('valid_until')->nullable()->after('valid_from');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $cols = array_filter(['valid_from', 'valid_until'], fn($c) => Schema::hasColumn('documents', $c));
            if ($cols) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
