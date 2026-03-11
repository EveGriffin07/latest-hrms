<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * final_status: PENDING_SUPERVISOR | PENDING_ADMIN | REJECTED_SUPERVISOR | APPROVED_ADMIN | REJECTED_ADMIN
     * supervisor_decision: APPROVED | REJECTED (set when supervisor acts)
     * issue_flagged_by / issue_flagged_at: who/when "Mark Issue" was set (issue_flag = flagged_for_admin, issue_reason = admin_review_remark)
     * admin_decision / admin_comment / admin_action_at: admin approve/reject
     */
    public function up(): void
    {
        Schema::table('overtime_records', function (Blueprint $table) {
            if (!Schema::hasColumn('overtime_records', 'final_status')) {
                $table->string('final_status', 32)->nullable()->after('ot_status');
            }
            if (!Schema::hasColumn('overtime_records', 'supervisor_decision')) {
                $table->string('supervisor_decision', 32)->nullable()->after('final_status');
            }
            if (!Schema::hasColumn('overtime_records', 'issue_flagged_by')) {
                $table->unsignedBigInteger('issue_flagged_by')->nullable()->after('admin_review_remark');
                $table->foreign('issue_flagged_by')->references('user_id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('overtime_records', 'issue_flagged_at')) {
                $table->timestamp('issue_flagged_at')->nullable()->after('issue_flagged_by');
            }
            if (!Schema::hasColumn('overtime_records', 'admin_decision')) {
                $table->string('admin_decision', 32)->nullable()->after('issue_flagged_at');
            }
            if (!Schema::hasColumn('overtime_records', 'admin_comment')) {
                $table->text('admin_comment')->nullable()->after('admin_decision');
            }
            if (!Schema::hasColumn('overtime_records', 'admin_action_at')) {
                $table->timestamp('admin_action_at')->nullable()->after('admin_comment');
            }
        });

        // Backfill final_status from ot_status / supervisor_approved_by / submitted_to_admin_at / approved_by
        $records = DB::table('overtime_records')->get();
        foreach ($records as $r) {
            $finalStatus = null;
            $supervisorDecision = null;
            $adminDecision = null;
            if ($r->ot_status === 'rejected') {
                $finalStatus = $r->approved_by ? 'REJECTED_ADMIN' : 'REJECTED_SUPERVISOR';
                $supervisorDecision = $r->approved_by ? 'APPROVED' : 'REJECTED';
                if ($r->approved_by) {
                    $adminDecision = 'REJECTED';
                }
            } elseif ($r->ot_status === 'approved') {
                $finalStatus = 'APPROVED_ADMIN';
                $supervisorDecision = 'APPROVED';
                $adminDecision = 'APPROVED';
            } else {
                if ($r->supervisor_approved_by) {
                    $finalStatus = $r->submitted_to_admin_at ? 'PENDING_ADMIN' : 'PENDING_ADMIN';
                    $supervisorDecision = 'APPROVED';
                } else {
                    $finalStatus = 'PENDING_SUPERVISOR';
                }
            }
            DB::table('overtime_records')->where('ot_id', $r->ot_id)->update([
                'final_status' => $finalStatus ?? 'PENDING_SUPERVISOR',
                'supervisor_decision' => $supervisorDecision,
                'admin_decision' => $adminDecision,
            ]);
        }

    }

    public function down(): void
    {
        Schema::table('overtime_records', function (Blueprint $table) {
            if (Schema::hasColumn('overtime_records', 'admin_action_at')) {
                $table->dropColumn('admin_action_at');
            }
            if (Schema::hasColumn('overtime_records', 'admin_comment')) {
                $table->dropColumn('admin_comment');
            }
            if (Schema::hasColumn('overtime_records', 'admin_decision')) {
                $table->dropColumn('admin_decision');
            }
            if (Schema::hasColumn('overtime_records', 'issue_flagged_at')) {
                $table->dropColumn('issue_flagged_at');
            }
            if (Schema::hasColumn('overtime_records', 'issue_flagged_by')) {
                $table->dropForeign(['issue_flagged_by']);
                $table->dropColumn('issue_flagged_by');
            }
            if (Schema::hasColumn('overtime_records', 'supervisor_decision')) {
                $table->dropColumn('supervisor_decision');
            }
            if (Schema::hasColumn('overtime_records', 'final_status')) {
                $table->dropColumn('final_status');
            }
        });
    }
};
