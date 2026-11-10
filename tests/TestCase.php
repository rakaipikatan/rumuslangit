<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Abaikan config cache produksi (bootstrap/cache/config.php) selama tes, supaya
     * APP_ENV=testing berlaku (CSRF dilewati) dan `php artisan test` tidak memakai config live.
     */
    public function createApplication()
    {
        $_SERVER['APP_CONFIG_CACHE'] = $_ENV['APP_CONFIG_CACHE'] = dirname(__DIR__) . '/storage/framework/testing/config-tidak-ada.php';
        putenv('APP_CONFIG_CACHE=' . $_SERVER['APP_CONFIG_CACHE']);

        // Rute juga jangan dibaca dari cache (bisa usang terhadap routes/web.php).
        $_SERVER['APP_ROUTES_CACHE'] = $_ENV['APP_ROUTES_CACHE'] = dirname(__DIR__) . '/storage/framework/testing/routes-tidak-ada.php';
        putenv('APP_ROUTES_CACHE=' . $_SERVER['APP_ROUTES_CACHE']);

        return parent::createApplication();
    }
}
