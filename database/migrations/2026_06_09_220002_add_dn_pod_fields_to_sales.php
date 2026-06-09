<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Delivery note capture fields
            $table->string('driver_name', 150)->nullable()->after('delivery_notes');
            $table->text('seal_numbers')->nullable()->after('driver_name');
            $table->decimal('temperature', 5, 2)->nullable()->default(20.00)->after('seal_numbers');
            $table->decimal('density', 5, 3)->nullable()->default(0.820)->after('temperature');

            // POD / delivery confirmation
            $table->decimal('qty_delivered', 15, 4)->nullable()->after('density');
            $table->date('pod_received_at')->nullable()->after('qty_delivered');
            $table->text('pod_notes')->nullable()->after('pod_received_at');
            $table->unsignedBigInteger('pod_confirmed_by')->nullable()->after('pod_notes');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'driver_name', 'seal_numbers', 'temperature', 'density',
                'qty_delivered', 'pod_received_at', 'pod_notes', 'pod_confirmed_by',
            ]);
        });
    }
};
