<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HandoffProposal extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['terms' => 'array', 'expires_at' => 'datetime', 'decided_at' => 'datetime'];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function proposer()
    {
        return $this->belongsTo(User::class, 'proposed_by_user_id');
    }
}
