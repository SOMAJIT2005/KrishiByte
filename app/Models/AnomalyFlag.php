<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnomalyFlag extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id', 'rule_snapshot',
        'transaction_id',
        'user_id',
        'rule_id',
        'reason_code',
        'measured_value',
        'threshold_value',
        'severity',
        'status',
        'reviewed_by_user_id',
        'reviewer_notes',
    ];

    protected $casts = [
        'rule_snapshot' => 'array',
        'measured_value' => 'decimal:2',
        'threshold_value' => 'decimal:2',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(MarkupRule::class, 'rule_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
