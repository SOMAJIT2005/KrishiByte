<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_reports', fn (Blueprint $table) => $table->decimal('reported_price', 12, 2)->nullable()->change());
    }

    public function down(): void
    {
        // Keep nullable prices on rollback to preserve quantity/quality reports without inventing a price.
    }
};
