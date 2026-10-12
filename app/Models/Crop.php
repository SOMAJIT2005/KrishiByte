<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Crop extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'variety_or_grade',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function priceObservations(): HasMany
    {
        return $this->hasMany(PriceObservation::class);
    }

    public function priceReports(): HasMany
    {
        return $this->hasMany(PriceReport::class);
    }

    public function markupRules(): HasMany
    {
        return $this->hasMany(MarkupRule::class);
    }
}
