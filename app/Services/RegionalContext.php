<?php

namespace App\Services;

use App\Models\Region;
use App\Models\Settlement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

class RegionalContext
{
    private ?Settlement $settlement = null;

    public function setSettlement(?Settlement $settlement): void
    {
        $this->settlement = $settlement;
    }

    public function settlement(): ?Settlement
    {
        return $this->settlement;
    }

    public function region(): ?Region
    {
        return $this->settlement?->region;
    }

    public function isAllRegions(): bool
    {
        return $this->settlement === null;
    }

    public function label(): string
    {
        return $this->settlement?->name ?? 'Все регионы';
    }

    public function availableRegions(): Collection
    {
        try {
            if (! Schema::hasTable('regions') || ! Schema::hasTable('settlements')) {
                return collect();
            }

            return Region::query()
                ->active()
                ->ordered()
                ->with(['settlements' => fn ($query) => $query->active()->ordered()])
                ->get();
        } catch (Throwable) {
            return collect();
        }
    }
}
