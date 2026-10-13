<?php

namespace App\Services;

use App\Models\User;

class FarmerAccess
{
    public static function id(): ?int
    {
        if (auth()->user()?->isFarmer() && auth()->user()->account_status === 'active') {
            return auth()->id();
        }
        $id = session('farmer_access_expires', 0) > time() ? session('farmer_access_id') : null;

        return $id && User::whereKey($id)->where('role', 'farmer')->where('account_status', 'active')->exists() ? $id : null;
    }
}
