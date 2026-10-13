<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Models\MarkupRule;
use App\Models\PriceObservation;
use App\Models\PriceReport;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BatchHandoffService;
use App\Services\PriceReferenceService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class SecurityAndConfirmationTest extends TestCase
{
    public function test_pending_handoff_unit_snapshot_cannot_be_silently_changed(): void
    {
        $farmer = $this->person('farmer');
        $collector = $this->person('collector');
        $lot = $this->lot($farmer);
        $service = app(BatchHandoffService::class);
        $proposal = $service->propose($this->terms($lot, $farmer, $collector), (string) Str::uuid());
        $this->assertSame('kg', $proposal->terms['unit']);
        $lot->update(['unit' => 'piece']);
        try {
            $service->decide($proposal->id, $collector->id, 'accepted');
            $this->fail('Unit change must block acceptance.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('unit changed', $e->getMessage());
        }
        $this->assertSame('proposed', $proposal->fresh()->status);
        $this->assertSame(0, $lot->transactions()->count());
    }

    use DatabaseTransactions;

    private function person(string $role): User
    {
        $person = User::factory()->create(['role' => $role, 'trading_approved' => true, 'account_status' => 'active']);
        if ($role === 'admin') {
            foreach (District::pluck('id') as $districtId) {
                DB::table('review_assignments')->insert(['user_id' => $person->id, 'district_id' => $districtId, 'assigned_by' => $person->id, 'reason' => 'Explicit QA scope', 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        return $person;
    }

    private function lot(User $owner): Batch
    {
        return Batch::create(['batch_code' => Batch::generateBatchCode(), 'crop_id' => Crop::first()->id, 'farmer_id' => $owner->id, 'origin_district_id' => District::first()->id, 'harvest_date' => now(), 'initial_quantity' => 100, 'available_quantity' => 100, 'unit' => 'kg', 'initial_farmer_price' => 20, 'current_custodian_id' => $owner->id, 'current_stage' => $owner->isRetail() ? 'retail_custody' : 'farm_gate', 'status' => 'active', 'farm_location_name' => 'PRIVATE FARM ADDRESS', 'origin_latitude' => '24.1234567', 'origin_longitude' => '89.1234567']);
    }

    private function terms(Batch $lot, User $seller, ?User $buyer, ?User $actor = null): array
    {
        return ['batch_id' => $lot->id, 'seller_id' => $seller->id, 'buyer_id' => $buyer?->id, 'quantity' => '100.00', 'price_per_unit' => '21.00', 'district_id' => District::first()->id, 'recorded_by_user_id' => ($actor ?? $seller)->id];
    }

    public function test_daily_references_exclude_stale_mixed_demo_withdrawn_and_complaint_samples(): void
    {
        $crop = Crop::first();
        $district = District::first();
        $day = now('Asia/Dhaka')->toDateString();
        $base = ['crop_id' => $crop->id, 'district_id' => $district->id, 'market_name' => 'Regression market', 'unit' => $crop->unit, 'observation_date' => $day, 'source' => 'verified_market_survey', 'verification_status' => 'verified', 'entered_by_user_id' => User::first()->id];
        // Isolate this day/crop/district in this rolled-back test.
        PriceObservation::where('crop_id', $crop->id)->where('district_id', $district->id)->delete();
        foreach ([20, 21, 22, 22] as $price) {
            PriceObservation::create($base + ['observed_retail_price' => $price]);
        }
        foreach ([['observation_date' => now()->subDays(30)->toDateString()], ['unit' => 'piece'], ['is_demo' => true], ['verification_status' => 'rejected'], ['source' => 'verified_citizen_report']] as $override) {
            PriceObservation::create(array_replace($base + ['observed_retail_price' => 999], $override));
        }
        $stats = PriceReferenceService::stats(PriceReferenceService::query($crop->id, $crop->unit, $district->id, $day));
        $this->assertSame(3, $stats['sample_count']);
        $this->assertEquals(21, $stats['indicative_price']);
        $this->assertSame(1, $stats['source_count']);
        $this->get(route('public.prices', ['crop_id' => $crop->id, 'district_id' => $district->id, 'date' => $day]))->assertOk()->assertSee('21.00')->assertDontSee('High Confidence')->assertSee('not proof of independence');
        $farmer = $this->person('farmer');
        $farmer->update(['district_id' => $district->id]);
        $this->actingAs($farmer)->get(route('farmer.dashboard'))->assertOk()->assertSee('21.00');
        PriceObservation::where('observed_retail_price', 22)->where('crop_id', $crop->id)->delete();
        $this->assertNull(PriceReferenceService::stats(PriceReferenceService::query($crop->id, $crop->unit, $district->id, $day))['indicative_price']);
    }

    public function test_proposal_reserves_without_moving_stock_and_acceptance_is_idempotent(): void
    {
        $farmer = $this->person('farmer');
        $collector = $this->person('collector');
        $lot = $this->lot($farmer);
        $service = app(BatchHandoffService::class);
        $terms = $this->terms($lot, $farmer, $collector, $collector);
        $key = (string) Str::uuid();
        $proposal = $service->propose($terms, $key);
        $this->assertEquals($farmer->id, $lot->fresh()->current_custodian_id);
        $this->assertEquals(0, $lot->transactions()->count());
        $this->assertEquals($proposal->id, $service->propose($terms, $key)->id);
        try {
            $service->propose(array_replace($terms, ['price_per_unit' => '22.00']), $key);
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('different terms', $e->getMessage());
        }
        try {
            $service->propose($terms, (string) Str::uuid());
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('active proposal', $e->getMessage());
        }
        try {
            $service->decide($proposal->id, $collector->id, 'accepted');
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('intended participant', $e->getMessage());
        }
        $accepted = $service->decide($proposal->id, $farmer->id, 'accepted');
        $this->assertEquals($collector->id, $lot->fresh()->current_custodian_id);
        $this->assertSame('counterparty_accepted', Transaction::find($accepted->transaction_id)->confirmation_status);
        $this->assertEquals($accepted->transaction_id, $service->decide($proposal->id, $farmer->id, 'accepted')->transaction_id);
        $this->assertEquals(1, $lot->transactions()->count());
    }

    public function test_dispute_cancellation_expiry_and_suspension_do_not_transfer_custody(): void
    {
        $farmer = $this->person('farmer');
        $collector = $this->person('collector');
        $lot = $this->lot($farmer);
        $service = app(BatchHandoffService::class);
        $terms = $this->terms($lot, $farmer, $collector);
        $proposal = $service->propose($terms, (string) Str::uuid());
        $service->decide($proposal->id, $collector->id, 'disputed', 'Price was not agreed');
        $this->assertSame('disputed', $proposal->fresh()->status);
        $proposal = $service->propose($terms, (string) Str::uuid());
        $service->decide($proposal->id, $farmer->id, 'cancelled', 'Wrong destination');
        $this->assertSame('cancelled', $proposal->fresh()->status);
        $proposal = $service->propose($terms, (string) Str::uuid());
        $proposal->update(['expires_at' => now()->subMinute()]);
        $this->assertSame('expired', $service->decide($proposal->id, $collector->id, 'accepted')->status);
        $proposal = $service->propose($terms, (string) Str::uuid());
        $collector->update(['account_status' => 'suspended']);
        try {
            $service->decide($proposal->id, $collector->id, 'accepted');
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('intended participant', $e->getMessage());
        }
        $this->assertEquals(0, $lot->transactions()->count());
        $this->assertEquals($farmer->id, $lot->fresh()->current_custodian_id);
        $this->actingAs($collector)->post(route('public.report.store'), [])->assertForbidden();
        $this->assertGuest();
    }

    public function test_unilateral_finalization_is_rejected_at_service_boundary(): void
    {
        $farmer = $this->person('farmer');
        $lot = $this->lot($farmer);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Counterparty acceptance');
        app(BatchHandoffService::class)->recordHandoff($this->terms($lot, $farmer, $this->person('collector')));
    }

    public function test_anonymous_retail_idempotency_private_consumers_and_correct_audit(): void
    {
        $retail = $this->person('retail');
        $lot = $this->lot($retail);
        $service = app(BatchHandoffService::class);
        $terms = $this->terms($lot, $retail, null);
        $terms['quantity'] = '10.00';
        $key = (string) Str::uuid();
        $tx = $service->recordRetailSale($terms, $key);
        $this->assertNull($tx->buyer_id);
        $this->assertEquals($tx->id, $service->recordRetailSale($terms, $key)->id);
        $this->assertEquals(90, $lot->fresh()->available_quantity);
        $this->assertEquals($retail->id, AuditLog::where('target_type', 'Transaction')->where('target_id', $tx->id)->first()->after_payload['custodian_id']);
        $consumer = $this->person('consumer');
        $service->recordRetailSale(array_replace($terms, ['buyer_id' => $consumer->id]), (string) Str::uuid());
        $this->get(route('public.traceability.show', $lot->batch_code))->assertOk()->assertDontSee($consumer->name)->assertDontSee('PRIVATE FARM ADDRESS')->assertDontSee('24.1234567')->assertDontSee('Private retail customer')->assertSee('Retail shop & selling price', false);
        $this->actingAs($this->person('collector'))->get(route('transactions.receipt', $tx->id))->assertForbidden();
        $this->actingAs($this->person('admin'))->get(route('admin.batches.detail', $lot->id))->assertOk();
        $this->actingAs($retail)->get(route('transactions.receipt', $tx->id))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        try {
            $service->recordRetailSale(array_replace($terms, ['quantity' => 95]), (string) Str::uuid());
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('exceeds available', $e->getMessage());
        }
        $this->assertEquals(80, $lot->fresh()->available_quantity);
    }

    public function test_rules_respect_dates_zero_caps_metric_severity_and_snapshot(): void
    {
        $farmer = $this->person('farmer');
        $lot = $this->lot($farmer);
        $admin = $this->person('admin');
        $base = ['crop_id' => $lot->crop_id, 'stage' => 'farmer_to_collector', 'max_markup_percent' => 100, 'max_spread_amount' => 0, 'is_active' => true, 'created_by_user_id' => $admin->id, 'explanation' => 'Snapshot rule'];
        $active = MarkupRule::create($base);
        MarkupRule::create($base + ['effective_from' => now()->addDays(2)->toDateString()]);
        MarkupRule::create($base + ['effective_to' => now()->subDays(2)->toDateString()]);
        $service = app(BatchHandoffService::class);
        $collector = $this->person('collector');
        $proposal = $service->propose($this->terms($lot, $farmer, $collector), (string) Str::uuid());
        $tx = Transaction::find($service->decide($proposal->id, $collector->id, 'accepted')->transaction_id);
        $flag = $tx->anomalyFlags()->firstOrFail();
        $this->assertEquals($active->id, $flag->rule_id);
        $this->assertSame('high', $flag->severity);
        $this->assertSame('EXCESSIVE_SPREAD', $flag->reason_code);
        $active->update(['explanation' => 'Changed later']);
        $this->assertSame('Snapshot rule', $flag->fresh()->rule_snapshot['explanation']);
        $this->assertDatabaseHas('notifications', ['user_id' => $farmer->id, 'message' => 'Gross price spread 1.00 BDT/kg exceeded 0.00 BDT/kg; high review signal, not a misconduct finding.']);
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])->post(route('admin.rules.store'), ['crop_id' => $lot->crop_id, 'stage' => 'all', 'max_markup_percent' => 10, 'max_spread_amount' => 0, 'explanation' => 'Zero allowed'])->assertRedirect();
        $this->assertEquals('0.00', MarkupRule::latest('id')->first()->max_spread_amount);
    }

    public function test_admin_capabilities_recent_password_and_conflict_of_interest(): void
    {
        $admin = $this->person('admin');
        $farmer = $this->person('farmer');
        $this->actingAs($admin)->get(route('farmer.dashboard'))->assertForbidden();
        $this->get(route('vendor.dashboard'))->assertForbidden();
        $this->post(route('admin.users.verify', $farmer->id), ['verified' => 1, 'reason' => 'Valid reason'])->assertRedirect(route('password.confirm'));
        $this->assertFalse($farmer->fresh()->is_verified);
        $reviewer = $this->person('authority');
        $report = PriceReport::create(['tracking_code' => PriceReport::generateTrackingCode(), 'reporter_user_id' => $reviewer->id, 'crop_id' => Crop::first()->id, 'district_id' => District::first()->id, 'market_name' => 'Own report', 'unit' => 'kg', 'reported_price' => 30, 'report_narrative' => 'Own report narrative', 'incident_date' => now(), 'status' => 'pending']);
        $this->actingAs($reviewer)->post(route('authority.reports.moderate', $report->id), ['status' => 'verified', 'moderation_notes' => 'Own review attempt'])->assertForbidden();
    }

    public function test_registration_rotates_session_and_ignores_verification_input(): void
    {
        $this->withSession(['prior' => 'session']);
        $id = session()->getId();
        $this->post(route('register.post'), ['name' => 'Registration QA', 'email' => Str::uuid().'@example.test', 'role' => 'farmer', 'password' => 'SecurePass123!', 'password_confirmation' => 'SecurePass123!', 'is_verified' => 1, 'account_status' => 'suspended'])->assertRedirect();
        $this->assertNotEquals($id, session()->getId());
        $this->assertFalse(auth()->user()->is_verified);
        $this->assertSame('active', auth()->user()->account_status);
    }

    public function test_uploads_decode_strip_metadata_and_reject_invalid_or_large_images(): void
    {
        Storage::fake('local');
        $actor = $this->person('consumer');
        $payload = ['category' => 'quality', 'crop_id' => Crop::first()->id, 'district_id' => District::first()->id, 'market_name' => 'Upload QA', 'unit' => 'kg', 'incident_date' => now()->toDateString(), 'report_narrative' => 'Evidence validation test narrative.'];
        $this->actingAs($actor)->post(route('public.report.store'), $payload + ['evidence_photo' => UploadedFile::fake()->image('original.png')])->assertRedirect();
        $report = PriceReport::where('reporter_user_id', $actor->id)->firstOrFail();
        $this->assertSame('image/jpeg', $report->evidence_mime);
        $this->assertNotNull($report->evidence_original_sha256);
        $this->assertStringContainsString('metadata stripped', $report->evidence_transformation);
        $before = count(Storage::disk('local')->files('evidence'));
        $payload['market_name'] = 'Rejected QA';
        $this->post(route('public.report.store'), $payload + ['evidence_photo' => UploadedFile::fake()->image('giant.jpg', 5000, 10)])->assertSessionHasErrors('evidence_photo');
        $this->post(route('public.report.store'), $payload + ['evidence_photo' => UploadedFile::fake()->createWithContent('script.jpg', '<?php echo "bad";')])->assertSessionHasErrors('evidence_photo');
        $this->assertSame($before, count(Storage::disk('local')->files('evidence')));
        $this->post(route('public.report.store'), array_replace($payload, ['unit' => 'arbitrary']))->assertSessionHasErrors('unit');
    }

    public function test_security_headers_demo_get_and_production_shortcuts(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'DENY')->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->get('/demo-login/admin')->assertStatus(405);
        $this->post('/demo-login/admin')->assertNotFound();
        $this->app['env'] = 'production';
        $this->get('/login')->assertDontSee('1-Click Demo');
        $this->post('/demo-login/admin')->assertStatus(419); // CSRF is enforced outside the test environment.
    }

    public function test_rolled_back_acceptance_emits_no_success_sms(): void
    {
        $sms = \Mockery::mock(SmsService::class);
        $sms->shouldNotReceive('send');
        $this->app->instance(SmsService::class, $sms);
        $farmer = $this->person('farmer');
        $collector = $this->person('collector');
        $collector->update(['phone' => 'QA_ONLY']);
        $lot = $this->lot($farmer);
        try {
            DB::transaction(function () use ($lot, $farmer, $collector) {
                $service = app(BatchHandoffService::class);
                $proposal = $service->propose($this->terms($lot, $farmer, $collector), (string) Str::uuid());
                $service->decide($proposal->id, $collector->id, 'accepted');
                throw new \RuntimeException('Simulated transaction failure');
            });
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated transaction failure', $e->getMessage());
        }
        $this->assertEquals(0, $lot->transactions()->count());
        $this->assertEquals($farmer->id, $lot->fresh()->current_custodian_id);
    }

    public function test_sensitive_identifiers_are_encrypted_and_not_serialized(): void
    {
        $person = $this->person('farmer');
        $person->update(['nid_or_trade_license' => 'QA_IDENTIFIER']);
        $stored = DB::table('users')->where('id', $person->id)->value('nid_or_trade_license');
        $this->assertNotSame('QA_IDENTIFIER', $stored);
        $this->assertSame('QA_IDENTIFIER', $person->fresh()->nid_or_trade_license);
        $this->assertArrayNotHasKey('nid_or_trade_license', $person->fresh()->toArray());
    }

    public function test_registration_and_tracking_rate_limits(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('register.post'), ['email' => 'invalid'])->assertRedirect();
        }
        $this->post(route('register.post'), ['email' => 'invalid'])->assertStatus(429);
        for ($i = 0; $i < 30; $i++) {
            $this->get(route('public.report.track'))->assertOk();
        }
        $this->get(route('public.report.track'))->assertStatus(429);
    }

    public function test_audit_application_update_is_rejected(): void
    {
        $audit = AuditLog::create(['action' => 'QA_APPEND_ONLY', 'target_type' => 'User', 'target_id' => User::first()->id, 'created_at' => now()]);
        $this->expectException(\LogicException::class);
        $audit->update(['action' => 'REWRITTEN']);
    }
}
