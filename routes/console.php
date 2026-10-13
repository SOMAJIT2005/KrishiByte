<?php

use App\Models\Batch;
use App\Models\PriceObservation;
use App\Models\PriceReport;
use Database\Seeders\DemoPriceHistorySeeder;
use Database\Seeders\MarketplaceShowcaseSeeder;
use Database\Seeders\ShowcaseScenarioSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('showcase:populate', function () {
    if (! app()->environment(['local', 'testing'])) {
        $this->error('Showcase data is only allowed in local/test environments.');

        return 1;
    }
    $this->info('Adding illustrative scenarios and 14 days of prices; preserving existing records and completed actions.');
    foreach ([ShowcaseScenarioSeeder::class, DemoPriceHistorySeeder::class, MarketplaceShowcaseSeeder::class] as $seeder) {
        if ($this->call('db:seed', ['--class' => $seeder, '--force' => true]) !== 0) {
            return 1;
        }
    }
    $this->table(['Dataset', 'Demo records'], [
        ['Workflow lots', Batch::where('batch_code', 'like', 'KB-DEMO-%')->count()],
        ['Analytics samples', PriceObservation::where('market_name', 'like', 'Illustrative % analytics sample %')->count()],
        ['Report examples', PriceReport::where('tracking_code', 'like', 'RPT-DEMO-%')->count()],
    ]);
    $this->info('Open http://127.0.0.1:8000/demo-showcase and follow the role walkthrough.');
})->purpose('Populate clearly labeled local demo prices and workflow scenarios without resetting data');

Artisan::command('reports:secure-evidence', function () {
    $moved = 0;
    foreach (PriceReport::whereNotNull('evidence_path')->cursor() as $report) {
        $path = $report->evidence_path;
        if (! str_starts_with($path, 'evidence/') || str_contains($path, '..')) {
            continue;
        }
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        if (! $public->exists($path)) {
            continue;
        }
        $bytes = $public->get($path);
        if (! $private->exists($path) && ! $private->put($path, $bytes)) {
            throw new RuntimeException('Could not secure evidence.');
        }
        if (hash('sha256', $private->get($path)) !== hash('sha256', $bytes)) {
            throw new RuntimeException('Evidence copy differs; original retained.');
        }
        $public->delete($path);
        $moved++;
    }
    $this->info("Secured {$moved} legacy evidence file(s).");
})->purpose('Preserve legacy evidence in private storage and remove its public copy');
