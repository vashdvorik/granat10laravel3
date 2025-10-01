<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure admin role exists
        DB::table('roles')->updateOrInsert(
            ['name' => 'admin'],
            [
                'display_name' => 'Administrator',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $roleId = DB::table('roles')->where('name', 'admin')->value('id');
        if (!$roleId) {
            return;
        }

        // Ensure browse_admin permission exists
        DB::table('permissions')->updateOrInsert(
            ['key' => 'browse_admin', 'table_name' => null],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $permissionId = DB::table('permissions')->where('key', 'browse_admin')->value('id');
        if ($permissionId) {
            DB::table('permission_role')->updateOrInsert(
                ['permission_id' => $permissionId, 'role_id' => $roleId],
                []
            );
        }

        // Ensure desired user exists and has admin role
        $user = DB::table('users')->where('email', 'kinokaef@gmail.com')->first();

        if ($user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'role_id' => $roleId,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('users')->insert([
                'name' => 'Admin',
                'email' => 'kinokaef@gmail.com',
                'password' => Hash::make('password'),
                'role_id' => $roleId,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
