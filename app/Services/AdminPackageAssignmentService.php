<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AdminPackageAssignmentService
{
    public function __construct(private readonly PackageService $packageService) {}

    public function assign(
        User $actor,
        Seller $seller,
        Package $package,
        bool $chargeBalance,
        string $reason
    ): SellerPackage {
        Gate::forUser($actor)->authorize('create', SellerPackage::class);
        $reason = $this->validatedReason($reason);

        return DB::transaction(function () use (
            $actor,
            $seller,
            $package,
            $chargeBalance,
            $reason
        ): SellerPackage {
            $seller->loadMissing('user');

            if (! $seller->user) {
                throw ValidationException::withMessages([
                    'seller_id' => 'У продавца отсутствует пользователь для оплаты тарифа.',
                ]);
            }

            $balanceBefore = $seller->user->balance;
            $sellerPackage = $chargeBalance
                ? $this->packageService->purchase($seller->user, $seller, $package)
                : $this->packageService->assignWithoutCharge($seller->user, $seller, $package);

            $this->log(
                actor: $actor,
                sellerPackage: $sellerPackage,
                action: 'assign',
                chargeBalance: $chargeBalance,
                reason: $reason,
                balanceBefore: $balanceBefore,
            );

            return $sellerPackage->refresh();
        });
    }

    public function renew(
        User $actor,
        SellerPackage $sellerPackage,
        bool $chargeBalance,
        string $reason
    ): SellerPackage {
        Gate::forUser($actor)->authorize('update', $sellerPackage);
        $reason = $this->validatedReason($reason);

        return DB::transaction(function () use (
            $actor,
            $sellerPackage,
            $chargeBalance,
            $reason
        ): SellerPackage {
            $sellerPackage->loadMissing('seller.user');
            $balanceBefore = $sellerPackage->seller->user?->balance;

            $renewed = $chargeBalance
                ? $this->packageService->renew($sellerPackage)
                : $this->packageService->renewWithoutCharge($sellerPackage);

            $this->log(
                actor: $actor,
                sellerPackage: $renewed,
                action: 'renew',
                chargeBalance: $chargeBalance,
                reason: $reason,
                balanceBefore: $balanceBefore,
            );

            return $renewed->refresh();
        });
    }

    public function deactivate(User $actor, SellerPackage $sellerPackage, string $reason): SellerPackage
    {
        Gate::forUser($actor)->authorize('update', $sellerPackage);
        $reason = $this->validatedReason($reason);

        return DB::transaction(function () use ($actor, $sellerPackage, $reason): SellerPackage {
            $lockedSellerPackage = SellerPackage::query()
                ->with(['seller', 'package'])
                ->lockForUpdate()
                ->findOrFail($sellerPackage->id);

            $this->packageService->deactivateExpired($lockedSellerPackage);

            $this->log(
                actor: $actor,
                sellerPackage: $lockedSellerPackage,
                action: 'deactivate',
                chargeBalance: false,
                reason: $reason,
                balanceBefore: null,
            );

            return $lockedSellerPackage->refresh();
        });
    }

    private function validatedReason(string $reason): string
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Укажите причину административной операции.',
            ]);
        }

        return $reason;
    }

    private function log(
        User $actor,
        SellerPackage $sellerPackage,
        string $action,
        bool $chargeBalance,
        string $reason,
        mixed $balanceBefore
    ): void {
        $sellerPackage->loadMissing(['seller.user', 'package']);

        activity('packages')
            ->causedBy($actor)
            ->performedOn($sellerPackage)
            ->withProperties([
                'action' => $action,
                'seller_id' => $sellerPackage->seller_id,
                'seller_name' => $sellerPackage->seller->name,
                'package_id' => $sellerPackage->package_id,
                'package_name' => $sellerPackage->package->name,
                'charge_balance' => $chargeBalance,
                'balance_before' => $balanceBefore,
                'balance_after' => $sellerPackage->seller->user?->fresh()->balance,
                'reason' => $reason,
            ])
            ->log(match ($action) {
                'assign' => 'Административное назначение тарифа',
                'renew' => 'Административное продление тарифа',
                default => 'Административная деактивация тарифа',
            });
    }
}
