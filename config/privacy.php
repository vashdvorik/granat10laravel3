<?php

return [
    'order_retention_days' => (int) env('ORDER_PERSONAL_DATA_RETENTION_DAYS', 365),
    'anonymization_batch_size' => (int) env('ORDER_ANONYMIZATION_BATCH_SIZE', 200),
];
