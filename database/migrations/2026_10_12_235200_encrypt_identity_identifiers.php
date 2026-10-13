<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->text('nid_or_trade_license')->nullable()->change());
        foreach (DB::table('users')->whereNotNull('nid_or_trade_license')->get(['id', 'nid_or_trade_license']) as $user) {
            DB::table('users')->where('id', $user->id)->update(['nid_or_trade_license' => Crypt::encryptString($user->nid_or_trade_license)]);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Identity protection must not be removed by an automatic rollback; restore the isolated pre-upgrade backup and matching application key.');
    }
};
