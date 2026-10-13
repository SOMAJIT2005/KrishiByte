<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Models\Marketplace;
use App\Models\RetailPrice;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketplaceWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private function market(?int $district = null): Marketplace
    {
        return Marketplace::create(['district_id' => $district ?? District::first()->id, 'name' => 'QA Market '.Str::random(8)]);
    }

    private function shop(Marketplace $market): User
    {
        return User::factory()->create(['role' => 'retail', 'account_status' => 'active', 'trading_approved' => true, 'marketplace_id' => $market->id, 'district_id' => $market->district_id, 'shop_name' => 'QA Shop '.Str::random(8)]);
    }

    private function lot(User $shop, ?int $origin = null): Batch
    {
        return Batch::create(['batch_code' => Batch::generateBatchCode(), 'crop_id' => Crop::first()->id, 'farmer_id' => User::where('role', 'farmer')->first()->id, 'origin_district_id' => $origin ?? District::first()->id, 'harvest_date' => now(), 'initial_quantity' => 20.25, 'available_quantity' => 20.25, 'unit' => 'kg', 'initial_farmer_price' => 15, 'current_custodian_id' => $shop->id, 'current_stage' => 'retail_custody', 'status' => 'active']);
    }

    public function test_registration_requires_shop_market_and_district_and_rejects_mismatch(): void
    {
        $market = $this->market();
        $data = ['name' => 'QA retail', 'email' => 'market-register@example.test', 'role' => 'retail', 'nid_or_trade_license' => 'QA-LICENSE', 'password' => 'TestPassword123!', 'password_confirmation' => 'TestPassword123!'];
        $this->post(route('register.post'), $data)->assertSessionHasErrors(['district_id', 'marketplace_id', 'shop_name']);
        $data += ['district_id' => District::where('id', '!=', $market->district_id)->first()->id, 'marketplace_id' => $market->id, 'shop_name' => 'QA registered shop'];
        $this->post(route('register.post'), $data)->assertSessionHasErrors('marketplace_id');
        $data['district_id'] = $market->district_id;
        $this->post(route('register.post'), $data)->assertRedirect(route('retail.prices'))->assertSessionHasNoErrors();
        $user = User::where('email', $data['email'])->firstOrFail();
        $this->assertSame($market->id, $user->marketplace_id);
        $this->assertFalse($user->trading_approved);
    }

    public function test_quote_is_idempotent_and_preserves_custody_stock_and_purchase_history(): void
    {
        $market = $this->market();
        $shop = $this->shop($market);
        $lot = $this->lot($shop);
        $count = Transaction::count();
        $data = ['batch_id' => $lot->id, 'price_per_unit' => 35.75, 'request_key' => (string) Str::uuid()];
        $this->actingAs($shop)->post(route('retail.prices.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('retail.prices.store'), $data)->assertRedirect();
        $this->assertSame(1, RetailPrice::where('batch_id', $lot->id)->count());
        $this->assertEquals(20.25, $lot->fresh()->available_quantity);
        $this->assertSame($shop->id, $lot->fresh()->current_custodian_id);
        $this->assertSame($count, Transaction::count());
        $this->post(route('retail.prices.store'), array_replace($data, ['price_per_unit' => 36]))->assertStatus(409);
        $this->get(route('public.traceability.show', $lot->batch_code))->assertOk()->assertSee('35.75')->assertSee($shop->shop_name)->assertDontSee('Anonymous retail customer');
        $this->get(route('vendor.dashboard'))->assertRedirect(route('retail.prices'));
        $this->post(route('vendor.handoff.store'), [])->assertForbidden();
    }

    public function test_quotes_reject_other_stock_unapproved_shop_and_non_retail_roles(): void
    {
        $market = $this->market();
        $shop = $this->shop($market);
        $other = $this->shop($market);
        $lot = $this->lot($other);
        $data = ['batch_id' => $lot->id, 'price_per_unit' => 35, 'request_key' => (string) Str::uuid()];
        $this->actingAs($shop)->post(route('retail.prices.store'), $data)->assertForbidden();
        $other->update(['trading_approved' => false]);
        $this->actingAs($other)->post(route('retail.prices.store'), $data)->assertForbidden();
        foreach (['collector', 'wholesale', 'consumer', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'account_status' => 'active']))->post(route('retail.prices.store'), $data)->assertForbidden();
        }
        $this->assertSame(0, RetailPrice::where('batch_id', $lot->id)->count());
    }

    public function test_destination_filter_shows_multiple_origins_and_only_matching_shops(): void
    {
        $market = $this->market();
        $shop = $this->shop($market);
        $otherMarket = $this->market();
        $otherShop = $this->shop($otherMarket);
        $first = $this->lot($shop);
        $second = $this->lot($shop, District::where('id', '!=', $market->district_id)->first()->id);
        $other = $this->lot($otherShop);
        $response = $this->get(route('public.traceability', ['district_id' => $market->district_id, 'marketplace_id' => $market->id]))->assertOk()->assertSee($shop->shop_name)->assertSee($first->batch_code)->assertSee($second->batch_code)->assertDontSee($other->batch_code)->assertDontSee($otherShop->shop_name);
        $this->assertCount(2, $response->viewData('recentBatches'));
        $this->get(route('public.traceability', ['district_id' => District::where('id', '!=', $market->district_id)->first()->id, 'marketplace_id' => $market->id]))->assertStatus(422);
    }

    public function test_admin_adds_market_and_other_roles_cannot(): void
    {
        $data = ['district_id' => District::first()->id, 'name' => 'QA administrator marketplace'];
        $this->actingAs(User::factory()->create(['role' => 'retail']))->post(route('admin.marketplaces.store'), $data)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'account_status' => 'active']))->withSession(['auth.password_confirmed_at' => time()])->post(route('admin.marketplaces.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('marketplaces', $data + ['is_demo' => false]);
        $this->post(route('admin.marketplaces.store'), $data)->assertSessionHasErrors('name');
        $this->get(route('admin.marketplaces'))->assertOk()->assertSee($data['name']);
    }
}
