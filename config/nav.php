<?php

return [
    'ot_claims' => [
        'items' => [
            [
                'title' => 'Overtime Records',
                'route' => 'employee.attendance.overtime',
                'roles' => ['employee', 'supervisor'],
            ],
            [
                'title' => 'My OT Claims',
                'route' => 'employee.ot_claims.index',
                'roles' => ['employee', 'supervisor'],
            ],
            [
                'title' => 'OT Requests',
                'route' => 'employee.overtime_requests.index',
                'roles' => ['supervisor'],
            ],
            [
                'title' => 'OT Claims Inbox',
                'route' => 'employee.overtime_inbox.index',
                'roles' => ['supervisor'],
            ],
        ],
    ],
];

