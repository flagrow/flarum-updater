<?php

namespace App\Providers;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use LaravelZero\Framework\Application;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->singleton(Client::class, function(Application $app) {
            return new Client([
               'base_uri' => $app->environment() === 'production' ? 'https://flagrow.io/api/' : 'http://flagrow.test/api/',
               'timeout' => 5,
                'allow_redirects' => false,
                'connect_timeout' => 5,
                'headers' => [
                    'User-Agent' => 'Flagrow "flarum-updater"/v1',
                    'Accept' => 'application/vnd.api+json, application/json',
                ]
            ]);
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (file_exists($file = getcwd() . "/config.php") && file_exists(getcwd() . '/flarum')) {
            $config = include $file;

            config(['database.connections.default' => array_get($config, 'database', [])]);
        }
    }
}
