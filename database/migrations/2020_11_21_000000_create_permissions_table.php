<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();

            // your seeder inserts these:
            $table->string('slug')->unique();                 // e.g. depots.view
            $table->string('name');                           // e.g. View depots
            $table->string('group', 80)->nullable()->index(); // e.g. Settings
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};