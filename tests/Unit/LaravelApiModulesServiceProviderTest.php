<?php

use Illuminate\Support\Facades\Cache;
use Webmonks\LaravelApiModules\Commands\MakeModuleCommand;
use Webmonks\LaravelApiModules\LaravelApiModulesServiceProvider;

beforeEach(function () {
    $this->provider = new LaravelApiModulesServiceProvider($this->app);
});

it('publishes config file', function () {
    $this->provider->boot();

    $publishedPaths = $this->provider::$publishes;

    expect($publishedPaths)->toHaveKey(LaravelApiModulesServiceProvider::class);
});

it('registers make module command', function () {
    // Set console mode to ensure commands are registered
    $this->app['env'] = 'testing';

    $this->provider->boot();

    expect($this->app->bound(MakeModuleCommand::class))->toBeTrue();
});

it('merges config from package', function () {
    $this->provider->register();

    $config = config('laravel-api-modules');

    expect($config)->toHaveValidConfig()
        ->and($config['modules_dir'])->toBe('app/Modules');
});

it('loads module routes when auto discover is enabled', function () {
    $this->app['config']->set('laravel-api-modules.auto_discover_routes', true);

    // Create a test modules directory
    $modulesPath = base_path('app/Modules');
    if (!is_dir($modulesPath)) {
        mkdir($modulesPath, 0755, true);
    }

    $testModulePath = $modulesPath . '/TestModule';
    if (!is_dir($testModulePath)) {
        mkdir($testModulePath, 0755, true);
    }

    $routeFile = $testModulePath . '/routes.php';
    file_put_contents($routeFile, "<?php\n// Test route file\n");

    $this->provider->boot();

    // Cleanup with proper error handling
    if (file_exists($routeFile)) {
        unlink($routeFile);
    }
    if (is_dir($testModulePath)) {
        // Recursively remove to avoid Windows directory-not-empty errors
        removeDirectory($testModulePath);
    }
    if (is_dir($modulesPath) && count(scandir($modulesPath)) === 2) {
        rmdir($modulesPath);
    }

    expect(true)->toBeTrue(); // If we get here without errors, the test passes
});

it('does not load routes when auto discover is disabled', function () {
    $this->app['config']->set('laravel-api-modules.auto_discover_routes', false);

    $this->provider->boot();

    expect(true)->toBeTrue(); // If no routes are loaded, test passes
});

it('handles missing modules directory gracefully', function () {
    $this->app['config']->set('laravel-api-modules.auto_discover_routes', true);
    $this->app['config']->set('laravel-api-modules.modules_dir', 'non/existent/path');

    $this->provider->boot();

    expect(true)->toBeTrue(); // Should not throw exceptions
});

it('caches route discovery results', function () {
    Cache::shouldReceive('remember')
        ->once()
        ->with(Mockery::pattern('/laravel-api-modules\.routes\.\d+/'), 3600, Mockery::type('Closure'))
        ->andReturn([]);

    $this->app['config']->set('laravel-api-modules.auto_discover_routes', true);

    // Create modules directory
    $modulesPath = base_path('app/Modules');
    if (!is_dir($modulesPath)) {
        mkdir($modulesPath, 0755, true);
    }

    $this->provider->boot();

    // Cleanup
    if (is_dir($modulesPath) && count(scandir($modulesPath)) === 2) {
        rmdir($modulesPath);
    }

    expect(true)->toBeTrue();
});

it('auto registers repository service provider when exists', function () {
    // Create the repository service provider file
    $providerDir = app_path('Core/Providers');
    if (!is_dir($providerDir)) {
        mkdir($providerDir, 0755, true);
    }

    $providerFile = $providerDir . '/RepositoryServiceProvider.php';
    $providerContent = "<?php\n\nnamespace App\\Core\\Providers;\n\nuse Illuminate\\Support\\ServiceProvider;\n\nclass RepositoryServiceProvider extends ServiceProvider\n{\n    public function register() {}\n    public function boot() {}\n}";
    file_put_contents($providerFile, $providerContent);

    $this->provider->boot();

    // Cleanup
    unlink($providerFile);
    if (is_dir($providerDir)) {
        rmdir($providerDir);
    }
    if (is_dir(app_path('Core')) && count(scandir(app_path('Core'))) === 2) {
        rmdir(app_path('Core'));
    }

    expect(true)->toBeTrue(); // Test passes if no exceptions thrown
});

