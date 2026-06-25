<?php

return [
    'worker_heartbeat_key' => 'health:queue-worker:moderation',
    'worker_heartbeat_max_age' => (int) env('HEALTH_WORKER_MAX_AGE', 180),
];
