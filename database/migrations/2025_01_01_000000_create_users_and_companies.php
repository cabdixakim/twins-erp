<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * IMPORTANT:
         * We create companies FIRST so users.active_company_id can be a proper FK.
         * This is still "additive" to your intent — we are not removing columns,
         * just reordering creation to support multi-company.
         */

        Schema::create('companies', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('logo_path')->nullable();
            $t->string('base_currency')->default('USD');
            $t->string('country')->nullable();
            $t->string('timezone')->default('Africa/Lubumbashi');
            $t->timestamps();
        });

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');

            // ✅ ADDITION: active company pointer (nullable)
            $t->foreignId('active_company_id')
              ->nullable()
              ->constrained('companies')
              ->nullOnDelete();

            $t->rememberToken();
            $t->timestamps();
        });

        // ✅ ADDITION: user belongs to many companies
        Schema::create('company_user', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->timestamps();

            $t->unique(['company_id', 'user_id']);
            $t->index(['user_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('companies');
    }
};