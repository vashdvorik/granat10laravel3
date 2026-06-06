<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\VoyagerBreadSecuritySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use TCG\Voyager\Models\DataRow;
use TCG\Voyager\Models\DataType;
use Tests\TestCase;

class VoyagerBreadSecuritySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_protects_user_roles_balance_and_password(): void
    {
        $dataType = DataType::query()->create([
            'name' => 'users',
            'slug' => 'users',
            'display_name_singular' => 'User',
            'display_name_plural' => 'Users',
            'model_name' => 'TCG\\Voyager\\Models\\User',
            'generate_permissions' => 1,
            'server_side' => 0,
        ]);

        foreach ([
            'role_id',
            'user_belongsto_role_relationship',
            'user_belongstomany_role_relationship',
            'balance',
            'password',
        ] as $index => $field) {
            DataRow::query()->create([
                'data_type_id' => $dataType->id,
                'field' => $field,
                'type' => 'text',
                'display_name' => $field,
                'required' => 0,
                'browse' => 1,
                'read' => 1,
                'edit' => 1,
                'add' => 1,
                'delete' => 1,
                'order' => $index + 1,
            ]);
        }

        $this->seed(VoyagerBreadSecuritySeeder::class);

        $this->assertSame(User::class, $dataType->refresh()->model_name);

        foreach ([
            'role_id',
            'user_belongsto_role_relationship',
            'user_belongstomany_role_relationship',
            'balance',
        ] as $field) {
            $row = $dataType->rows()->where('field', $field)->firstOrFail();

            $this->assertFalse((bool) $row->edit);
            $this->assertFalse((bool) $row->add);
            $this->assertFalse((bool) $row->delete);
        }

        $password = $dataType->rows()->where('field', 'password')->firstOrFail();
        $this->assertFalse((bool) $password->edit);
        $this->assertTrue((bool) $password->add);
    }
}
