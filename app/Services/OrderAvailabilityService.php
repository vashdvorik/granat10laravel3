<?php

namespace App\Services;

use App\Data\OrderAvailability;
use App\Enums\ProductModerationStatus;
use App\Models\Product;

class OrderAvailabilityService
{
    public function check(Product $product): OrderAvailability
    {
        $product->loadMissing(['seller.settlement']);
        $seller = $product->seller;

        if ($seller === null) {
            return new OrderAvailability(false, false, 'Продавец товара недоступен.');
        }

        if (
            ! $product->is_active
            || ! $product->can_order
            || $product->moderation_status !== ProductModerationStatus::Approved
            || $product->moderated_version !== $product->moderation_version
        ) {
            return new OrderAvailability(false, false, 'Товар недоступен для заказа.');
        }

        if (! $seller->can_order) {
            return new OrderAvailability(false, false, 'Магазин временно не принимает заказы.');
        }

        $sellerPackage = $seller->activePackage();

        if ($sellerPackage === null || ! $sellerPackage->package->has_order_system) {
            return new OrderAvailability(false, false, 'Тариф магазина не поддерживает заказы.');
        }

        $selectedSettlement = app(RegionalContext::class)->settlement();

        if (
            $selectedSettlement !== null
            && (int) $seller->settlement_id !== (int) $selectedSettlement->id
        ) {
            return new OrderAvailability(false, false, 'Товар относится к другому городу.');
        }

        return new OrderAvailability(
            allowed: true,
            deliveryAllowed: $seller->delivery_enabled && $product->delivery_enabled,
        );
    }
}
