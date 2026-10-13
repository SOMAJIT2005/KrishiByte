<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id', 'confirmation_status', 'confirmed_by_user_id', 'request_key', 'request_hash',
        'sequence_no',
        'seller_id',
        'buyer_id',
        'stage',
        'quantity',
        'unit',
        'price_per_unit',
        'total_amount',
        'previous_price_per_unit',
        'gross_price_spread',
        'markup_percent',
        'gross_margin_percent',
        'transaction_date',
        'district_id',
        'market_name',
        'latitude',
        'longitude',
        'note',
        'recorded_by_user_id',
        'validation_status',
        'is_voided',
        'void_reason',
        'voided_by_user_id',
    ];

    protected $casts = [
        'sequence_no' => 'integer',
        'quantity' => 'decimal:2',
        'price_per_unit' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'previous_price_per_unit' => 'decimal:2',
        'gross_price_spread' => 'decimal:2',
        'markup_percent' => 'decimal:2',
        'gross_margin_percent' => 'decimal:2',
        'transaction_date' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_voided' => 'boolean',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by_user_id');
    }

    public function anomalyFlags(): HasMany
    {
        return $this->hasMany(AnomalyFlag::class);
    }
}
