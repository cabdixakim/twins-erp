<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name', 120)->nullable(); // snapshot at log time

            // created | updated | deleted | posted | voided | paid | confirmed | received | cancelled | issued | adjusted
            $table->string('event', 60);

            $table->string('model_type', 120)->nullable();   // App\Models\Sale
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('model_label', 200)->nullable();  // "Sale SO-TWN-2026-00001"

            $table->text('description');
            $table->string('ip_address', 45)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
