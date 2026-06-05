<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('type', 60);
            // nominations | offloads | purchases | opening_balances | payments | clients | etc.

            $table->string('ref_type', 80)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();

            $table->string('filename', 500);
            $table->string('status', 24)->default('pending');
            // pending | validating | validated | posting | posted | failed

            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('valid_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);

            $table->foreignId('posted_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();

            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'type', 'status']);
            $table->index(['ref_type', 'ref_id']);
        });

        Schema::create('import_job_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('import_jobs')->cascadeOnDelete();
            $table->unsignedInteger('row_number');

            $table->jsonb('raw_data');
            $table->jsonb('mapped_data')->nullable();

            $table->string('status', 24)->default('pending');
            // pending | valid | invalid | posted | skipped

            $table->jsonb('errors')->nullable();

            $table->string('result_type', 80)->nullable();
            $table->unsignedBigInteger('result_id')->nullable();

            $table->timestamps();

            $table->index(['job_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_job_rows');
        Schema::dropIfExists('import_jobs');
    }
};
