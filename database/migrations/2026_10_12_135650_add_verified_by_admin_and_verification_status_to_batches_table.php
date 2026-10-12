<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->unsignedBigInteger('verified_by_admin_id')->nullable()->index();
            $table->timestamp('verified_at')->nullable();
            $table->enum('verification_status', ['unverified', 'verified', 'rejected'])->default('unverified')->index();
            $table->text('admin_verification_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn([
                'verified_by_admin_id',
                'verified_at',
                'verification_status',
                'admin_verification_notes',
            ]);
        });
    }
};
