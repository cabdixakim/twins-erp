<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_trucks', function (Blueprint $table) {
            if (! Schema::hasColumn('import_trucks', 'duty_vendor_type')) {
                $table->string('duty_vendor_type', 30)->nullable()->after('delivery_notes');
            }
            if (! Schema::hasColumn('import_trucks', 'duty_vendor_id')) {
                $table->unsignedBigInteger('duty_vendor_id')->nullable()->after('duty_vendor_type');
            }
            if (! Schema::hasColumn('import_trucks', 'duty_rate_per_1000l')) {
                $table->decimal('duty_rate_per_1000l', 14, 4)->nullable()->after('duty_vendor_id');
            }
            if (! Schema::hasColumn('import_trucks', 'duty_qty')) {
                $table->decimal('duty_qty', 14, 3)->nullable()->after('duty_rate_per_1000l');
            }
            if (! Schema::hasColumn('import_trucks', 'duty_amount')) {
                $table->decimal('duty_amount', 14, 4)->nullable()->after('duty_qty');
            }
            if (! Schema::hasColumn('import_trucks', 'duty_currency')) {
                $table->string('duty_currency', 8)->nullable()->after('duty_amount');
            }
            if (! Schema::hasColumn('import_trucks', 'duty_notes')) {
                $table->text('duty_notes')->nullable()->after('duty_currency');
            }
            if (! Schema::hasColumn('import_trucks', 'duty_status')) {
                $table->string('duty_status', 20)->nullable()->after('duty_notes');
            }
            if (! Schema::hasColumn('import_trucks', 'duty_posted_at')) {
                $table->timestamp('duty_posted_at')->nullable()->after('duty_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('import_trucks', function (Blueprint $table) {
            $cols = ['duty_vendor_type','duty_vendor_id','duty_rate_per_1000l','duty_qty','duty_amount','duty_currency','duty_notes','duty_status','duty_posted_at'];
            $table->dropColumn(array_filter($cols, fn($c) => Schema::hasColumn('import_trucks', $c)));
        });
    }
};
