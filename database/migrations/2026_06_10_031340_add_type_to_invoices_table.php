<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('type', 24)->default('invoice')->after('id');
            $table->unsignedBigInteger('credit_note_for')->nullable()->after('type');
            $table->foreign('credit_note_for')->references('id')->on('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['credit_note_for']);
            $table->dropColumn(['type', 'credit_note_for']);
        });
    }
};
