<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['crops', 'batches', 'transactions', 'price_reports', 'price_observations'] as $name) {
            Schema::table($name, function (Blueprint $t) {
                $t->string('original_unit', 20)->nullable();
                $t->boolean('unit_needs_review')->default(false);
            });
            foreach (DB::table($name)->select('id', 'unit')->get() as $row) {
                $unit = match (strtolower(trim($row->unit))) {
                    'kg','kgs','kilogram','kilograms' => 'kg', 'mon','maund' => 'mon', 'sack','bag' => 'sack', 'piece','pieces','pc' => 'piece', default => null
                };
                DB::table($name)->where('id', $row->id)->update(['original_unit' => $row->unit, 'unit' => $unit ?? $row->unit, 'unit_needs_review' => $unit === null]);
            }
        }
        Schema::table('price_observations', fn (Blueprint $t) => $t->boolean('is_demo')->default(false));
        DB::table('price_observations')->where('notes', 'like', 'Illustrative demo%')->update(['is_demo' => true]);
        Schema::table('price_reports', function (Blueprint $t) {
            $t->string('evidence_original_sha256', 64)->nullable();
            $t->string('evidence_transformation')->nullable();
        });
        Schema::table('anomaly_flags', fn (Blueprint $t) => $t->json('rule_snapshot')->nullable());
        Schema::table('transactions', function (Blueprint $t) {
            $t->string('confirmation_status', 30)->default('legacy_unconfirmed');
            $t->foreignId('confirmed_by_user_id')->nullable()->constrained('users');
            $t->uuid('request_key')->nullable();
            $t->string('request_hash', 64)->nullable();
            $t->unique(['recorded_by_user_id', 'request_key']);
            $t->unsignedBigInteger('buyer_id')->nullable()->change();
        });
        Schema::create('handoff_proposals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('batch_id')->constrained('batches');
            $t->foreignId('proposed_by_user_id')->constrained('users');
            $t->foreignId('seller_id')->constrained('users');
            $t->foreignId('buyer_id')->constrained('users');
            $t->uuid('request_key');
            $t->string('request_hash', 64);
            $t->json('terms');
            $t->string('status', 20)->default('proposed')->index();
            $t->timestamp('expires_at');
            $t->timestamp('decided_at')->nullable();
            $t->text('decision_reason')->nullable();
            $t->foreignId('transaction_id')->nullable()->unique()->constrained('transactions');
            $t->timestamps();
            $t->unique(['proposed_by_user_id', 'request_key']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Preserve accepted history: restore the pre-upgrade backup to an isolated database before rollback.');
    }
};
