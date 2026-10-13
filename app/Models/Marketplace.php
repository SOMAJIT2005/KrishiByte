<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Marketplace extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean', 'is_demo' => 'boolean'];

    public function district()
    {
        return $this->belongsTo(District::class);
    }
}
