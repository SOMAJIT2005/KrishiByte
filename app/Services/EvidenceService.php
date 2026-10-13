<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EvidenceService
{
    public static function store(UploadedFile $file): array
    {
        $bytes = file_get_contents($file->getRealPath());
        if (strlen($bytes) > 2097152) {
            throw ValidationException::withMessages(['evidence_photo' => 'Evidence must be no larger than 2 MB.']);
        }
        $info = @getimagesizefromstring($bytes);
        if (! $info || $info[0] > 4096 || $info[1] > 4096 || $info[0] * $info[1] > 12000000) {
            throw ValidationException::withMessages(['evidence_photo' => 'Image dimensions exceed the 4096px / 12 megapixel limit.']);
        }
        $image = @imagecreatefromstring($bytes);
        if (! $image) {
            throw ValidationException::withMessages(['evidence_photo' => 'The image could not be decoded.']);
        }
        ob_start();
        imagejpeg($image, null, 90);
        $safeBytes = ob_get_clean();
        imagedestroy($image);
        $path = 'evidence/report_'.Str::random(32).'.jpg';
        $lock = DB::selectOne("SELECT GET_LOCK('krishibyte_evidence_budget', 10) AS acquired");
        if ((int) $lock->acquired !== 1) {
            throw new \RuntimeException('Evidence storage is busy; please retry.');
        }
        try {
            // Local pilot ceiling: filesystem scan; replace with a reconciled usage ledger at larger scale.
            $usage = 0;
            foreach (Storage::disk('local')->allFiles('evidence') as $existing) {
                $usage += Storage::disk('local')->size($existing);
            }
            $budget = (int) config('evidence.storage_budget_bytes', 512 * 1024 * 1024);
            if ($usage + strlen($safeBytes) > $budget) {
                Log::warning('Private evidence budget reached', ['bytes' => $usage, 'limit' => $budget]);
                throw ValidationException::withMessages(['evidence_photo' => 'Private evidence storage budget reached. Contact an administrator.']);
            }
            if ($usage > $budget * .8) {
                Log::warning('Private evidence storage exceeds 80 percent of budget', ['bytes' => $usage]);
            }
            if (! Storage::disk('local')->put($path, $safeBytes)) {
                throw new \RuntimeException('Private evidence storage failed.');
            }
        } finally {
            DB::select("SELECT RELEASE_LOCK('krishibyte_evidence_budget')");
        }

        return ['evidence_path' => $path, 'evidence_mime' => 'image/jpeg', 'evidence_original_sha256' => hash('sha256', $bytes), 'evidence_transformation' => 'Decoded and re-encoded to JPEG, quality 90; original metadata stripped.'];
    }
}
