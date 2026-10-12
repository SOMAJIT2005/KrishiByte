<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_id')->constrained('crops')->cascadeOnDelete();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->string('market_name')->nullable();
            $table->string('unit', 20)->default('kg');
            $table->decimal('observed_retail_price', 12, 2);
            $table->date('observation_date')->index();
            $table->string('source', 50)->default('verified_market_survey');
            $table->string('verification_status', 30)->default('verified')->index(); // pending, verified, rejected
            $table->foreignId('linked_batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('entered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_observations');
    }
};
