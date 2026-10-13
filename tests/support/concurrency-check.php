<?php

// Run only against the disposable MySQL test database.
if (PHP_SAPI !== 'cli' || getenv('DB_DATABASE') !== 'krishibyte_test') {
    exit("Set DB_DATABASE=krishibyte_test first.\n");
}
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BatchHandoffService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

if (($argv[1] ?? '') === 'worker') {
    try {
        $result = DB::transaction(function () use ($argv) {
            $batch = Batch::lockForUpdate()->findOrFail((int) $argv[3]);
            usleep(300000); // Force overlapping contenders while holding the MySQL row lock.
            $service = app(BatchHandoffService::class);
            if ($argv[2] === 'accept') {
                return $service->decide((int) $argv[4], (int) $argv[5], 'accepted')->transaction_id;
            }

            return $service->recordRetailSale(['batch_id' => $batch->id, 'seller_id' => (int) $argv[5], 'buyer_id' => null, 'quantity' => $argv[6], 'price_per_unit' => '21.00', 'district_id' => District::first()->id, 'recorded_by_user_id' => (int) $argv[5]], $argv[4])->id;
        });
        echo json_encode(['ok' => true, 'id' => $result]);
    } catch (InvalidArgumentException $e) {
        echo json_encode(['ok' => false, 'reason' => $e->getMessage()]);
    }
    exit;
}
$marker = (string) Str::uuid();
$people = [];
$lots = [];
try {
    foreach (['farmer', 'collector', 'retail'] as $role) {
        $people[$role] = User::create(['name' => 'Concurrency QA '.$role, 'email' => $role.'.'.$marker.'@example.test', 'password' => Str::random(32), 'role' => $role, 'trading_approved' => true, 'account_status' => 'active']);
    }
    foreach (['farmer', 'retail'] as $role) {
        $lots[$role] = Batch::create(['batch_code' => 'QA-CONC-'.$role.'-'.substr($marker, 0, 12), 'crop_id' => Crop::first()->id, 'farmer_id' => $people['farmer']->id, 'origin_district_id' => District::first()->id, 'harvest_date' => now(), 'initial_quantity' => 100, 'available_quantity' => 100, 'unit' => 'kg', 'initial_farmer_price' => 20, 'current_custodian_id' => $people[$role]->id, 'current_stage' => $role === 'farmer' ? 'farm_gate' : 'retail_custody', 'status' => 'active']);
    }
    $proposal = app(BatchHandoffService::class)->propose(['batch_id' => $lots['farmer']->id, 'seller_id' => $people['farmer']->id, 'buyer_id' => $people['collector']->id, 'quantity' => '100.00', 'price_per_unit' => '21.00', 'district_id' => District::first()->id, 'recorded_by_user_id' => $people['farmer']->id], (string) Str::uuid());
    $race = function ($mode, $batch, $keys, $actor, $qty) {
        $processes = [];
        foreach ($keys as $key) {
            $process = new Process([PHP_BINARY, __FILE__, 'worker', $mode, (string) $batch, $key, (string) $actor, (string) $qty], base_path());
            $process->start();
            $processes[] = $process;
        }
        $results = [];
        foreach ($processes as $process) {
            $process->wait();
            if (! $process->isSuccessful()) {
                throw new RuntimeException($process->getErrorOutput());
            } $results[] = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        }

        return $results;
    };
    $accept = $race('accept', $lots['farmer']->id, [(string) $proposal->id, (string) $proposal->id], $people['collector']->id, 100);
    if (! $accept[0]['ok'] || ! $accept[1]['ok'] || $accept[0]['id'] !== $accept[1]['id'] || Transaction::where('batch_id', $lots['farmer']->id)->count() !== 1) {
        throw new RuntimeException('Concurrent acceptance invariant failed.');
    }
    $sales = $race('sale', $lots['retail']->id, [(string) Str::uuid(), (string) Str::uuid()], $people['retail']->id, 60);
    if (count(array_filter($sales, fn ($r) => $r['ok'])) !== 1 || (float) $lots['retail']->fresh()->available_quantity !== 40.0) {
        throw new RuntimeException('Concurrent oversell invariant failed.');
    }
    $key = (string) Str::uuid();
    $duplicates = $race('sale', $lots['retail']->id, [$key, $key], $people['retail']->id, 10);
    if (! $duplicates[0]['ok'] || ! $duplicates[1]['ok'] || $duplicates[0]['id'] !== $duplicates[1]['id'] || (float) $lots['retail']->fresh()->available_quantity !== 30.0) {
        throw new RuntimeException('Concurrent sale idempotency failed.');
    }
    echo "PASS: concurrent acceptance created one transaction; competing 60/100 sales did not oversell; duplicate sale deducted once.\n";
} finally {
    // Clean up only this invocation's fixtures in the disposable test database.
    $lotIds = array_map(fn ($lot) => $lot->id, $lots);
    $personIds = array_map(fn ($person) => $person->id, $people);
    $txIds = DB::table('transactions')->whereIn('batch_id', $lotIds)->pluck('id');
    $proposalIds = DB::table('handoff_proposals')->whereIn('batch_id', $lotIds)->pluck('id');
    DB::table('audit_logs')->whereIn('user_id', $personIds)->delete();
    DB::table('notifications')->whereIn('user_id', $personIds)->delete();
    DB::table('anomaly_flags')->whereIn('batch_id', $lotIds)->delete();
    DB::table('handoff_proposals')->whereIn('id', $proposalIds)->delete();
    DB::table('transactions')->whereIn('id', $txIds)->delete();
    DB::table('batches')->whereIn('id', $lotIds)->delete();
    DB::table('users')->whereIn('id', $personIds)->delete();
}
