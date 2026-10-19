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

    /** Город, который посетитель выбирал раньше (cookie); читается один раз и только по требованию. */
    private ?Settlement $suggested = null;

    private bool $suggestionResolved = false;

    public function setSettlement(?Settlement $settlement): void
    {
        $this->settlement = $settlement;
    }

    /**
     * Город, который посетитель выбирал в прошлый раз. Показывается только подсказкой на главной
     * («Показать только Тирасполь?») и никогда не включает фильтр сам: город применяется только по адресу.
     */
    public function suggestedSettlement(): ?Settlement
    {
        if ($this->settlement !== null || ! config('regionality.enabled') || ! config('regionality.urls_enabled')) {
            return null;
        }

        if ($this->suggestionResolved) {
            return $this->suggested;
        }

        $this->suggestionResolved = true;
        $settlementId = app()->bound('request') ? app('request')->cookie(config('regionality.cookie_name')) : null;

        if (! $settlementId) {
            return null;
        }

        try {
            $settlement = Settlement::query()->active()->with('region')->find($settlementId);
        } catch (Throwable) {
            return null;
        }

        return $this->suggested = ($settlement && $settlement->region?->is_active) ? $settlement : null;
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
