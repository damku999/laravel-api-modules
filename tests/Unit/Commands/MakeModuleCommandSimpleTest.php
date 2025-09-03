<?php

use Webmonks\LaravelApiModules\Commands\MakeModuleCommand;
use Webmonks\LaravelApiModules\Support\FileSystemCache;

beforeEach(function () {
    $this->tempDir = createTempDirectory();

    // Setup configuration with temporary directory
    config([
        'laravel-api-modules.modules_dir' => $this->tempDir . '/Modules',
        'laravel-api-modules.core_interfaces_dir' => $this->tempDir . '/Core/Interfaces',
        'laravel-api-modules.namespace' => 'App\\Modules',
        'laravel-api-modules.interface_namespace' => 'App\\Core\\Interfaces',
        'laravel-api-modules.generate_migration' => false,
        'laravel-api-modules.generate_tests' => false,
        'laravel-api-modules.enable_base_model' => false,
        'laravel-api-modules.enable_base_service' => false,
    ]);

    FileSystemCache::clearCache();
});

afterEach(function () {
    FileSystemCache::clearCache();
    cleanupTempDirectory($this->tempDir);
});

it('validates module name properly', function () {
    $command = new MakeModuleCommand();

    // Use reflection to test private method
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('isValidModuleName');
    $method->setAccessible(true);

    expect($method->invoke($command, 'ValidModule'))->toBeTrue();
    expect($method->invoke($command, 'valid_module'))->toBeTrue();
    expect($method->invoke($command, 'Module123'))->toBeTrue();

    expect($method->invoke($command, '../malicious'))->toBeFalse();
    expect($method->invoke($command, '..\\malicious'))->toBeFalse();
    expect($method->invoke($command, 'test/path'))->toBeFalse();
    expect($method->invoke($command, 'test@module'))->toBeFalse();
    expect($method->invoke($command, str_repeat('a', 51)))->toBeFalse();
});

it('handles configuration correctly', function () {
    $config = config('laravel-api-modules');

    expect($config)->toHaveKey('modules_dir');
    expect($config)->toHaveKey('core_interfaces_dir');
    expect($config)->toHaveKey('namespace');
    expect($config)->toHaveKey('interface_namespace');
});

it('creates proper directory structure when called', function () {
    // Create necessary parent directories
    $modulesDir = $this->tempDir . '/Modules';
    $coreDir = $this->tempDir . '/Core/Interfaces';
    mkdir($modulesDir, 0755, true);
    mkdir($coreDir, 0755, true);

    // Create stub files first
    $stubsDir = $this->tempDir . '/stubs';
    mkdir($stubsDir, 0755, true);

    $stubFiles = [
        'controller.stub' => '<?php // Controller stub',
        'model.stub' => '<?php // Model stub',
        'repository.stub' => '<?php // Repository stub',
        'service.stub' => '<?php // Service stub',
        'route.stub' => '<?php // Route stub',
        'repository_interface.stub' => '<?php // Interface stub',
        'request_list.stub' => '<?php // Request stub',
    ];

    foreach ($stubFiles as $file => $content) {
        file_put_contents($stubsDir . '/' . $file, $content);
    }

    // Update config to use our stubs
    config(['laravel-api-modules.stubs_path' => $stubsDir]);

    // Create the command and simulate artisan call
    $exitCode = $this->artisan('make:module', ['name' => 'TestModule'])->run();

    expect($exitCode)->toBe(0);

    // Check that module directory was created
    expect(file_exists($this->tempDir . '/Modules/TestModule'))->toBeTrue();
    expect(is_dir($this->tempDir . '/Modules/TestModule'))->toBeTrue();
});

it('prevents path traversal attacks', function () {
    $this->artisan('make:module', ['name' => '../EvilModule'])
        ->expectsOutput('Invalid module name. Only alphanumeric characters and underscores are allowed.')
        ->assertExitCode(1);

    $this->artisan('make:module', ['name' => '..\\EvilModule'])
        ->expectsOutput('Invalid module name. Only alphanumeric characters and underscores are allowed.')
        ->assertExitCode(1);
});

it('handles long module names', function () {
    $longName = str_repeat('a', 51);

    $this->artisan('make:module', ['name' => $longName])
        ->expectsOutput('Invalid module name. Only alphanumeric characters and underscores are allowed.')
        ->assertExitCode(1);
});

it('checks for existing modules', function () {
    // Create necessary parent directories
    $modulesDir = $this->tempDir . '/Modules';
    mkdir($modulesDir, 0755, true);

    // Create an existing module directory
    $moduleDir = $this->tempDir . '/Modules/ExistingModule';
    mkdir($moduleDir, 0755, true);

    $this->artisan('make:module', ['name' => 'ExistingModule'])
        ->expectsOutput('Module ExistingModule already exists!')
        ->assertExitCode(1);
});
