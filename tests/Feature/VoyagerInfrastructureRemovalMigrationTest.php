<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VoyagerInfrastructureRemovalMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_removes_only_voyager_infrastructure_tables(): void
    {
        foreach ($this->voyagerTables() as $table) {
            Schema::create($table, function (Blueprint $blueprint): void {
                $blueprint->id();
            });
        }

        $migration = require database_path(
            'migrations/2026_06_08_000001_drop_voyager_infrastructure_tables.php'
        );
        $migration->up();

        foreach ($this->voyagerTables() as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }

        $this->assertTrue(Schema::hasTable('roles'));
        $this->assertTrue(Schema::hasTable('permissions'));
        $this->assertTrue(Schema::hasTable('users'));
    }

    private function voyagerTables(): array
    {
        return [
            'data_rows',
            'data_types',
            'menu_items',
            'menus',
            'settings',
            'translations',
        ];
    }
}
