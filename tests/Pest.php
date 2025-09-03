<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(
    Webmonks\LaravelApiModules\Tests\TestCase::class,
)->in(__DIR__);

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toHaveValidConfig', function () {
    return $this->toBeArray()
        ->and($this->value)->toHaveKeys([
            'modules_dir',
            'core_interfaces_dir',
            'namespace',
            'interface_namespace',
        ]);
});

expect()->extend('toBeValidFilePath', function () {
    return $this->toBeString()
        ->and(strlen($this->value))->toBeGreaterThan(0);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a temporary directory for testing
 */
function createTempDirectory(): string
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
function cleanupTempDirectory(string $dir): void
{
    if (is_dir($dir)) {
        removeDirectory($dir);
    }
}

/**
 * Recursively remove directory
 */
function removeDirectory(string $dir): void
{
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            removeDirectory($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

/**
 * Create a test stub file
 */
function createTestStub(string $name, string $content = ''): string
{
    $tempDir = createTempDirectory();
    $stubPath = $tempDir . '/' . $name . '.stub';
    file_put_contents($stubPath, $content ?: "<?php\n\n// Test stub: $name\nclass DummyClass {}\n");

    return $stubPath;
}

/**
 * Mock Laravel filesystem
 */
function mockFilesystem(): \Mockery\MockInterface
{
    return \Mockery::mock(\Illuminate\Filesystem\Filesystem::class);
}
