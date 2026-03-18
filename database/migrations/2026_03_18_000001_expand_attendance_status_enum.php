<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance') || ! Schema::hasColumn('attendance', 'at_status')) {
            return;
        }

        // MySQL ENUM must be altered via raw SQL.
        // We expand to include statuses already used by the app/services.
        DB::statement(
            "ALTER TABLE `attendance` MODIFY `at_status` ENUM(
                'present','absent','late','leave','incomplete','pending','off_day','holiday','early_leave'
            ) NOT NULL"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('attendance') || ! Schema::hasColumn('attendance', 'at_status')) {
            return;
        }

        // Best-effort rollback: map expanded statuses into closest legacy values, then shrink ENUM.
        DB::statement("UPDATE `attendance` SET `at_status`='leave' WHERE `at_status` IN ('off_day','holiday')");
        DB::statement("UPDATE `attendance` SET `at_status`='present' WHERE `at_status` IN ('early_leave')");
        DB::statement("UPDATE `attendance` SET `at_status`='incomplete' WHERE `at_status` IN ('pending')");
        DB::statement("UPDATE `attendance` SET `at_status`='present' WHERE `at_status` NOT IN ('present','absent','late','leave','incomplete')");

        // Original enum did not include incomplete/pending, but shrinking back without data loss is impossible.
        // Keep 'incomplete' to avoid truncation on rollback if data exists.
        DB::statement(
            "ALTER TABLE `attendance` MODIFY `at_status` ENUM(
                'present','absent','late','leave','incomplete'
            ) NOT NULL"
        );
    }
};

