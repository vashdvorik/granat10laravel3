<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SellerSettlementBackfillSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sellers')
            ->whereNull('settlement_id')
            ->whereNotNull('town')
            ->orderBy('id')
            ->eachById(function (object $seller): void {
                $settlementId = DB::table('settlements')
                    ->whereRaw('LOWER(name) = LOWER(?)', [trim($seller->town)])
                    ->value('id');

                if ($settlementId) {
                    DB::table('sellers')
                        ->where('id', $seller->id)
                        ->update([
                            'settlement_id' => $settlementId,
                            'updated_at' => now(),
                        ]);
                }
            });
    }
}
