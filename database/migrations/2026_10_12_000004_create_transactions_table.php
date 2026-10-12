<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->unsignedInteger('sequence_no');
            $table->foreignId('seller_id')->constrained('users');
            $table->foreignId('buyer_id')->constrained('users');
            $table->string('stage', 50)->index(); // farmer_to_collector, collector_to_wholesale, wholesale_to_retail, retail_sale, correction
            $table->decimal('quantity', 12, 2);
            $table->string('unit', 20)->default('kg');
            $table->decimal('price_per_unit', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('previous_price_per_unit', 12, 2)->nullable();
            $table->decimal('gross_price_spread', 12, 2)->nullable();
            $table->decimal('markup_percent', 8, 2)->nullable();
            $table->decimal('gross_margin_percent', 8, 2)->nullable();
            $table->dateTime('transaction_date');
            $table->foreignId('district_id')->constrained('districts');
            $table->string('market_name')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('recorded_by_user_id')->constrained('users');
            $table->string('validation_status', 30)->default('valid')->index(); // valid, flagged, voided
            $table->boolean('is_voided')->default(false)->index();
            $table->text('void_reason')->nullable();
            $table->foreignId('voided_by_user_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['batch_id', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
