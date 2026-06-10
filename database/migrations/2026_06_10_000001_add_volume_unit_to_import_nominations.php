<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_nominations', function (Blueprint $table) {
            $table->string('volume_unit', 3)->default('L')->after('notes');
        });

        // Backfill existing nominations with their company's current unit
        DB::statement("
            UPDATE import_nominations n
            SET volume_unit = COALESCE(
                (SELECT c.volume_unit FROM companies c WHERE c.id = n.company_id LIMIT 1),
                'L'
            )
        ");
    }

    public function down(): void
    {
        Schema::table('import_nominations', function (Blueprint $table) {
            $table->dropColumn('volume_unit');
        });
    }
};
