<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track when a ticket entered its current status, when it was resolved and
     * when it was last brought back from resolved, so the UI can show real
     * workflow timestamps instead of guessing from updated_at.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('status_updated_at')->nullable()->after('status_id');
            $table->timestamp('reopened_at')->nullable()->after('resolved_at');
        });

        // Backfill: updated_at is the closest historical approximation we have.
        DB::table('tickets')->update(['status_updated_at' => DB::raw('updated_at')]);

        $resolvedStatusIds = DB::table('ticket_statuses')
            ->where('type', 'resolved')
            ->pluck('id')
            ->all();

        if ($resolvedStatusIds !== []) {
            DB::table('tickets')
                ->whereIn('status_id', $resolvedStatusIds)
                ->whereNull('resolved_at')
                ->update(['resolved_at' => DB::raw('updated_at')]);
        }

        DB::table('tickets')
            ->where('reopen_count', '>', 0)
            ->whereNull('reopened_at')
            ->update(['reopened_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['status_updated_at', 'reopened_at']);
        });
    }
};
