<?php

namespace App\Services;

use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AdminPackageService
{
    public function delete(User $actor, Package $package): void
    {
        Gate::forUser($actor)->authorize('delete', $package);

        DB::transaction(function () use ($actor, $package): void {
            $lockedPackage = Package::query()
                ->lockForUpdate()
                ->findOrFail($package->id);

            if ($lockedPackage->sellerPackages()->exists()) {
                throw ValidationException::withMessages([
                    'package' => 'Нельзя удалить тариф, по которому существует история подписок. Отключите тариф вместо удаления.',
                ]);
            }

            activity('packages')
                ->causedBy($actor)
                ->performedOn($lockedPackage)
                ->withProperties([
                    'package_id' => $lockedPackage->id,
                    'package_name' => $lockedPackage->name,
                ])
                ->log('Административное удаление тарифа');

            $lockedPackage->delete();
        });
    }
}
