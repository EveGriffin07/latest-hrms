<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\OvertimeClaim;

class OtClaimNotifier
{
    public static function onSubmitted(OvertimeClaim $claim): void
    {
        $supervisorId = $claim->employee->supervisor_id;
        if ($supervisorId) {
            AppNotification::notify(
                $supervisorId,
                'ot_claim_submitted',
                'OT claim submitted',
                $claim->employee->user->name ?? 'Employee' . ' submitted an OT claim for ' . $claim->date->format('Y-m-d') . ' (' . $claim->hours . 'h).',
                ['claim_id' => $claim->id]
            );
        }
    }

    public static function onSupervisorApproved(OvertimeClaim $claim): void
    {
        $empUserId = $claim->employee->user_id ?? null;
        if ($empUserId) {
            AppNotification::notify($empUserId, 'ot_claim_supervisor_approved', 'OT claim approved by supervisor', 'Your OT claim has been approved by your supervisor and is pending admin.', ['claim_id' => $claim->id]);
        }
        // Notify admins: get all admin user_ids (simple: role = admin)
        $adminIds = \App\Models\User::where('role', 'admin')->pluck('user_id');
        foreach ($adminIds as $uid) {
            AppNotification::notify($uid, 'ot_claim_pending_admin', 'OT claim pending your approval', 'A supervisor-approved OT claim is waiting for your action.', ['claim_id' => $claim->id]);
        }
    }

    public static function onSupervisorRejected(OvertimeClaim $claim): void
    {
        $empUserId = $claim->employee->user_id ?? null;
        if ($empUserId) {
            AppNotification::notify($empUserId, 'ot_claim_supervisor_rejected', 'OT claim rejected', 'Your supervisor has rejected your OT claim.', ['claim_id' => $claim->id, 'remark' => $claim->supervisor_remark]);
        }
    }

    public static function onSupervisorReturned(OvertimeClaim $claim): void
    {
        $empUserId = $claim->employee->user_id ?? null;
        if ($empUserId) {
            AppNotification::notify($empUserId, 'ot_claim_supervisor_returned', 'OT claim returned for changes', 'Your supervisor has returned your OT claim. Please update and resubmit.', ['claim_id' => $claim->id, 'remark' => $claim->supervisor_remark]);
        }
    }

    public static function onAdminApproved(OvertimeClaim $claim): void
    {
        $empUserId = $claim->employee->user_id ?? null;
        if ($empUserId) {
            AppNotification::notify($empUserId, 'ot_claim_admin_approved', 'OT claim approved', 'Your OT claim has been finally approved and will be included in payroll.', ['claim_id' => $claim->id]);
        }
        if ($claim->supervisor_id) {
            AppNotification::notify($claim->supervisor_id, 'ot_claim_admin_approved', 'OT claim approved', 'An OT claim you approved has been finally approved by admin.', ['claim_id' => $claim->id]);
        }
    }

    public static function onAdminRejected(OvertimeClaim $claim): void
    {
        $empUserId = $claim->employee->user_id ?? null;
        if ($empUserId) {
            AppNotification::notify($empUserId, 'ot_claim_admin_rejected', 'OT claim rejected', 'Admin has rejected your OT claim.', ['claim_id' => $claim->id, 'remark' => $claim->admin_remark]);
        }
        if ($claim->supervisor_id) {
            AppNotification::notify($claim->supervisor_id, 'ot_claim_admin_rejected', 'OT claim rejected by admin', 'An OT claim you approved has been rejected by admin.', ['claim_id' => $claim->id]);
        }
    }

    public static function onAdminOnHold(OvertimeClaim $claim): void
    {
        $empUserId = $claim->employee->user_id ?? null;
        if ($empUserId) {
            AppNotification::notify($empUserId, 'ot_claim_admin_on_hold', 'OT claim on hold', 'Admin has put your OT claim on hold. ' . ($claim->admin_remark ?: ''), ['claim_id' => $claim->id]);
        }
        if ($claim->supervisor_id) {
            AppNotification::notify($claim->supervisor_id, 'ot_claim_admin_on_hold', 'OT claim on hold', 'An OT claim is on hold; clarification may be needed.', ['claim_id' => $claim->id]);
        }
    }
}
