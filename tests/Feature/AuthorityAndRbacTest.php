<?php

namespace Tests\Feature;

use App\Models\AnomalyFlag;
use App\Models\PriceReport;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthorityAndRbacTest extends TestCase
{
    use DatabaseTransactions;

    protected User $authority;
    protected User $farmer;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authority = User::where('role', 'authority')->first();
        $this->farmer = User::where('role', 'farmer')->first();
        $this->admin = User::where('role', 'admin')->first();
    }

    /** AT-16: Authority reviews an anomaly flag */
    public function test_authority_reviews_anomaly_flag(): void
    {
        $flag = AnomalyFlag::first();
        $this->assertNotNull($flag, "Seeded anomaly flag required for test.");

        $response = $this->actingAs($this->authority)
            ->post(route('authority.flags.review', $flag->id), [
                'status' => 'cleared',
                'reviewer_notes' => 'Examined transport invoices; justified cold storage energy costs verified.',
            ]);

        $response->assertRedirect(route('authority.dashboard'));

        $flag->refresh();
        $this->assertEquals('cleared', $flag->status);
        $this->assertEquals($this->authority->id, $flag->reviewed_by_user_id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'FLAG_REVIEWED',
            'target_type' => 'AnomalyFlag',
            'target_id' => $flag->id,
            'user_id' => $this->authority->id,
        ]);
    }

    /** AT-16: Authority moderates consumer report */
    public function test_authority_moderates_consumer_report_and_creates_verified_observation(): void
    {
        $report = PriceReport::where('status', 'pending')->first();
        $this->assertNotNull($report, "Pending report required for test.");

        $response = $this->actingAs($this->authority)
            ->post(route('authority.reports.moderate', $report->id), [
                'status' => 'verified',
                'moderation_notes' => 'Field monitor confirmed stall rate on-site.',
            ]);

        $response->assertRedirect(route('authority.dashboard'));

        $report->refresh();
        $this->assertEquals('verified', $report->status);
        $this->assertEquals($this->authority->id, $report->reviewed_by_user_id);

        // Must create verified price observation for the Explorer
        $this->assertDatabaseHas('price_observations', [
            'crop_id' => $report->crop_id,
            'district_id' => $report->district_id,
            'observed_retail_price' => $report->reported_price,
            'verification_status' => 'verified',
        ]);
    }

    /** RBAC: Farmer cannot access authority dashboard */
    public function test_farmer_is_forbidden_from_authority_dashboard(): void
    {
        $response = $this->actingAs($this->farmer)->get(route('authority.dashboard'));
        $response->assertStatus(403);
    }

    /** RBAC: Public registration cannot select admin or authority role */
    public function test_registration_prohibits_self_assigning_admin_or_authority(): void
    {
        $response = $this->post(route('register.post'), [
            'name' => 'Attacker',
            'email' => 'attacker@example.com',
            'role' => 'admin', // Disallowed!
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'attacker@example.com']);
    }
}
