<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Models\HandoffProposal;
use App\Models\Marketplace;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegisteredBuyerHandoffTest extends TestCase
{
    use DatabaseTransactions;

    public function test_new_traders_need_a_registered_district_and_approval_rejects_missing_district(): void
    {
        foreach (['collector', 'wholesale', 'retail'] as $role) {
            $email = 'qa-district-'.$role.'@example.test';
            $this->post(route('register.post'), ['name' => 'QA District Trader', 'email' => $email, 'role' => $role, 'nid_or_trade_license' => 'QA-SYNTHETIC-LICENSE', 'password' => 'TestPassword123!', 'password_confirmation' => 'TestPassword123!'])->assertSessionHasErrors('district_id');
            $this->assertDatabaseMissing('users', ['email' => $email]);
        }
        $trader = $this->person('collector', ['district_id' => null, 'nid_or_trade_license' => 'QA-SYNTHETIC-LICENSE', 'trading_approved' => false]);
        $this->actingAs($this->person('admin'))->withSession(['auth.password_confirmed_at' => time()])->post(route('admin.users.trading', $trader->id), ['approved' => 1, 'reason' => 'QA incomplete district registration'])->assertStatus(422);
        $this->assertFalse($trader->fresh()->trading_approved);
    }

    private function person(string $role, array $extra = []): User
    {
        return User::factory()->create(array_merge(['role' => $role, 'account_status' => 'active', 'trading_approved' => true, 'district_id' => District::first()->id], $extra));
    }

    private function lot(User $owner): Batch
    {
        return Batch::create(['batch_code' => Batch::generateBatchCode(), 'crop_id' => Crop::first()->id, 'farmer_id' => User::where('role', 'farmer')->firstOrFail()->id, 'origin_district_id' => District::first()->id, 'harvest_date' => now(), 'initial_quantity' => 10, 'available_quantity' => 10, 'unit' => 'kg', 'initial_farmer_price' => 20, 'current_custodian_id' => $owner->id, 'current_stage' => $owner->role.'_custody', 'status' => 'active']);
    }

    private function payload(Batch $batch, int $buyer): array
    {
        return ['batch_id' => $batch->id, 'buyer_id' => $buyer, 'buyer_district_id' => District::first()->id, 'request_key' => (string) Str::uuid(), 'stage' => 'auto', 'quantity' => 10, 'price_per_unit' => 31, 'district_id' => District::first()->id];
    }

    public function test_all_requested_registered_buyer_routes_work_through_http_and_recipient_acceptance(): void
    {
        $market = Marketplace::create(['district_id' => District::first()->id, 'name' => 'QA handoff market']);
        foreach ([['collector', 'wholesale'], ['collector', 'retail'], ['wholesale', 'retail']] as [$sellerRole,$buyerRole]) {
            $seller = $this->person($sellerRole);
            $buyer = $this->person($buyerRole, ['marketplace_id' => $buyerRole === 'retail' ? $market->id : null, 'shop_name' => $buyerRole === 'retail' ? 'QA receiving retail shop' : null]);
            $batch = $this->lot($seller);
            $this->actingAs($seller)->post(route('vendor.handoff.store'), $this->payload($batch, $buyer->id))->assertRedirect(route('handoffs.index'))->assertSessionHasNoErrors();
            $proposal = HandoffProposal::where('batch_id', $batch->id)->firstOrFail();
            $this->assertSame($buyer->id, $proposal->buyer_id);
            $this->assertSame($seller->id, $batch->fresh()->current_custodian_id);
            $this->assertSame(0, $batch->transactions()->count());
            $this->actingAs($buyer)->post(route('handoffs.decide', $proposal->id), ['decision' => 'accepted'])->assertRedirect();
            $batch->refresh();
            $tx = $batch->transactions()->firstOrFail();
            $this->assertSame($buyer->id, $batch->current_custodian_id);
            $this->assertSame($sellerRole.'_to_'.$buyerRole, $tx->stage);
            $this->assertSame($batch->farmer_id, $tx->batch->farmer_id);
            $this->assertEquals(10, $batch->available_quantity);
            $this->assertEquals(31, $tx->price_per_unit);
            $this->assertSame('counterparty_accepted', $tx->confirmation_status);
            $this->assertSame('Dispatched', $tx->movementStatusFor($seller));
            $this->assertSame('Received', $tx->movementStatusFor($buyer));
            $this->actingAs($seller)->get(route('vendor.dashboard'))->assertOk()->assertSee('Dispatched');
            $this->actingAs($buyer)->get(route($buyerRole === 'retail' ? 'retail.prices' : 'vendor.dashboard'))
                ->assertOk()->assertSee('Received')->assertSee($batch->batch_code);
        }
    }

    public function test_role_filter_keeps_wholesalers_visible_and_searches_registered_shop_names(): void
    {
        $collector = $this->person('collector');
        $wholesaler = $this->person('wholesale', ['name' => 'ZZZ QA listed wholesaler']);
        User::factory()->count(51)->create(['name' => 'AAA QA retail owner', 'role' => 'retail', 'account_status' => 'active', 'trading_approved' => true]);
        $this->actingAs($collector)->get(route('vendor.dashboard'))->assertOk()->assertViewHas('eligibleBuyers', fn ($buyers) => $buyers->isEmpty());
        $response = $this->get(route('vendor.dashboard', ['buyer_district_id' => District::first()->id]))->assertOk();
        $this->assertSame('wholesale', $response->viewData('buyerRole'));
        $this->assertTrue($response->viewData('eligibleBuyers')->contains('id', $wholesaler->id));
        $this->assertTrue($response->viewData('eligibleBuyers')->every(fn ($user) => $user->role === 'wholesale'));
        $market = Marketplace::create(['district_id' => District::first()->id, 'name' => 'QA searchable market']);
        $shop = $this->person('retail', ['shop_name' => 'QA Searchable Produce Shop', 'marketplace_id' => $market->id]);
        $this->person('retail', ['shop_name' => 'QA Searchable Unapproved Shop', 'trading_approved' => false]);
        $this->person('retail', ['shop_name' => 'QA Searchable Suspended Shop', 'account_status' => 'suspended']);
        $response = $this->get(route('vendor.dashboard', ['buyer_district_id' => District::first()->id, 'buyer_role' => 'retail', 'buyer_search' => 'QA Searchable']))->assertOk();
        $this->assertSame([$shop->id], $response->viewData('eligibleBuyers')->pluck('id')->all());
        $response->assertSee($shop->shop_name)->assertSee($market->name)->assertDontSee('QA Searchable Unapproved Shop')->assertDontSee('QA Searchable Suspended Shop');
    }

    public function test_unlisted_and_unapproved_buyers_cannot_receive_a_proposal(): void
    {
        $seller = $this->person('collector');
        $batch = $this->lot($seller);
        $this->actingAs($seller)->post(route('vendor.handoff.store'), $this->payload($batch, 999999999))->assertSessionHasErrors('buyer_id');
        foreach ([['trading_approved' => false], ['account_status' => 'suspended']] as $state) {
            $buyer = $this->person('retail', $state);
            $this->post(route('vendor.handoff.store'), $this->payload($batch, $buyer->id))->assertRedirect()->assertSessionHas('error');
        }
        $this->assertSame(0, HandoffProposal::where('batch_id', $batch->id)->count());
        $this->assertSame($seller->id, $batch->fresh()->current_custodian_id);
        $this->assertEquals(10, $batch->fresh()->available_quantity);
    }

    public function test_district_selection_scopes_every_seller_workspace_and_cannot_be_bypassed(): void
    {
        $district = District::first()->id;
        $outside = District::where('id', '!=', $district)->first()->id;
        foreach (['collector', 'wholesale', 'farmer'] as $sellerRole) {
            $seller = $this->person($sellerRole);
            foreach (['collector', 'wholesale', 'retail'] as $buyerRole) {
                if ($sellerRole === 'farmer' && $buyerRole !== 'collector') {
                    continue;
                }
                $local = $this->person($buyerRole, ['name' => 'QA district match '.Str::random(8)]);
                $foreign = $this->person($buyerRole, ['name' => 'QA district match '.Str::random(8), 'district_id' => $outside]);
                $response = $this->actingAs($seller)->get(route($sellerRole === 'farmer' ? 'farmer.handoff' : 'vendor.dashboard', ['buyer_district_id' => $district, 'buyer_role' => $buyerRole, 'buyer_search' => 'QA district match']))->assertOk();
                $buyers = $response->viewData('eligibleBuyers');
                $this->assertTrue($buyers->contains('id', $local->id));
                $this->assertFalse($buyers->contains('id', $foreign->id));
                $this->assertTrue($buyers->every(fn ($person) => $person->district_id === $district && $person->role === $buyerRole));
            }
        }
        $seller = $this->person('collector');
        $lot = $this->lot($seller);
        $foreign = $this->person('wholesale', ['district_id' => $outside]);
        $this->actingAs($seller)->post(route('vendor.handoff.store'), $this->payload($lot, $foreign->id))->assertRedirect()->assertSessionHas('error');
        $this->assertSame(0, HandoffProposal::where('batch_id', $lot->id)->count());
        $payload = $this->payload($lot, $foreign->id);
        unset($payload['buyer_district_id']);
        $this->post(route('vendor.handoff.store'), $payload)->assertSessionHasErrors('buyer_district_id');
    }
}
