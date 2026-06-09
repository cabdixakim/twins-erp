<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('severity', 20)->default('info')->after('event'); // info | warning | critical
            $table->string('url', 500)->nullable()->after('ip_address');
            $table->string('method', 10)->nullable()->after('url');
            $table->text('user_agent')->nullable()->after('method');
            $table->jsonb('before_data')->nullable()->after('user_agent');
            $table->jsonb('after_data')->nullable()->after('before_data');
            $table->string('module', 60)->nullable()->after('model_label'); // Purchase | Sale | Invoice | Client

            $table->index('severity');
            $table->index('module');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['severity']);
            $table->dropIndex(['module']);
            $table->dropColumn(['severity', 'url', 'method', 'user_agent', 'before_data', 'after_data', 'module']);
        });
    }
};
