<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('invoice_accent_color', 7)->default('#10b981')->after('volume_unit');
            $table->unsignedTinyInteger('invoice_payment_days')->default(30)->after('invoice_accent_color');
            $table->string('invoice_prefix', 10)->default('INV')->after('invoice_payment_days');
            $table->decimal('invoice_tax_rate', 5, 2)->default(0)->after('invoice_prefix');
            $table->text('invoice_footer_notes')->nullable()->after('invoice_tax_rate');
            $table->text('invoice_bank_details')->nullable()->after('invoice_footer_notes');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_accent_color', 'invoice_payment_days', 'invoice_prefix',
                'invoice_tax_rate', 'invoice_footer_notes', 'invoice_bank_details',
            ]);
        });
    }
};
