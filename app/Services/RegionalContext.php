<?php

namespace App\Services;

use App\Models\Region;
use App\Models\Settlement;
use Illuminate\Support\Collection;
use Throwable;

class RegionalContext
{
    private ?Settlement $settlement = null;

    /** Список регионов запоминается на время запроса: шапка и другие шаблоны просят его по нескольку раз. */
    private ?Collection $availableRegions = null;

    private ?object $availableRegionsFor = null;

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
        $request = app()->bound('request') ? app('request') : null;

        if ($this->availableRegions !== null && $this->availableRegionsFor === $request) {
            return $this->availableRegions;
        }

        try {
            $inspector = app(SchemaInspector::class);

            if (! $inspector->hasTable('regions') || ! $inspector->hasTable('settlements')) {
                return collect();
            }

            $regions = Region::query()
                ->active()
                ->ordered()
                ->with(['settlements' => fn ($query) => $query->active()->ordered()])
                ->get();
        } catch (Throwable) {
            return collect();
        }

        $this->availableRegions = $regions;
        $this->availableRegionsFor = $request;

        return $regions;
    }
}
