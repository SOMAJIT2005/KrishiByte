<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anomaly_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('markup_rules')->nullOnDelete();
            $table->string('reason_code', 50)->index();
            $table->decimal('measured_value', 12, 2);
            $table->decimal('threshold_value', 12, 2);
            $table->string('severity', 20)->default('medium')->index(); // low, medium, high
            $table->string('status', 30)->default('new')->index(); // new, reviewing, cleared, escalated
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reviewer_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anomaly_flags');
    }
};
