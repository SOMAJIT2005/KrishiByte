<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarkupRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'crop_id',
        'stage',
        'max_markup_percent',
        'max_spread_amount',
        'effective_from',
        'effective_to',
        'is_active',
        'created_by_user_id',
        'explanation',
    ];

    protected $casts = [
        'max_markup_percent' => 'decimal:2',
        'max_spread_amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
