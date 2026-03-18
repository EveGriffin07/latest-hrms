<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix leave_status column to accept supervisor_approved and pending_admin.
     * Run this if you get "Data truncated for column 'leave_status'" when approving leave.
     */
    public function up(): void
    {
        $driver = config('database.default');
        $connection = config("database.connections.$driver.driver");

        if ($connection === 'mysql') {
            // Use VARCHAR so all status values (including supervisor_approved, pending_admin) are accepted
            DB::statement("ALTER TABLE leave_requests MODIFY leave_status VARCHAR(32) NOT NULL DEFAULT 'pending'");
        } else {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->string('leave_status', 32)->default('pending')->change();
            });
        }
    }

    public function down(): void
    {
        $driver = config('database.default');
        $connection = config("database.connections.$driver.driver");

        if ($connection === 'mysql') {
            DB::statement("ALTER TABLE leave_requests MODIFY leave_status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending'");
        }
    }
};
