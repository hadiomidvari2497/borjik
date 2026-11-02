<?php
/*
|--------------------------------------------------------------------------
| Customer support service levels
|--------------------------------------------------------------------------
| Durations are elapsed calendar minutes (24/7), not business-hour minutes.
| Adjust these defaults to match the support team's actual coverage.
*/
return [
    'sla' => [
        'warning_minutes' => 30,
        'priorities' => [
            'urgent' => ['first_response_minutes' => 60, 'resolution_minutes' => 240],
            'high' => ['first_response_minutes' => 240, 'resolution_minutes' => 1440],
            'normal' => ['first_response_minutes' => 480, 'resolution_minutes' => 4320],
            'low' => ['first_response_minutes' => 1440, 'resolution_minutes' => 7200],
        ],
    ],
];
