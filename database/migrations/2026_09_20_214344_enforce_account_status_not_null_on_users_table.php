<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backfill legacy users (created before account_status existed) and then
     * require the column.
     *
     * "approved" is the safe database default: self-registration explicitly
     * stores "pending", while admin-created users and seeders explicitly store
     * "approved".
     */
    public function up(): void
    {
        DB::table('users')->whereNull('account_status')->update(['account_status' => 'approved']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('account_status')->default('approved')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_status')->default(null)->nullable()->change();
        });
    }
};
