<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthorizationSchemaMigrationTest extends TestCase
{
    public function test_application_migrations_create_authorization_schema_without_voyager_migrations(): void
    {
        $originalConnection = config('database.default');

        config()->set('database.connections.authorization_schema', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('authorization_schema');
        DB::setDefaultConnection('authorization_schema');

        try {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });

            $this->runAuthorizationMigrations();

            $this->assertTrue(Schema::hasTable('roles'));
            $this->assertTrue(Schema::hasTable('permissions'));
            $this->assertTrue(Schema::hasTable('permission_role'));
            $this->assertTrue(Schema::hasTable('user_roles'));
            $this->assertTrue(Schema::hasColumns('users', ['role_id', 'avatar', 'settings']));
        } finally {
            DB::purge('authorization_schema');
            DB::setDefaultConnection($originalConnection);
        }
    }

    private function runAuthorizationMigrations(): void
    {
        $migrationFiles = [
            '2026_06_07_000001_create_authorization_tables.php',
            '2026_06_07_000002_add_authorization_fields_to_users_table.php',
            '2026_06_07_000003_create_authorization_pivot_tables.php',
        ];

        foreach ($migrationFiles as $migrationFile) {
            $migration = require database_path("migrations/{$migrationFile}");
            $migration->up();
        }
    }
}
