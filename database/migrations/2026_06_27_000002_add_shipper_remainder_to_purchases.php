<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('purchases', 'shipper_remainder_resolution')) {
                $table->enum('shipper_remainder_resolution', ['credit_note', 'carried_forward'])
                      ->nullable()->after('action_note');
            }
            if (! Schema::hasColumn('purchases', 'shipper_remainder_qty')) {
                $table->decimal('shipper_remainder_qty', 15, 4)->nullable()->after('shipper_remainder_resolution');
            }
            if (! Schema::hasColumn('purchases', 'shipper_remainder_note')) {
                $table->text('shipper_remainder_note')->nullable()->after('shipper_remainder_qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['shipper_remainder_resolution', 'shipper_remainder_qty', 'shipper_remainder_note']);
        });
    }
};
