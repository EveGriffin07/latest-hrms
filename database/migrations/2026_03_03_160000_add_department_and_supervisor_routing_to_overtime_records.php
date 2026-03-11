<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * OT routing: snapshot department_id and supervisor_id (from department.manager_id) when employee submits.
     * Supervisor list filters by supervisor_id = auth()->id().
     */
    public function up(): void
    {
        Schema::table('overtime_records', function (Blueprint $table) {
            if (!Schema::hasColumn('overtime_records', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('employee_id');
                $table->foreign('department_id')->references('department_id')->on('departments')->onDelete('set null');
            }
            if (!Schema::hasColumn('overtime_records', 'supervisor_id')) {
                $table->unsignedBigInteger('supervisor_id')->nullable()->after('department_id');
                $table->foreign('supervisor_id')->references('user_id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('overtime_records', 'supervisor_action_at')) {
                $table->timestamp('supervisor_action_at')->nullable()->after('supervisor_approved_by');
            }
        });

        // Backfill: set department_id and supervisor_id from employee (department.manager_id or employee.supervisor_id)
        $records = DB::table('overtime_records')->get();
        foreach ($records as $r) {
            $emp = DB::table('employees')->where('employee_id', $r->employee_id)->first();
            if (!$emp) {
                continue;
            }
            $deptId = $emp->department_id ?? null;
            $supervisorId = null;
            if ($deptId) {
                $dept = DB::table('departments')->where('department_id', $deptId)->first();
                $supervisorId = $dept->manager_id ?? null;
            }
            if (!$supervisorId) {
                $supervisorId = $emp->supervisor_id ?? null;
            }
            DB::table('overtime_records')
                ->where('ot_id', $r->ot_id)
                ->update([
                    'department_id' => $deptId,
                    'supervisor_id' => $supervisorId,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtime_records', function (Blueprint $table) {
            if (Schema::hasColumn('overtime_records', 'supervisor_action_at')) {
                $table->dropColumn('supervisor_action_at');
            }
            if (Schema::hasColumn('overtime_records', 'supervisor_id')) {
                $table->dropForeign(['supervisor_id']);
                $table->dropColumn('supervisor_id');
            }
            if (Schema::hasColumn('overtime_records', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            }
        });
    }
};
