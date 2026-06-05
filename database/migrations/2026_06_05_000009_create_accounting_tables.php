<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Accounting module tables — created but INERT until companies.accounting_enabled = true.
 * All operational reports (stock, P&L, aging) must function without these tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('name', 200);
            $table->string('type', 24);
            // asset | liability | equity | revenue | expense
            $table->string('sub_type', 40)->nullable();
            // e.g. current_asset | fixed_asset | current_liability | etc.
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'type', 'is_active']);
        });

        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('type', 24);
            // general | purchase | sale | cash | bank
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'type']);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->foreignId('period_id')->nullable()->constrained('inventory_periods')->nullOnDelete();

            $table->string('reference', 80);
            $table->string('description', 500);
            $table->date('entry_date');

            $table->string('status', 24)->default('draft');
            // draft | posted | reversed

            $table->string('ref_type', 80)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();

            $table->foreignId('posted_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'journal_id', 'status']);
            $table->index(['company_id', 'entry_date']);
            $table->index(['ref_type', 'ref_id']);
        });

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->cascadeOnDelete();

            $table->string('description', 500)->nullable();
            $table->decimal('debit', 15, 4)->default(0);
            $table->decimal('credit', 15, 4)->default(0);

            $table->timestamps();

            $table->index(['company_id', 'entry_id']);
            $table->index(['company_id', 'account_id']);
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('account_number', 80)->nullable();
            $table->string('bank_name', 150)->nullable();
            $table->string('currency', 8)->default('USD');
            $table->decimal('opening_balance', 15, 4)->default(0);
            $table->foreignId('gl_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('chart_of_accounts');
    }
};
