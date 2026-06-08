<?php

namespace Tests\Feature;

use Tests\TestCase;

class VoyagerRemovalBootTest extends TestCase
{
    public function test_application_boots_without_voyager(): void
    {
        $this->assertFalse(class_exists(\TCG\Voyager\VoyagerServiceProvider::class));
        $this->assertFalse(class_exists(\TCG\Voyager\Facades\Voyager::class));
        $this->assertFalse(config()->has('voyager'));
    }

    public function test_voyager_routes_are_absent(): void
    {
        $router = app('router');

        $this->assertFalse($router->has('voyager.dashboard'));
        $this->assertFalse($router->has('voyager.media.index'));
        $this->assertFalse($router->has('voyager.settings.index'));
        $this->assertFalse($router->has('voyager.bread.index'));
    }

    public function test_filament_admin_route_remains_available(): void
    {
        $this->assertTrue(app('router')->has('filament.control.pages.dashboard'));
        $this->assertSame(
            'admin',
            app('router')->getRoutes()
                ->getByName('filament.control.pages.dashboard')
                ?->uri()
        );
    }
}
