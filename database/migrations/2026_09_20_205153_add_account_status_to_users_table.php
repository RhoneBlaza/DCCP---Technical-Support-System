<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_status')->default('pending')->after('is_active');
            $table->index('account_status');
        });

        // Backfill existing rows: active accounts are approved; inactive
        // requesters are treated as pending; inactive staff members are
        // treated as suspended.
        DB::table('users')->update(['account_status' => 'approved']);
        DB::table('users')->where('is_active', false)->where('role', 'requester')->update(['account_status' => 'pending']);
        DB::table('users')->where('is_active', false)->where('role', '<>', 'requester')->update(['account_status' => 'suspended']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['account_status']);
            $table->dropColumn('account_status');
        });
    }
};
