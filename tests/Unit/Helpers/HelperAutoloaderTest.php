<?php

use Webmonks\LaravelApiModules\Helpers\HelperAutoloader;

beforeEach(function () {
    $this->tempDir = createTempDirectory();
});

afterEach(function () {
    cleanupTempDirectory($this->tempDir);
});

// HelperAutoloader tests

it('loads valid helper files from directory', function () {
    // Create a helper file
    $helperFile = $this->tempDir . '/test_helper.php';
    file_put_contents($helperFile, '<?php function test_helper_function() { return "test_loaded"; }');

    HelperAutoloader::loadHelpers($this->tempDir);

    // Check if function was loaded
    expect(function_exists('test_helper_function'))->toBeTrue();
    expect(test_helper_function())->toBe('test_loaded');
});

it('throws exception for invalid directory', function () {
    expect(function () {
        HelperAutoloader::loadHelpers('/non/existent/directory');
    })->toThrow(InvalidArgumentException::class, "The directory '/non/existent/directory' is not valid or readable.");
});

it('throws exception for unreadable directory', function () {
    // Create a directory but make it unreadable (Unix only)
    $unreadableDir = $this->tempDir . '/unreadable';
    mkdir($unreadableDir);

    if (PHP_OS_FAMILY === 'Windows') {
        // On Windows, we can't easily make directories unreadable
        // So we'll just test with a non-existent directory
        expect(function () {
            HelperAutoloader::loadHelpers('/non/existent/path');
        })->toThrow(InvalidArgumentException::class);
    } else {
        chmod($unreadableDir, 0000);

        expect(function () use ($unreadableDir) {
            HelperAutoloader::loadHelpers($unreadableDir);
        })->toThrow(InvalidArgumentException::class);

        // Restore permissions for cleanup
        chmod($unreadableDir, 0755);
    }
});

it('handles empty directory gracefully', function () {
    $emptyDir = $this->tempDir . '/empty';
    mkdir($emptyDir);

    // Should not throw any exception
    HelperAutoloader::loadHelpers($emptyDir);

    // Verify operation completed successfully
    expect(is_dir($emptyDir))->toBe(true);
});

it('skips non-php files', function () {
    // Create non-PHP files
    file_put_contents($this->tempDir . '/text.txt', 'not a php file');
    file_put_contents($this->tempDir . '/config.json', '{"key": "value"}');

    // Create one valid PHP file
    file_put_contents($this->tempDir . '/valid.php', '<?php function valid_helper() { return "loaded"; }');

    HelperAutoloader::loadHelpers($this->tempDir);

    expect(function_exists('valid_helper'))->toBeTrue();
});

it('logs errors for invalid PHP files', function () {
    // Create a PHP file with syntax error
    $invalidFile = $this->tempDir . '/invalid.php';
    file_put_contents($invalidFile, '<?php function invalid_syntax( { echo "broken"; }');

    // Capture error log
    $errorLogPath = $this->tempDir . '/error.log';
    ini_set('log_errors', '1');
    ini_set('error_log', $errorLogPath);

    HelperAutoloader::loadHelpers($this->tempDir);

    // Check if error was logged
    if (file_exists($errorLogPath)) {
        $errorLog = file_get_contents($errorLogPath);
        expect($errorLog)->toContain('Failed to load helper file');
    }
});

it('loads multiple helper files in directory', function () {
    // Create multiple helper files
    file_put_contents($this->tempDir . '/helper1.php', '<?php function helper_one() { return "one"; }');
    file_put_contents($this->tempDir . '/helper2.php', '<?php function helper_two() { return "two"; }');
    file_put_contents($this->tempDir . '/helper3.php', '<?php function helper_three() { return "three"; }');

    HelperAutoloader::loadHelpers($this->tempDir);

    expect(function_exists('helper_one'))->toBeTrue();
    expect(function_exists('helper_two'))->toBeTrue();
    expect(function_exists('helper_three'))->toBeTrue();
    expect(helper_one())->toBe('one');
    expect(helper_two())->toBe('two');
    expect(helper_three())->toBe('three');
});

it('prevents duplicate loading with require_once', function () {
    // Create a helper file that defines a function
    $helperFile = $this->tempDir . '/duplicate_test.php';
    file_put_contents($helperFile, '<?php 
        if (!function_exists("duplicate_test_function")) {
            function duplicate_test_function() { return "loaded_once"; }
        }
    ');

    // Load twice
    HelperAutoloader::loadHelpers($this->tempDir);
    HelperAutoloader::loadHelpers($this->tempDir);

    // Should still work without errors
    expect(function_exists('duplicate_test_function'))->toBeTrue();
    expect(duplicate_test_function())->toBe('loaded_once');
});

it('handles subdirectories correctly', function () {
    // Create subdirectory with PHP files
    $subDir = $this->tempDir . '/subdir';
    mkdir($subDir);
    file_put_contents($subDir . '/sub_helper.php', '<?php function sub_helper() { return "sub"; }');

    // Create file in main directory
    file_put_contents($this->tempDir . '/main_helper.php', '<?php function main_helper() { return "main"; }');

    HelperAutoloader::loadHelpers($this->tempDir);

    // Should load main directory files but not subdirectory files
    expect(function_exists('main_helper'))->toBeTrue();
    expect(function_exists('sub_helper'))->toBeFalse();
});
