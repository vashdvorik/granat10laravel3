<?php

namespace App\Services;

use App\Models\Seller;

class SellerSettingsService
{
    public function updateAutoRenew(Seller $seller, bool $autoRenew): Seller
    {
        $seller->update([
            'auto_renew' => $autoRenew,
        ]);

        return $seller->refresh();
    }
}
