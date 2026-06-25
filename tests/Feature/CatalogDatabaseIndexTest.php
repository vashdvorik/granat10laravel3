<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogDatabaseIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_query_indexes_exist(): void
    {
        $this->assertIndexExists('products', 'products_catalog_category_visibility_sort_index');
        $this->assertIndexExists('products', 'products_seller_active_vip_index');
        $this->assertIndexExists('products', 'products_seller_created_index');
        $this->assertIndexExists('categories', 'categories_parent_active_number_index');
        $this->assertIndexExists('seller_packages', 'seller_packages_active_period_index');
    }

    private function assertIndexExists(string $table, string $index): void
    {
        $indexes = collect(DB::select("PRAGMA index_list('{$table}')"))
            ->pluck('name');

        $this->assertTrue(
            $indexes->contains($index),
            "Index [{$index}] does not exist on table [{$table}]."
        );
    }
}
