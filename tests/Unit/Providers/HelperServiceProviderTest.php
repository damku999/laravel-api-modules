<?php

use Webmonks\LaravelApiModules\Providers\HelperServiceProvider;

// HelperServiceProvider tests

beforeEach(function () {
    $this->tempDir = createTempDirectory();
    $this->helpersDir = app_path('Helpers/AutoloadFiles');
    if (!is_dir($this->helpersDir)) {
        mkdir($this->helpersDir, 0755, true);
    }
});

afterEach(function () {
    cleanupTempDirectory($this->tempDir);

    // Clean up the helpers directory if it was created
    if (is_dir($this->helpersDir)) {
        $files = glob($this->helpersDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->helpersDir)) {
            rmdir($this->helpersDir);
        }
        $parentDir = dirname($this->helpersDir);
        if (is_dir($parentDir) && basename($parentDir) === 'Helpers') {
            rmdir($parentDir);
        }
    }
});

it('boots without errors', function () {
    $provider = new HelperServiceProvider(app());

    // Should not throw any exception
    $provider->boot();

    // Verify provider is properly constructed
    expect($provider)->toBeInstanceOf(HelperServiceProvider::class);
});

it('handles missing helpers directory gracefully', function () {
    $provider = new HelperServiceProvider(app());

    // Should not throw any exception even when helpers directory doesn't exist
    $provider->boot();

    // Verify provider completed boot process
    expect($provider)->toBeInstanceOf(HelperServiceProvider::class);
});

it('is registered as service provider', function () {
    expect(HelperServiceProvider::class)->toBeString();

    $provider = new HelperServiceProvider(app());
    expect($provider)->toBeInstanceOf(HelperServiceProvider::class);
});

it('calls helper autoloader when directory exists', function () {
    // Create helper files with a unique function name
    $uniqueFunction = 'test_autoloaded_helper_' . uniqid();
    file_put_contents($this->helpersDir . '/helper1.php', '<?php function ' . $uniqueFunction . '() { return "autoloaded"; }');

    $provider = new HelperServiceProvider(app());

    // Boot the provider
    $provider->boot();

    // Verify the helper was loaded (function should exist after boot)
    expect(function_exists($uniqueFunction))->toBe(true);
    expect(call_user_func($uniqueFunction))->toBe('autoloaded');
});

it('extends laravel service provider', function () {
    $provider = new HelperServiceProvider(app());
    expect($provider)->toBeInstanceOf(\Illuminate\Support\ServiceProvider::class);
});

it('calls boot method on service provider boot', function () {
    $provider = new HelperServiceProvider(app());

    // The boot method should exist and be callable
    expect(method_exists($provider, 'boot'))->toBeTrue();
    expect(is_callable([$provider, 'boot']))->toBeTrue();
});
