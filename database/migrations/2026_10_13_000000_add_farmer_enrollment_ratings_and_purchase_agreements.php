<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('farmer_profile_public')->default(false);
            $table->json('grown_crop_ids')->nullable();
            $table->text('cultivation_notes')->nullable();
        });
        // Real legacy profiles stay private until consent; demo publication is explicit.
        DB::table('users')->where('role', 'farmer')->where('email', 'farmer.rahim@krishibyte.local')->update(['farmer_profile_public' => true]);
        Schema::create('farmer_enrollments', function (Blueprint $table) {
            $table->id();
            $table->uuid('tracking_code')->unique();
            $table->string('name', 150);
            $table->string('phone', 20);
            $table->foreignId('district_id')->constrained();
            $table->json('grown_crop_ids');
            $table->text('cultivation_notes')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('farmer_id')->nullable()->constrained('users');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users');
            $table->text('review_reason')->nullable();
            $table->timestamps();
        });
        Schema::create('farmer_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('users');
            $table->foreignId('reviewer_id')->constrained('users');
            $table->unsignedTinyInteger('score');
            $table->text('comment');
            $table->string('reviewer_group');
            $table->string('status')->default('pending');
            $table->foreignId('moderated_by_user_id')->nullable()->constrained('users');
            $table->text('moderation_reason')->nullable();
            $table->timestamps();
            $table->unique(['farmer_id', 'reviewer_id']);
        });
        Schema::create('farm_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collector_id')->constrained('users');
            $table->foreignId('farmer_id')->constrained('users');
            $table->foreignId('batch_id')->constrained();
            $table->foreignId('transaction_id')->nullable()->unique()->constrained('transactions');
            $table->uuid('request_key');
            $table->char('request_hash', 64);
            $table->json('terms');
            $table->string('witness_name', 150);
            $table->string('evidence_path');
            $table->string('evidence_mime');
            $table->char('evidence_original_sha256', 64);
            $table->text('evidence_transformation');
            $table->timestamps();
            $table->unique(['collector_id', 'request_key']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Preserve enrollment, rating, and contract history; restore a reviewed backup instead.');
    }
};
