<?php

namespace App\Console\Commands;

use App\Services\ProductVipService;
use Illuminate\Console\Command;

class ExpireProductVip extends Command
{
    protected $signature = 'products:expire-vip';

    protected $description = 'Снять VIP с товаров, у которых закончился оплаченный срок';

    public function handle(ProductVipService $vipService): int
    {
        $expired = $vipService->expireDue();
        $warned = $vipService->notifyExpiringSoon();

        $this->info("Снят VIP с товаров: {$expired}. Предупреждений об окончании: {$warned}");

        return self::SUCCESS;
    }
}
