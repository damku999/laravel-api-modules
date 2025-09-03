<?php

namespace Webmonks\LaravelApiModules\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Webmonks\LaravelApiModules\LaravelApiModulesServiceProvider;
use Webmonks\LaravelApiModules\Providers\HelperServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('laravel-api-modules.modules_dir', 'app/Modules');
        $this->app['config']->set('laravel-api-modules.core_interfaces_dir', 'app/Core/Interfaces');
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelApiModulesServiceProvider::class,
            HelperServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    /**
     * Create a temporary directory for testing file operations
     */
    protected function createTempDirectory(): string
    {
        $tempDir = sys_get_temp_dir() . '/laravel-api-modules-test-' . uniqid();
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        return $tempDir;
    }

    /**
     * Clean up temporary directories
     */
    protected function cleanupTempDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            $this->removeDirectory($dir);
        }
    }

    /**
     * Recursively remove directory
     */
    private function removeDirectory(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * Mock filesystem operations
     */
    protected function mockFilesystem(): \Mockery\MockInterface
    {
        $mock = \Mockery::mock(\Illuminate\Filesystem\Filesystem::class);
        $this->app->instance(\Illuminate\Filesystem\Filesystem::class, $mock);

        return $mock;
    }
}
