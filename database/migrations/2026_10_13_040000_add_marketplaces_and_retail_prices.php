<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_demo')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->unique(['district_id', 'name']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('marketplace_id')->nullable()->constrained();
            $table->string('shop_name', 150)->nullable();
        });
        Schema::create('retail_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained();
            $table->foreignId('retailer_id')->constrained('users');
            $table->foreignId('marketplace_id')->constrained();
            $table->decimal('price_per_unit', 10, 2);
            $table->string('unit', 20);
            $table->uuid('request_key');
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
            $table->unique(['retailer_id', 'request_key']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Preserve marketplace and price history; restore a verified backup.');
    }
};
