<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use TCG\Voyager\Models\DataType;

class VoyagerBreadSecuritySeeder extends Seeder
{
    public function run(): void
    {
        $userDataType = DataType::query()->where('slug', 'users')->first();

        if (!$userDataType) {
            return;
        }

        $userDataType->update([
            'model_name' => User::class,
        ]);

        $userDataType->rows()
            ->whereIn('field', [
                'role_id',
                'user_belongsto_role_relationship',
                'user_belongstomany_role_relationship',
            ])
            ->update([
                'edit' => 0,
                'add' => 0,
                'delete' => 0,
            ]);

        $userDataType->rows()
            ->where('field', 'balance')
            ->update([
                'edit' => 0,
                'add' => 0,
                'delete' => 0,
            ]);

        $userDataType->rows()
            ->where('field', 'password')
            ->update([
                'edit' => 0,
            ]);
    }
}
