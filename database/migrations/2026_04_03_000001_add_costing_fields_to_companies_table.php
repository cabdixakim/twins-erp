<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('costing_method')->default('weighted_average')->after('base_currency');
            $table->boolean('inventory_posting_paused')->default(false)->after('costing_method');
            $table->timestamp('posting_paused_at')->nullable()->after('inventory_posting_paused');
            $table->unsignedBigInteger('posting_paused_by')->nullable()->after('posting_paused_at');
            $table->text('posting_paused_reason')->nullable()->after('posting_paused_by');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'costing_method',
                'inventory_posting_paused',
                'posting_paused_at',
                'posting_paused_by',
                'posting_paused_reason',
            ]);
        });
    }
};
