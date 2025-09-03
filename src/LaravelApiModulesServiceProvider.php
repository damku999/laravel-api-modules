<?php

namespace Webmonks\LaravelApiModules;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Webmonks\LaravelApiModules\Support\FileSystemCache;

class LaravelApiModulesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/laravel-api-modules.php' => config_path('laravel-api-modules.php'),
        ], 'laravel-api-modules-config');

        // Publish stubs
        $this->publishes([
            __DIR__ . '/../stubs' => base_path('stubs/laravel-api-modules'),
        ], 'laravel-api-modules-stubs');

        // Always bind commands in the container (so tests can resolve them)
        $this->app->bind(\Webmonks\LaravelApiModules\Commands\MakeModuleCommand::class);
        $this->app->bind(\Webmonks\LaravelApiModules\Commands\MakeSwaggerDocsCommand::class);
        $this->app->bind(\Webmonks\LaravelApiModules\Commands\RemoveModuleCommand::class);

        // Register commands to Artisan only when running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Webmonks\LaravelApiModules\Commands\MakeModuleCommand::class,
                \Webmonks\LaravelApiModules\Commands\MakeSwaggerDocsCommand::class,
                \Webmonks\LaravelApiModules\Commands\RemoveModuleCommand::class,
            ]);
        }

        // Optionally auto-discover module routes
        if (config('laravel-api-modules.auto_discover_routes', true)) {
            $this->loadModuleRoutes();
        }
        $this->autoRegisterRepositoryServiceProvider();

    }

    /**
     * @psalm-suppress MissingOverrideAttribute
     * @psalm-override
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/laravel-api-modules.php',
            'laravel-api-modules'
        );
    }

    /**
     * Load module routes with performance optimization through caching.
     *
     * @return void
     */
    protected function loadModuleRoutes(): void
    {
        $modulesPath = base_path(config('laravel-api-modules.modules_dir', 'app/Modules'));
        if (!is_dir($modulesPath)) {
            return;
        }

        // Performance: Cache route file discovery to reduce filesystem operations
        $cacheKey = 'laravel-api-modules.routes.' . filemtime($modulesPath);
        $routeFiles = Cache::remember($cacheKey, 3600, function () use ($modulesPath) {
            return glob($modulesPath . '/*/routes.php');
        });

        foreach ($routeFiles as $routeFile) {
            if (FileSystemCache::exists($routeFile)) {
                $this->loadRoutesFrom($routeFile);
            }
        }
    }
    /**
     * Auto-register repository service provider with performance optimization.
     *
     * @return void
     */
    protected function autoRegisterRepositoryServiceProvider(): void
    {
        $repoProviderPath = app_path('Core/Providers/RepositoryServiceProvider.php');
        $providerClass = 'App\Core\Providers\RepositoryServiceProvider';

        // Performance: Use cached file existence check
        if (FileSystemCache::exists($repoProviderPath) && class_exists($providerClass)) {
            $this->app->register($providerClass);
        }
    }
}
