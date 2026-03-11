<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Supervisor sends "approval summary" to admin; admin sees only submitted records.
     * flagged_for_admin = needs admin decision (issue); otherwise "fully approved" by supervisor.
     */
    public function up(): void
    {
        Schema::table('overtime_records', function (Blueprint $table) {
            if (!Schema::hasColumn('overtime_records', 'submitted_to_admin_at')) {
                $table->timestamp('submitted_to_admin_at')->nullable()->after('supervisor_approved_by');
            }
            if (!Schema::hasColumn('overtime_records', 'flagged_for_admin')) {
                $table->boolean('flagged_for_admin')->default(false)->after('submitted_to_admin_at');
            }
            if (!Schema::hasColumn('overtime_records', 'admin_review_remark')) {
                $table->text('admin_review_remark')->nullable()->after('flagged_for_admin');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtime_records', function (Blueprint $table) {
            $columns = ['submitted_to_admin_at', 'flagged_for_admin', 'admin_review_remark'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('overtime_records', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
