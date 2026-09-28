<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "Pending External" shared the orange badge with the "High" priority, and
     * orange/yellow were too close to tell apart at a glance. Purple is unused
     * by every other system status and priority, so it separates cleanly.
     */
    private const REBALANCE = [
        'pending_external' => 'purple',
    ];

    public function up(): void
    {
        foreach (self::REBALANCE as $key => $color) {
            DB::table('ticket_statuses')
                ->where('key', $key)
                ->update(['color' => $color]);
        }
    }

    public function down(): void
    {
        foreach (self::REBALANCE as $key => $color) {
            DB::table('ticket_statuses')
                ->where('key', $key)
                ->update(['color' => 'orange']);
        }
    }
};
