<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Resync employees.employee_id AUTO_INCREMENT so the next insert never duplicates an existing PK.
     */
    public function up(): void
    {
        $max = DB::table('employees')->max('employee_id');
        $next = $max ? (int) $max + 1 : 1;
        DB::statement('ALTER TABLE employees AUTO_INCREMENT = ' . (int) $next);
    }

    public function down(): void
    {
        // No-op
    }
};
