<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS inv_movements_chart_index ON inventory_movements (company_id, type, ref_type, created_at)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS inv_movements_chart_index');
    }
};
