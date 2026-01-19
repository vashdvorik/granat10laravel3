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
            VoyagerDatabaseSeeder::class,
            AdminRoleSeeder::class,
            UsersTableSeeder::class,
            //CategoriesTableSeeder::class,
            CategoriesJsonSeeder::class,
            PackagesTableSeeder::class,
            SellersTableSeeder::class,
            ProductsTableSeeder::class,
            ImagesTableSeeder::class,
            SellerPackagesTableSeeder::class,
            CategoriesJsonSeeder::class
        ]);
    }
}
