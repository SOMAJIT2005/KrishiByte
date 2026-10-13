<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceObservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'crop_id', 'price_report_id', 'is_demo',
        'district_id',
        'market_name',
        'unit',
        'observed_retail_price',
        'observation_date',
        'source',
        'verification_status',
        'linked_batch_id',
        'entered_by_user_id',
        'notes',
    ];

    protected $casts = [
        'observed_retail_price' => 'decimal:2',
        'observation_date' => 'date',
        'is_demo' => 'boolean',
    ];

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function linkedBatch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'linked_batch_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by_user_id');
    }
}
