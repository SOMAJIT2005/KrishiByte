<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_reports', fn (Blueprint $table) => $table->boolean('is_demo')->default(false));
        DB::table('price_reports')->whereIn('tracking_code', ['RPT-20261012-ON001', 'RPT-20261011-PO002', 'RPT-20261009-RE003'])->update(['is_demo' => true]);
        DB::table('price_observations')->whereIn('price_report_id', DB::table('price_reports')->where('is_demo', true)->select('id'))->update(['is_demo' => true]);
    }

    public function down(): void
    {
        throw new RuntimeException('Preserve demo provenance labels when restoring data.');
    }
};
