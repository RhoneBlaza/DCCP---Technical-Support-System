<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reopening is now allowed only while a ticket sits in "Resolved", so the
     * configurable reopen window is no longer read by the application. Drop the
     * orphaned setting row so it stops showing up in settings exports.
     */
    public function up(): void
    {
        DB::table('settings')->where('key', 'reopen_window_days')->delete();
    }

    public function down(): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'reopen_window_days'],
            ['value' => '7', 'type' => 'integer', 'created_at' => now(), 'updated_at' => now()]
        );
    }
};
