<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Repair the previously applied blanket migration without republishing any withdrawn profile.
        $consented = DB::table('farmer_enrollments')->where('status', 'approved')->whereNotNull('farmer_id')->pluck('farmer_id');
        DB::table('users')->where('role', 'farmer')->whereNotIn('id', $consented)->whereNull('publication_withdrawn_at')
            ->where('email', '!=', 'farmer.rahim@krishibyte.local')->where('email', 'not like', 'grower.%@demo.krishibyte.invalid')
            ->where('consent_method', 'Historical enrollment consent or explicit synthetic demo profile')
            ->update(['farmer_profile_public' => false, 'publication_consented_at' => null, 'consent_method' => null]);
    }

    public function down(): void
    {
        throw new RuntimeException('Do not restore blanket publication; use a verified backup.');
    }
};
