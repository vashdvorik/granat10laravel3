<?php

namespace App\Console\Commands;

use App\Services\OrderAnonymizationService;
use Illuminate\Console\Command;

class AnonymizeExpiredOrders extends Command
{
    protected $signature = 'orders:anonymize-expired
        {--limit= : Максимальное количество заказов за один запуск}
        {--dry-run : Только показать количество без изменений}';

    protected $description = 'Обезличивает персональные данные завершённых и отменённых заказов';

    public function handle(OrderAnonymizationService $anonymization): int
    {
        $limit = $this->option('limit');
        $limit = $limit !== null
            ? max(1, (int) $limit)
            : (int) config('privacy.anonymization_batch_size', 200);

        if ($this->option('dry-run')) {
            $count = $anonymization->eligibleQuery()->limit($limit)->count();
            $this->info("Будет обезличено заказов: {$count}");

            return self::SUCCESS;
        }

        $count = $anonymization->anonymizeExpired($limit);
        $this->info("Обезличено заказов: {$count}");

        return self::SUCCESS;
    }
}
