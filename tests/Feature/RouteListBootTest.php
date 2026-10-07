<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RouteListBootTest extends TestCase
{
    public function test_route_list_does_not_require_database_connection(): void
    {
        $originalDefaultConnection = Config::get('database.default');

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '1');
        DB::purge('mysql');

        try {
            $exitCode = Artisan::call('route:list', ['--path' => 'catalog']);

            $this->assertSame(0, $exitCode);
        } finally {
            Config::set('database.default', $originalDefaultConnection);
            DB::purge('mysql');
        }
    }

    public function test_routes_can_be_cached(): void
    {
        $this->assertSame(0, Artisan::call('route:cache'));

        Artisan::call('route:clear');
    }
}
