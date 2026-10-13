<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Exact attribution text from the original illustrative seeder, not independently verified DAM data.
        DB::table('price_observations')->where('notes', 'Surveyed by Directorate of Agricultural Marketing (DAM) field monitor.')
            ->whereIn('entered_by_user_id', DB::table('users')->where('email', 'authority@krishibyte.local')->select('id'))->update(['is_demo' => true]);
    }

    public function down(): void
    { /* Do not turn illustrative samples back into apparent real evidence. */
    }
};