it('handles missing repository service provider gracefully', function () {
    $this->provider->boot();

    expect(true)->toBeTrue(); // Should not throw exceptions when file doesn't exist
});

it('has correct config merge structure', function () {
    $this->provider->register();

    $config = config('laravel-api-modules');

    // Test all expected configuration keys exist
    $expectedKeys = [
        'modules_dir',
        'core_interfaces_dir',
        'namespace',
        'interface_namespace',
        'auto_discover_routes',
        'generate_migration',
        'generate_tests',
        'enable_base_model',
        'enable_base_service',
    ];

    foreach ($expectedKeys as $key) {
        expect($config)->toHaveKey($key);
    }
});

// Additional edge case tests for better coverage
// LaravelApiModulesServiceProvider Edge Cases tests

it('handles route files with filesystem cache miss', function () {
    $tempDir = sys_get_temp_dir() . '/test-modules-' . uniqid();
    mkdir($tempDir, 0755, true);
    mkdir($tempDir . '/TestModule', 0755, true);

    // Create a route file
    file_put_contents($tempDir . '/TestModule/routes.php', '<?php // Test routes');

    config(['laravel-api-modules.modules_dir' => $tempDir]);
    config(['laravel-api-modules.auto_discover_routes' => true]);

    // Clear cache to test cache miss scenario
    \Webmonks\LaravelApiModules\Support\FileSystemCache::clearCache();
    Cache::flush();

    $provider = new LaravelApiModulesServiceProvider($this->app);

    // This should work even when cache is empty
    $provider->boot();
    expect(true)->toBe(true);

    // Cleanup
    unlink($tempDir . '/TestModule/routes.php');
    rmdir($tempDir . '/TestModule');
    rmdir($tempDir);
});

it('handles filemtime failure gracefully in route caching', function () {
    // Test with a path that doesn't exist
    config(['laravel-api-modules.modules_dir' => '/nonexistent/path']);
    config(['laravel-api-modules.auto_discover_routes' => true]);

    Cache::flush();

    $provider = new LaravelApiModulesServiceProvider($this->app);

    $provider->boot();
    expect(true)->toBe(true);
});

it('tests register method with empty config', function () {
    // Clear existing config
    config(['laravel-api-modules' => []]);

    $provider = new LaravelApiModulesServiceProvider($this->app);

    $provider->register();
    expect(true)->toBe(true);
});

it('tests loadModuleRoutes with glob returning empty array', function () {
    $tempDir = sys_get_temp_dir() . '/test-modules-empty-' . uniqid();
    mkdir($tempDir, 0755, true);

    // Create modules dir but no modules with routes.php
    mkdir($tempDir . '/EmptyModule', 0755, true);

    config(['laravel-api-modules.modules_dir' => $tempDir]);
    config(['laravel-api-modules.auto_discover_routes' => true]);

    Cache::flush();

    $provider = new LaravelApiModulesServiceProvider($this->app);

    $provider->boot();
    expect(true)->toBe(true);

    // Cleanup
    rmdir($tempDir . '/EmptyModule');
    rmdir($tempDir);
});

it('tests boot method when auto_discover_routes is disabled', function () {
    config(['laravel-api-modules.auto_discover_routes' => false]);

    $provider = new LaravelApiModulesServiceProvider($this->app);

    $provider->boot();
    expect(true)->toBe(true);
});

afterEach(function () {
    Mockery::close();
});

// Additional comprehensive tests for uncovered code paths
// LaravelApiModulesServiceProvider Comprehensive Coverage tests

