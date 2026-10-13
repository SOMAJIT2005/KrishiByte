<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmPurchase extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['terms' => 'array'];
}
