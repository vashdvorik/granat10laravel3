<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Auto generated seed file.
     */
    public function run()
    {
        Permission::firstOrCreate([
            'key' => 'browse_admin',
            'table_name' => null,
        ]);
    }
}
