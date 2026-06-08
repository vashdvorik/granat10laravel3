<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        if (getenv('APP_ENV') === 'testing') {
            $cachedBootstrapFiles = array_merge(
                [__DIR__.'/../bootstrap/cache/config.php'],
                glob(__DIR__.'/../bootstrap/cache/routes-*.php') ?: []
            );

            foreach ($cachedBootstrapFiles as $cachedBootstrapFile) {
                if (is_file($cachedBootstrapFile)) {
                    unlink($cachedBootstrapFile);
                }
            }
        }

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        if ($app->environment('testing') && $app['config']->get('database.default') !== 'sqlite') {
            throw new \RuntimeException('Tests must use the isolated SQLite database.');
        }

        return $app;
    }
}
