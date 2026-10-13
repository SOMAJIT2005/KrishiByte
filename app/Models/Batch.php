<?php

namespace App\Models;

use App\Services\CalculationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_code',
        'crop_id',
        'farmer_id',
        'origin_district_id',
        'farm_location_name',
        'origin_latitude',
        'origin_longitude',
        'harvest_date',
        'initial_quantity',
        'available_quantity',
        'unit',
        'initial_farmer_price',
        'current_custodian_id',
        'current_stage',
        'status',
        'verification_status',
        'verified_by_admin_id',
        'verified_at',
        'admin_verification_notes',
        'notes',
    ];

    protected $casts = [
        'harvest_date' => 'date',
        'initial_quantity' => 'decimal:2',
        'available_quantity' => 'decimal:2',
        'initial_farmer_price' => 'decimal:2',
        'origin_latitude' => 'decimal:7',
        'origin_longitude' => 'decimal:7',
        'verified_at' => 'datetime',
    ];

    public static function generateBatchCode(): string
    {
        do {
            $code = 'KB-'.now('Asia/Dhaka')->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (static::where('batch_code', $code)->exists());

        return $code;
    }

    public function handoffProposals(): HasMany
    {
        return $this->hasMany(HandoffProposal::class);
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function originDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'origin_district_id');
    }

    public function currentCustodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_custodian_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->orderBy('sequence_no', 'asc');
    }

    public function anomalyFlags(): HasMany
    {
        return $this->hasMany(AnomalyFlag::class);
    }

    public function verifiedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_admin_id');
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function getLatestPriceAttribute(): float
    {
        $lastTx = $this->transactions()->where('is_voided', false)->reorder()->latest('sequence_no')->first();

        return $lastTx ? (float) $lastTx->price_per_unit : (float) $this->initial_farmer_price;
    }

    public function getAcquisitionPriceAttribute(): float
    {
        $tx = $this->transactions()->where('is_voided', false)->where('stage', '!=', 'retail_sale')->reorder()->latest('sequence_no')->first();

        return $tx ? (float) $tx->price_per_unit : (float) $this->initial_farmer_price;
    }

    public function getCustodyMarkupPercentAttribute(): ?float
    {
        return CalculationService::calculateMarkupPercent((float) $this->initial_farmer_price, $this->acquisition_price);
    }

    /**
     * Calculate cumulative price growth from farm-gate to current latest transaction.
     * Shows total markup across ALL middlemen in the chain.
     */
    public function getCumulativeMarkupPercentAttribute(): ?float
    {
        $lastTx = $this->transactions()->where('is_voided', false)->reorder()->latest('sequence_no')->first();
        if (! $lastTx) {
            return null;
        }

        return CalculationService::calculateMarkupPercent((float) $this->initial_farmer_price, (float) $lastTx->price_per_unit);
    }

    public function getCumulativeSpreadAttribute(): ?float
    {
        $lastTx = $this->transactions()->where('is_voided', false)->reorder()->latest('sequence_no')->first();
        if (! $lastTx) {
            return null;
        }

        return CalculationService::calculateSpread((float) $this->initial_farmer_price, (float) $lastTx->price_per_unit);
    }

    /**
     * Total number of custody handoffs (middleman transfers) in the chain.
     */
    public function getTotalHandoffsAttribute(): int
    {
        return $this->transactions()->where('is_voided', false)->where('stage', '!=', 'retail_sale')->count();
    }

    /**
     * Get all unique districts the batch has passed through (origin + all transfer markets).
     */
    public function getTraversedDistrictsAttribute(): array
    {
        $districts = [$this->originDistrict->name];
        foreach ($this->transactions as $tx) {
            if ($tx->district && $tx->district->name) {
                $districts[] = $tx->district->name;
            }
        }

        return array_values(array_unique($districts));
    }
}
