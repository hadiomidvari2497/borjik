<?php

return [
    'host' => env('MAILBOX_HOST', ''),
    'port' => (int) env('MAILBOX_PORT', 993),
    'username' => env('MAILBOX_USERNAME', ''),
    'password' => env('MAILBOX_PASSWORD', ''),
    'timeout' => (int) env('MAILBOX_TIMEOUT', 12),
];
