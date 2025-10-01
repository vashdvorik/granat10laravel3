<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultRoleId = DB::table('roles')->where('name', 'user')->value('id');

        if (!$defaultRoleId) {
            return;
        }

        $users = [
            [
                'name' => 'Seller 1',
                'email' => 'seller1@example.com',
                'password' => 'password',
            ],
            [
                'name' => 'Seller 2',
                'email' => 'seller2@example.com',
                'password' => 'password',
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'role_id' => $defaultRoleId,
                ]
            );
        }
    }
}
