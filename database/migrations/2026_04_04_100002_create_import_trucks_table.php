<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_trucks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nomination_id')->constrained('import_nominations')->cascadeOnDelete();

            // Truck identity
            $table->string('truck_reg', 40)->nullable();
            $table->string('trailer_reg', 40)->nullable();
            $table->string('driver_name', 150)->nullable();
            $table->string('driver_passport', 60)->nullable();
            $table->string('driver_license', 60)->nullable();
            $table->string('driver_phone', 30)->nullable();
            $table->decimal('capacity', 15, 3)->default(0); // expected litres

            // Status lifecycle
            $table->string('status', 30)->default('nominated');
            // nominated | loading_failed | loaded | in_transit | border_cleared | delivered

            // Loading
            $table->decimal('qty_loaded', 15, 3)->nullable();
            $table->date('pickup_date')->nullable();
            $table->string('pickup_terminal', 200)->nullable();
            $table->text('load_notes')->nullable();

            // Border clearance
            $table->string('tr8_number', 80)->nullable();
            $table->string('t1_number', 80)->nullable();
            $table->date('border_date')->nullable();

            // Delivery
            $table->foreignId('depot_id')->nullable()->constrained('depots')->nullOnDelete();
            $table->decimal('qty_delivered', 15, 3)->nullable();
            $table->date('delivery_date')->nullable();
            $table->text('delivery_notes')->nullable();

            // Shortfall (computed at delivery)
            $table->decimal('shortfall_qty', 15, 3)->nullable();      // total loss
            $table->decimal('allowed_loss_qty', 15, 3)->nullable();    // allowed loss
            $table->decimal('excess_loss_qty', 15, 3)->nullable();     // chargeable shortfall
            $table->decimal('shortfall_charge', 18, 2)->nullable();    // amount charged

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'nomination_id']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_trucks');
    }
};
