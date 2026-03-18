<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('leave_requests', 'supervisor_id')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('supervisor_id')->nullable()->after('proof_path')
                    ->comment('user_id of department manager / supervisor who must approve first');
            });
        }

        if (! Schema::hasColumn('leave_requests', 'supervisor_approved_at')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->timestamp('supervisor_approved_at')->nullable()->after('supervisor_id');
            });
        }

        if (! Schema::hasColumn('leave_requests', 'supervisor_approved_by')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('supervisor_approved_by')->nullable()->after('supervisor_approved_at');
            });
        }

        $driver = config('database.default');
        $connection = config("database.connections.$driver.driver");
        if ($connection === 'mysql') {
            DB::statement("ALTER TABLE leave_requests MODIFY leave_status ENUM('pending','supervisor_approved','pending_admin','approved','rejected','cancelled') NOT NULL DEFAULT 'pending'");
        }

        // Backfill supervisor_id for existing pending requests from employee's department manager
        $pending = DB::table('leave_requests')->where('leave_status', 'pending')->whereNull('supervisor_id')->pluck('employee_id', 'leave_request_id');
        foreach ($pending as $leaveRequestId => $employeeId) {
            $dept = DB::table('employees')->where('employee_id', $employeeId)->value('department_id');
            $managerId = $dept ? DB::table('departments')->where('department_id', $dept)->value('manager_id') : null;
            if ($managerId) {
                DB::table('leave_requests')->where('leave_request_id', $leaveRequestId)->update(['supervisor_id' => $managerId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (Schema::hasColumn('leave_requests', 'supervisor_id')) {
                $table->dropColumn('supervisor_id');
            }
            if (Schema::hasColumn('leave_requests', 'supervisor_approved_at')) {
                $table->dropColumn('supervisor_approved_at');
            }
            if (Schema::hasColumn('leave_requests', 'supervisor_approved_by')) {
                $table->dropColumn('supervisor_approved_by');
            }
        });
        if (config("database.connections." . config('database.default') . ".driver") === 'mysql') {
            DB::statement("ALTER TABLE leave_requests MODIFY leave_status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending'");
        }
    }
};
