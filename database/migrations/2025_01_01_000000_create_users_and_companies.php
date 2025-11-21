<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->rememberToken();
            $t->timestamps();
        });
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
    }
    public function down(): void {
        Schema::dropIfExists('companies');
        Schema::dropIfExists('users');
    }
};
