<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;

class OtClaimApproverResolver
{
    /**
     * Resolve the approver (user_id) for an OT claim.
     * 1. If routing_area_id is set (override), use that area's supervisor.
     * 2. Else use the employee's department manager.
     * 3. If no manager, route to first ADMIN role user so the claim doesn't get lost.
     */
    public static function resolve(Employee $employee, ?int $routingAreaId = null): ?int
    {
        if ($routingAreaId) {
            $area = Area::find($routingAreaId);
            if ($area && $area->supervisor_id) {
                return (int) $area->supervisor_id;
            }
        }

        $dept = $employee->department;
        if ($dept && $dept->manager_id) {
            return (int) $dept->manager_id;
        }

        $admin = User::whereIn('role', ['admin', 'administrator', 'hr', 'manager'])->orderBy('user_id')->first();
        return $admin ? (int) $admin->user_id : null;
    }
}
