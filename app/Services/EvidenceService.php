<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EvidenceService
{
    public static function store(UploadedFile $file): array
    {
        $bytes = file_get_contents($file->getRealPath());
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
        if (! Storage::disk('local')->put($path, $safeBytes)) {
            throw new \RuntimeException('Private evidence storage failed.');
        }

        return ['evidence_path' => $path, 'evidence_mime' => 'image/jpeg', 'evidence_original_sha256' => hash('sha256', $bytes), 'evidence_transformation' => 'Decoded and re-encoded to JPEG, quality 90; original metadata stripped.'];
    }
}
