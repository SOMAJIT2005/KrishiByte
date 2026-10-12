<?php

use App\Models\PriceReport;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
