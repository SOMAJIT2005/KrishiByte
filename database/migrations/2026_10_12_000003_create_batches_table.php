<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code', 30)->unique()->index();
            $table->foreignId('crop_id')->constrained('crops')->cascadeOnDelete();
            $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('origin_district_id')->constrained('districts')->cascadeOnDelete();
            $table->string('farm_location_name')->nullable();
            $table->decimal('origin_latitude', 10, 7)->nullable();
            $table->decimal('origin_longitude', 10, 7)->nullable();
            $table->date('harvest_date');
            $table->decimal('initial_quantity', 12, 2);
            $table->decimal('available_quantity', 12, 2);
            $table->string('unit', 20)->default('kg');
            $table->decimal('initial_farmer_price', 12, 2);
            $table->foreignId('current_custodian_id')->constrained('users');
            $table->string('current_stage', 40)->default('farm_gate')->index();
            $table->string('status', 30)->default('active')->index(); // active, flagged, completed, voided
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
