<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('penalty_removal_requests') || ! Schema::hasColumn('penalty_removal_requests', 'status')) {
            return;
        }

        // Normalize legacy statuses into the current 2-step chain (supervisor -> admin).
        DB::table('penalty_removal_requests')
            ->where('status', 'needs_clarification')
            ->update(['status' => 'pending_supervisor_review']);

        // Rename legacy status into the new admin-pending stage so requests don't get stuck.
        DB::table('penalty_removal_requests')
            ->where('status', 'submitted_to_admin')
            ->update(['status' => 'pending_admin_review']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('penalty_removal_requests') || ! Schema::hasColumn('penalty_removal_requests', 'status')) {
            return;
        }

        DB::table('penalty_removal_requests')
            ->where('status', 'pending_supervisor_review')
            ->update(['status' => 'needs_clarification']);

        DB::table('penalty_removal_requests')
            ->where('status', 'pending_admin_review')
            ->update(['status' => 'submitted_to_admin']);
    }
};

