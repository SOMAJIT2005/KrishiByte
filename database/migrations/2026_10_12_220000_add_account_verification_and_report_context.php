<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verification_date')->nullable();
            $table->text('verification_reason')->nullable();
        });
        Schema::table('price_reports', function (Blueprint $table) {
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->string('category', 40)->default('price');
        });
        Schema::table('price_observations', function (Blueprint $table) {
            $table->foreignId('price_report_id')->nullable()->unique()->constrained('price_reports')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('price_observations', fn (Blueprint $t) => $t->dropConstrainedForeignId('price_report_id'));
        Schema::table('price_reports', function (Blueprint $t) {
            $t->dropConstrainedForeignId('reporter_user_id');
            $t->dropConstrainedForeignId('batch_id');
            $t->dropColumn('category');
        });
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('verified_by_user_id');
            $t->dropColumn(['is_verified', 'verification_date', 'verification_reason']);
        });
    }
};
