<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesTableSeeder::class,
            PermissionsTableSeeder::class,
            PermissionRoleTableSeeder::class,
            AdminRoleSeeder::class,
            RegionsAndSettlementsSeeder::class,
            UsersTableSeeder::class,
            //CategoriesTableSeeder::class,
            CategoriesJsonSeeder::class,
            SellersTableSeeder::class,
            SellerSettlementBackfillSeeder::class,
            ProductsTableSeeder::class,
            ImagesTableSeeder::class,
        ]);
    }
}