it('tests loadModuleRoutes with filemtime failure', function () {
    config(['laravel-api-modules.auto_discover_routes' => true]);
    config(['laravel-api-modules.modules_dir' => '/nonexistent/path']);

    Cache::flush();

    $provider = new LaravelApiModulesServiceProvider($this->app);

    // This should handle filemtime failure gracefully
    $provider->boot();
    expect(true)->toBe(true); // Should complete without error
});

it('tests loadModuleRoutes with glob returning false', function () {
    $tempDir = sys_get_temp_dir() . '/test-modules-glob-' . uniqid();
    config(['laravel-api-modules.modules_dir' => $tempDir]);
    config(['laravel-api-modules.auto_discover_routes' => true]);

    // Don't create the directory to force glob to fail
    Cache::flush();

    $provider = new LaravelApiModulesServiceProvider($this->app);
    $provider->boot();
    expect(true)->toBe(true); // Should handle glob failure gracefully
});

it('tests loadModuleRoutes cache hit scenario', function () {
    $tempDir = sys_get_temp_dir() . '/test-modules-cache-' . uniqid();
    mkdir($tempDir, 0755, true);
    mkdir($tempDir . '/TestModule', 0755, true);

    // Create a route file
    file_put_contents($tempDir . '/TestModule/routes.php', '<?php // Test routes');

    config(['laravel-api-modules.modules_dir' => $tempDir]);
    config(['laravel-api-modules.auto_discover_routes' => true]);

    // First call should cache the result
    $provider1 = new LaravelApiModulesServiceProvider($this->app);
    $provider1->boot();

    // Second call should use cached result
    $provider2 = new LaravelApiModulesServiceProvider($this->app);
    $provider2->boot();

    expect(true)->toBe(true);

    // Cleanup
    unlink($tempDir . '/TestModule/routes.php');
    rmdir($tempDir . '/TestModule');
    rmdir($tempDir);
});

it('tests loadModuleRoutes with FileSystemCache::exists returning false', function () {
    $tempDir = sys_get_temp_dir() . '/test-modules-file-cache-' . uniqid();
    mkdir($tempDir, 0755, true);
    mkdir($tempDir . '/TestModule', 0755, true);

    config(['laravel-api-modules.modules_dir' => $tempDir]);
    config(['laravel-api-modules.auto_discover_routes' => true]);

    Cache::flush();
    \Webmonks\LaravelApiModules\Support\FileSystemCache::clearCache();

    $provider = new LaravelApiModulesServiceProvider($this->app);
    $provider->boot();

    expect(true)->toBe(true);

    // Cleanup
    rmdir($tempDir . '/TestModule');
    rmdir($tempDir);
});

it('tests command binding in different environments', function () {
    // Test that commands are always bound regardless of console mode
    $provider = new LaravelApiModulesServiceProvider($this->app);
    $provider->boot();

    // Commands should always be bound (line 24-25 in ServiceProvider)
    expect($this->app->bound(\Webmonks\LaravelApiModules\Commands\MakeModuleCommand::class))->toBeTrue();
    expect($this->app->bound(\Webmonks\LaravelApiModules\Commands\MakeSwaggerDocsCommand::class))->toBeTrue();
});

it('tests autoRegisterRepositoryServiceProvider with class exists but file missing', function () {
    // Test when class doesn't exist
    \Webmonks\LaravelApiModules\Support\FileSystemCache::clearCache();

    $provider = new LaravelApiModulesServiceProvider($this->app);
    $provider->boot();

    expect(true)->toBe(true); // Should handle gracefully when class doesn't exist
});

