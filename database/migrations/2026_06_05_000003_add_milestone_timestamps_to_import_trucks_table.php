<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_trucks', function (Blueprint $table) {
            $table->timestamp('failed_at')->nullable()->after('load_notes');
            $table->string('failure_reason', 500)->nullable()->after('failed_at');
            $table->timestamp('in_transit_at')->nullable()->after('failure_reason');
            $table->timestamp('border_cleared_at')->nullable()->after('in_transit_at');
        });
    }

    public function down(): void
    {
        Schema::table('import_trucks', function (Blueprint $table) {
            $table->dropColumn(['failed_at', 'failure_reason', 'in_transit_at', 'border_cleared_at']);
        });
    }
};
