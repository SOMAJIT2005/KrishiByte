<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PriceReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_code', 'reporter_user_id', 'category', 'batch_id', 'is_demo',
        'reporter_name',
        'reporter_phone',
        'reporter_email',
        'crop_id',
        'district_id',
        'market_name',
        'reported_price',
        'unit',
        'report_narrative',
        'incident_date',
        'latitude',
        'longitude',
        'evidence_path', 'evidence_original_sha256', 'evidence_transformation',
        'evidence_mime',
        'status',
        'reviewed_by_user_id',
        'moderation_notes',
        'ip_address',
    ];

    protected $casts = [
        'reported_price' => 'decimal:2',
        'is_demo' => 'boolean',
        'incident_date' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public static function generateTrackingCode(): string
    {
        do {
            $code = 'RPT-'.now('Asia/Dhaka')->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (static::where('tracking_code', $code)->exists());

        return $code;
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
