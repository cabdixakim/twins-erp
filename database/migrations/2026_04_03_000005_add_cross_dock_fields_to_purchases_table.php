<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->timestamp('actioned_at')->nullable()->after('status');
            $table->unsignedBigInteger('actioned_by')->nullable()->after('actioned_at');
            $table->string('action_note', 500)->nullable()->after('actioned_by');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['actioned_at', 'actioned_by', 'action_note']);
        });
    }
};
