<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anomaly_flags', function (Blueprint $table) {
            $table->decimal('measured_value', 16, 2)->change();
            $table->decimal('threshold_value', 16, 2)->change();
        });
        if (! Schema::hasTable('farmer_access_requests')) {
            Schema::create('farmer_access_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('farmer_id')->constrained('users');
                $table->string('name', 150);
                $table->string('phone', 20);
                $table->text('reason');
                $table->string('status')->default('pending');
                $table->char('token_hash', 64)->nullable()->unique();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('redeemed_at')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users');
                $table->text('verification_method')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Preserve private access history; restore a verified backup instead.');
    }
};
