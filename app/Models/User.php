<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'account_status',
        'nid_or_trade_license',
        'district_id',
        'password',
        'is_verified', 'verified_by_user_id', 'verification_date', 'verification_reason',
        'farmer_profile_public', 'grown_crop_ids', 'cultivation_notes',
        'status_version', 'trading_approved', 'publication_consented_at', 'publication_withdrawn_at', 'consent_recorded_by', 'consent_method',
        'marketplace_id', 'shop_name',
    ];

    protected $attributes = ['is_verified' => false];

    protected $hidden = [
        'password', 'nid_or_trade_license', 'phone', 'email',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_verified' => 'boolean', 'verification_date' => 'datetime',
            'password' => 'hashed', 'nid_or_trade_license' => 'encrypted',
            'farmer_profile_public' => 'boolean', 'grown_crop_ids' => 'array',
            'trading_approved' => 'boolean', 'publication_consented_at' => 'datetime', 'publication_withdrawn_at' => 'datetime',
            'status_version' => 'integer',
        ];
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function batchesCreated(): HasMany
    {
        return $this->hasMany(Batch::class, 'farmer_id');
    }

    public function batchesInCustody(): HasMany
    {
        return $this->hasMany(Batch::class, 'current_custodian_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Transaction::class, 'seller_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Transaction::class, 'buyer_id');
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function isFarmer(): bool
    {
        return $this->role === 'farmer';
    }

    public function isCollector(): bool
    {
        return $this->role === 'collector';
    }

    public function isWholesale(): bool
    {
        return $this->role === 'wholesale';
    }

    public function isRetail(): bool
    {
        return $this->role === 'retail';
    }

    public function isAuthority(): bool
    {
        return in_array($this->role, ['authority', 'admin'], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isVendor(): bool
    {
        return in_array($this->role, ['collector', 'wholesale', 'retail'], true);
    }
}
