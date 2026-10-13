<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('markup_percent', 16, 2)->nullable()->change();
            $table->decimal('gross_margin_percent', 16, 2)->nullable()->change();
        });
        Schema::table('anomaly_flags', function (Blueprint $table) {
            $table->decimal('measured_value', 16, 2)->change();
            $table->decimal('threshold_value', 16, 2)->change();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('status_version')->default(0);
            $table->boolean('trading_approved')->default(false);
            $table->timestamp('publication_consented_at')->nullable();
            $table->timestamp('publication_withdrawn_at')->nullable();
            $table->foreignId('consent_recorded_by')->nullable()->constrained('users');
            $table->string('consent_method')->nullable();
        });
        // Existing active traders keep capability during rollout; new accounts require review.
        DB::table('users')->whereIn('role', ['collector', 'wholesale', 'retail'])->where('account_status', 'active')->update(['trading_approved' => true]);
        // Enrollment approval recorded consent to a public profile; synthetic demo identities are explicit.
        $consented = DB::table('farmer_enrollments')->where('status', 'approved')->whereNotNull('farmer_id')->pluck('farmer_id');
        DB::table('users')->where('role', 'farmer')->whereNotIn('id', $consented)->where('email', '!=', 'farmer.rahim@krishibyte.local')->update(['farmer_profile_public' => false]);
        DB::table('users')->where('role', 'farmer')->where('farmer_profile_public', true)->update(['publication_consented_at' => now(), 'consent_method' => 'Historical enrollment consent or explicit synthetic demo profile']);
        Schema::table('farmer_enrollments', function (Blueprint $table) {
            $table->timestamp('consented_at')->nullable();
        });
        Schema::create('correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('counterparty_id')->constrained('users');
            $table->uuid('request_key');
            $table->string('status')->default('pending');
            $table->text('reason');
            $table->text('decision_reason')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['requested_by', 'request_key']);
        });
        Schema::create('review_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('district_id')->constrained();
            $table->foreignId('assigned_by')->constrained('users');
            $table->text('reason');
            $table->timestamps();
            $table->unique(['user_id', 'district_id']);
        });
        // Preserve the documented national local pilot for existing reviewers explicitly.
        foreach (DB::table('users')->whereIn('role', ['admin', 'authority'])->where('account_status', 'active')->get() as $reviewer) {
            foreach (DB::table('districts')->pluck('id') as $district) {
                DB::table('review_assignments')->insert(['user_id' => $reviewer->id, 'district_id' => $district, 'assigned_by' => $reviewer->id, 'reason' => 'Existing national pilot assignment; narrow before institutional rollout.', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        // Resolve historical cleared flags without erasing their trigger evidence.
        DB::table('transactions')->where('validation_status', 'flagged')->where('is_voided', false)
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('anomaly_flags')->whereColumn('anomaly_flags.transaction_id', 'transactions.id')->whereIn('status', ['new', 'reviewing', 'escalated']))->update(['validation_status' => 'valid']);
    }

    public function down(): void
    {
        throw new RuntimeException('Do not narrow percentages or discard consent/correction history. Restore a verified matching backup instead.');
    }
};
