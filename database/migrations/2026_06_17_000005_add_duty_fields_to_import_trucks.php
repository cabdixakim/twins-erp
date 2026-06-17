<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_trucks', function (Blueprint $table) {
            $table->string('duty_vendor_type', 30)->nullable()->after('delivery_notes');
            $table->unsignedBigInteger('duty_vendor_id')->nullable()->after('duty_vendor_type');
            $table->decimal('duty_rate_per_1000l', 14, 4)->nullable()->after('duty_vendor_id');
            $table->decimal('duty_qty', 14, 3)->nullable()->after('duty_rate_per_1000l');
            $table->decimal('duty_amount', 14, 4)->nullable()->after('duty_qty');
            $table->string('duty_currency', 8)->nullable()->after('duty_amount');
            $table->text('duty_notes')->nullable()->after('duty_currency');
            $table->string('duty_status', 20)->nullable()->after('duty_notes'); // pending|posted|waived
            $table->timestamp('duty_posted_at')->nullable()->after('duty_status');
        });
    }

    public function down(): void
    {
        Schema::table('import_trucks', function (Blueprint $table) {
            $table->dropColumn([
                'duty_vendor_type',
                'duty_vendor_id',
                'duty_rate_per_1000l',
                'duty_qty',
                'duty_amount',
                'duty_currency',
                'duty_notes',
                'duty_status',
                'duty_posted_at',
            ]);
        });
    }
};
