<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Marketplace;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TradeLicenseRegistrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_collectors_and_retail_shops_cannot_register_without_a_trade_license(): void
    {
        foreach (['collector', 'retail'] as $role) {
            foreach ([null, '   '] as $license) {
                $data = $this->registration($role);
                $data['nid_or_trade_license'] = $license;
                $this->from(route('register'))->post(route('register.post'), $data)
                    ->assertRedirect(route('register'))->assertSessionHasErrors('nid_or_trade_license');
                $this->assertDatabaseMissing('users', ['email' => $data['email']]);
            }
        }
    }

    public function test_traders_with_license_can_register_and_the_number_remains_encrypted_and_unverified(): void
    {
        foreach (['collector', 'retail'] as $role) {
            $data = $this->registration($role);
            $data['nid_or_trade_license'] = 'DEMO-TRADE-123';
            $this->post(route('register.post'), $data)->assertRedirect(route($role === 'retail' ? 'retail.prices' : 'vendor.dashboard'))->assertSessionHasNoErrors();
            $user = User::where('email', $data['email'])->firstOrFail();
            $this->assertSame('DEMO-TRADE-123', $user->nid_or_trade_license);
            $this->assertNotSame('DEMO-TRADE-123', DB::table('users')->where('id', $user->id)->value('nid_or_trade_license'));
            $this->assertFalse($user->is_verified);
            auth()->logout();
        }
    }

    public function test_other_registration_roles_do_not_require_a_trade_license(): void
    {
        foreach (['consumer', 'farmer', 'wholesale'] as $role) {
            $data = $this->registration($role);
            $this->post(route('register.post'), $data)->assertRedirect()->assertSessionHasNoErrors();
            $this->assertDatabaseHas('users', ['email' => $data['email'], 'role' => $role]);
            auth()->logout();
        }
    }

    private function registration(string $role): array
    {
        $market = Marketplace::firstOrCreate(['district_id' => District::first()->id, 'name' => 'Registration test market']);

        return ['name' => 'Demo Registration', 'email' => $role.'-license@example.test', 'role' => $role, 'district_id' => $market->district_id, 'marketplace_id' => $market->id, 'shop_name' => 'Test shop',
            'password' => 'TestPassword123!', 'password_confirmation' => 'TestPassword123!'];
    }
}
