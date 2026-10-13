<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Models\FarmerEnrollment;
use App\Models\FarmerRating;
use App\Models\FarmPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class FarmerProfileAndAgreementTest extends TestCase
{
    use DatabaseTransactions;

    private function person(string $role): User
    {
        return User::factory()->create(['role' => $role, 'trading_approved' => true, 'account_status' => 'active', 'farmer_profile_public' => $role === 'farmer', 'district_id' => District::first()->id]);
    }

    public function test_enrollment_requires_consent_and_admin_review_generates_private_contact_public_profile(): void
    {
        $data = ['name' => 'QA enrolled farmer', 'phone' => '01712345678', 'district_id' => District::first()->id, 'grown_crop_ids' => [Crop::first()->id], 'cultivation_notes' => 'Seasonal potatoes grown here.'];
        $this->post(route('farmers.enroll.store'), $data)->assertSessionHasErrors('public_profile_consent');
        $this->post(route('farmers.enroll.store'), $data + ['public_profile_consent' => 1])->assertRedirect(route('farmers.enroll'));
        $entry = FarmerEnrollment::where('name', $data['name'])->firstOrFail();
        $this->assertNull($entry->farmer_id);
        $collector = $this->person('collector');
        $this->actingAs($collector)->post(route('admin.farmer-enrollment.review', $entry->id), ['decision' => 'approved', 'reason' => 'Identity reviewed'])->assertForbidden();
        $admin = $this->person('admin');
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])->post(route('admin.farmer-enrollment.review', $entry->id), ['decision' => 'approved', 'reason' => 'Identity reviewed'])->assertRedirect();
        $entry->refresh();
        $this->assertNotNull($entry->farmer_id);
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])->post(route('admin.farmer-enrollment.review', $entry->id), ['decision' => 'approved', 'reason' => 'Repeated request'])->assertRedirect();
        $this->assertSame(1, User::where('name', $data['name'])->count());
        auth()->logout();
        $this->get(route('farmers.show', $entry->farmer_id))->assertOk()->assertSee($data['name'])->assertSee($data['cultivation_notes'])->assertDontSee($data['phone'])->assertDontSee('@profiles.krishibyte.invalid');
        $this->get(route('farmers.index'))->assertOk()->assertSee($data['name']);
    }

    public function test_public_and_middlemen_ratings_are_moderated_unique_and_cannot_self_rate(): void
    {
        $farmer = $this->person('farmer');
        $consumer = $this->person('consumer');
        $collector = $this->person('collector');
        $admin = $this->person('admin');
        $data = ['score' => 4, 'comment' => 'QA respectful transaction experience.'];
        $this->post(route('farmers.rate', $farmer->id), $data)->assertRedirect(route('login'));
        $this->actingAs($farmer)->post(route('farmers.rate', $farmer->id), $data)->assertForbidden();
        $this->actingAs($consumer)->post(route('farmers.rate', $farmer->id), $data)->assertRedirect();
        $rating = FarmerRating::where('reviewer_id', $consumer->id)->firstOrFail();
        $this->get(route('farmers.show', $farmer->id))->assertDontSee($data['comment']);
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])->post(route('admin.farmer-rating.review', $rating->id), ['decision' => 'approved', 'reason' => 'Appropriate experience feedback'])->assertRedirect();
        $this->get(route('farmers.show', $farmer->id))->assertOk()->assertSee($data['comment'])->assertSee('4.0 / 5');
        $this->actingAs($consumer)->post(route('farmers.rate', $farmer->id), ['score' => 3, 'comment' => 'QA updated experience after another visit.'])->assertRedirect();
        $this->assertSame(1, FarmerRating::where('reviewer_id', $consumer->id)->count());
        $this->assertSame('pending', $rating->fresh()->status);
        $this->actingAs($collector)->post(route('farmers.rate', $farmer->id), $data)->assertRedirect();
        $this->assertSame('middlemen', FarmerRating::where('reviewer_id', $collector->id)->first()->reviewer_group);
    }

    public function test_collector_can_create_lot_and_record_agreement_without_farmer_login_with_idempotency_and_private_evidence(): void
    {
        Storage::fake('local');
        $farmer = $this->person('farmer');
        $collector = $this->person('collector');
        $other = $this->person('collector');
        $photo = UploadedFile::fake()->image('qa-agreement.jpg', 500, 500);
        $data = ['farmer_id' => $farmer->id, 'crop_id' => Crop::first()->id, 'quantity' => '10.00', 'price_per_unit' => '21.00', 'district_id' => District::first()->id, 'harvest_date' => now('Asia/Dhaka')->toDateString(), 'witness_name' => 'QA witness', 'agreement_read_aloud' => 1, 'request_key' => (string) Str::uuid()];
        $this->actingAs($collector)->post(route('vendor.farm-purchase.store'), $data)->assertSessionHasErrors('agreement_photo');
        $this->post(route('vendor.farm-purchase.store'), $data + ['agreement_photo' => $photo])->assertRedirect();
        $purchase = FarmPurchase::where('collector_id', $collector->id)->firstOrFail();
        $batch = Batch::findOrFail($purchase->batch_id);
        $this->assertEquals($collector->id, $batch->current_custodian_id);
        $this->assertEquals(10, $batch->available_quantity);
        $this->assertSame('collector_attested', $batch->transactions()->first()->confirmation_status);
        $this->assertEquals(210, $batch->transactions()->first()->total_amount);
        $this->post(route('vendor.farm-purchase.store'), $data + ['agreement_photo' => $photo])->assertRedirect();
        $this->assertSame(1, FarmPurchase::where('collector_id', $collector->id)->count());
        $this->assertCount(1, Storage::disk('local')->allFiles());
        $this->post(route('vendor.farm-purchase.store'), array_replace($data, ['price_per_unit' => '22.00', 'agreement_photo' => $photo]))->assertSessionHas('error');
        $this->assertCount(1, Storage::disk('local')->allFiles());
        $this->get(route('farmers.agreement', $purchase->id))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('transactions.receipt', $purchase->transaction_id))->assertOk()->assertSee('Collector attested');
        $this->actingAs($other)->get(route('farmers.agreement', $purchase->id))->assertForbidden();
        auth()->logout();
        $this->get(route('farmers.agreement', $purchase->id))->assertRedirect(route('login'));
        $this->get(route('public.traceability.show', $batch->batch_code))->assertOk()->assertSee('collector attested')->assertDontSee($purchase->evidence_path)->assertDontSee('QA witness');
        $this->get(route('farmers.show', $farmer->id))->assertOk()->assertSee('10.00');
    }
}
