<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ManualTestingSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManualTestingDataTest extends TestCase
{
    use DatabaseTransactions;

    public function test_default_and_account_seeders_cannot_create_test_accounts_in_production(): void
    {
        $before = DB::table('users')->count();
        $this->app['env'] = 'production';
        foreach ([DatabaseSeeder::class, UserSeeder::class] as $seeder) {
            try {
                $this->artisan('db:seed', ['--class' => $seeder, '--force' => true])->run();
                $this->fail('Production synthetic seed unexpectedly succeeded.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('restricted to local/test environments', $exception->getMessage());
            }
            $this->assertSame($before, DB::table('users')->count());
        }
    }

    public function test_manual_fixtures_cover_every_table_and_preserve_repeated_actions(): void
    {
        Storage::fake('local');
        $this->seed(ManualTestingSeeder::class);
        foreach (DB::select('SHOW TABLES') as $table) {
            $name = array_values((array) $table)[0];
            $this->assertGreaterThan(0, DB::table($name)->count(), $name.' lacks manual fixture coverage');
        }
        $before = DB::table('correction_requests')->count();
        $accessBefore = DB::table('farmer_access_requests')->count();
        $this->seed(ManualTestingSeeder::class);
        $this->assertSame($before, DB::table('correction_requests')->count());
        $this->assertSame($accessBefore, DB::table('farmer_access_requests')->count());
        $this->assertDatabaseHas('correction_requests', ['status' => 'accepted']);
        $this->assertDatabaseHas('jobs', ['queue' => 'manual-fixture-disabled', 'available_at' => 2147483647]);
    }

    public function test_manual_population_is_disabled_in_production_and_showcase_routes_are_gone(): void
    {
        $this->get('/demo-showcase')->assertNotFound();
        $this->get('/login')->assertOk()->assertDontSee('1-Click Demo')->assertDontSee('/demo-login/', false);
        $this->post('/demo-login/admin')->assertNotFound();
        $this->app['env'] = 'production';
        $this->artisan('manual:populate')->expectsOutput('Manual test data is only allowed in local/test environments.')->assertExitCode(1);
    }
}
