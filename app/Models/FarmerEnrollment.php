<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmerEnrollment extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['grown_crop_ids' => 'array'];

    public function district()
    {
        return $this->belongsTo(District::class);
    }
}