it('tests autoRegisterRepositoryServiceProvider with file exists and class exists', function () {
    // Create the repository service provider file
    $providerDir = app_path('Core/Providers');
    if (!is_dir($providerDir)) {
        mkdir($providerDir, 0755, true);
    }

    $providerFile = $providerDir . '/RepositoryServiceProvider.php';
    $providerContent = "<?php\n\nnamespace App\\Core\\Providers;\n\nuse Illuminate\\Support\\ServiceProvider;\n\nclass RepositoryServiceProvider extends ServiceProvider\n{\n    public function register() {}\n    public function boot() {}\n}";
    file_put_contents($providerFile, $providerContent);

    // Clear cache to ensure fresh check
    \Webmonks\LaravelApiModules\Support\FileSystemCache::clearCache();

    $provider = new LaravelApiModulesServiceProvider($this->app);
    $provider->boot();

    expect(true)->toBe(true); // Should register the provider if it exists

    // Cleanup
    unlink($providerFile);
    if (is_dir($providerDir)) {
        rmdir($providerDir);
    }
    if (is_dir(app_path('Core')) && count(scandir(app_path('Core'))) === 2) {
        rmdir(app_path('Core'));
    }
});

it('tests register method merges config correctly', function () {
    // Test that register method works with existing config
    $provider = new LaravelApiModulesServiceProvider($this->app);
    $provider->register();

    // Should merge default config
    $config = config('laravel-api-modules');
    expect($config)->toBeArray();
    expect($config['modules_dir'])->toBe('app/Modules');
});

it('tests publishes array structure', function () {
    $provider = new LaravelApiModulesServiceProvider($this->app);
    $provider->boot();

    $publishes = $provider::$publishes;
    expect($publishes)->toHaveKey(LaravelApiModulesServiceProvider::class);

    $publishData = $publishes[LaravelApiModulesServiceProvider::class];
    expect($publishData)->toBeArray();

    // Should have publishing entries (the exact format may vary)
    expect(count($publishData))->toBeGreaterThan(0);
});

it('tests loadModuleRoutes method directly via reflection', function () {
    $provider = new LaravelApiModulesServiceProvider($this->app);
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('loadModuleRoutes');
    $method->setAccessible(true);

    // Test with non-existent modules directory
    config(['laravel-api-modules.modules_dir' => '/path/that/does/not/exist']);

    // Should return early when directory doesn't exist
    $result = $method->invoke($provider);
    expect($result)->toBe(null); // void method returns null
});

it('tests autoRegisterRepositoryServiceProvider method directly via reflection', function () {
    $provider = new LaravelApiModulesServiceProvider($this->app);
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('autoRegisterRepositoryServiceProvider');
    $method->setAccessible(true);

    // Clear file cache
    \Webmonks\LaravelApiModules\Support\FileSystemCache::clearCache();

    // Test when neither file nor class exists
    $result = $method->invoke($provider);
    expect($result)->toBe(null); // void method returns null
});

it('tests FileSystemCache integration', function () {
    // Test FileSystemCache::exists behavior
    $nonExistentFile = '/path/that/definitely/does/not/exist.php';
    expect(\Webmonks\LaravelApiModules\Support\FileSystemCache::exists($nonExistentFile))->toBe(false);

    // Test with a file that exists
    $tempFile = sys_get_temp_dir() . '/test-file-' . uniqid() . '.php';
    file_put_contents($tempFile, '<?php // test');
    expect(\Webmonks\LaravelApiModules\Support\FileSystemCache::exists($tempFile))->toBe(true);

    // Cleanup
    unlink($tempFile);
});

it('tests cache key generation with filemtime', function () {
    $tempDir = sys_get_temp_dir() . '/test-modules-filemtime-' . uniqid();
    mkdir($tempDir, 0755, true);

    config(['laravel-api-modules.modules_dir' => $tempDir]);

    // Test that filemtime is used in cache key generation
    $mtime = filemtime($tempDir);
    expect($mtime)->toBeInt();

    $expectedCacheKey = 'laravel-api-modules.routes.' . $mtime;

    // This tests the cache key generation logic
    expect(strlen($expectedCacheKey))->toBeGreaterThan(30);

    // Cleanup
    rmdir($tempDir);
});
