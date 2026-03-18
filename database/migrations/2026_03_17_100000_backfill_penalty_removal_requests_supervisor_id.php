<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill penalty_removal_requests.supervisor_id with users.user_id
     * (in case it was stored as employee_id or null; supervisor inbox filters by Auth::id()).
     */
    public function up(): void
    {
        $rows = DB::table('penalty_removal_requests')
            ->join('employees', 'penalty_removal_requests.employee_id', '=', 'employees.employee_id')
            ->leftJoin('employees as sup', 'employees.supervisor_id', '=', 'sup.employee_id')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.department_id')
            ->select(
                'penalty_removal_requests.id',
                'sup.user_id as supervisor_user_id',
                'departments.manager_id as dept_manager_id'
            )
            ->get();

        foreach ($rows as $row) {
            $userId = $row->supervisor_user_id ?? $row->dept_manager_id;
            if ($userId !== null) {
                DB::table('penalty_removal_requests')
                    ->where('id', $row->id)
                    ->update(['supervisor_id' => $userId]);
            }
        }
    }

    public function down(): void
    {
        // No reversible backfill
    }
};
