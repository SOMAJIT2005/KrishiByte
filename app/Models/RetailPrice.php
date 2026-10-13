<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetailPrice extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['price_per_unit' => 'decimal:2', 'is_demo' => 'boolean'];

    public function retailer()
    {
        return $this->belongsTo(User::class, 'retailer_id');
    }

    public function marketplace()
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
}
