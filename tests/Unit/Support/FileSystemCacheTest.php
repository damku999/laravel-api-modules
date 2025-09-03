<?php

use Webmonks\LaravelApiModules\Support\FileSystemCache;

beforeEach(function () {
    FileSystemCache::clearCache();
});

afterEach(function () {
    FileSystemCache::clearCache();
});

// FileSystemCache tests

it('caches file existence checks', function () {
    $existingFile = __FILE__;
    $nonExistentFile = '/non-existent-file.txt';

    // First call - should cache the result
    expect(FileSystemCache::exists($existingFile))->toBeTrue();
    expect(FileSystemCache::exists($nonExistentFile))->toBeFalse();

    // Get cache stats
    $stats = FileSystemCache::getCacheStats();
    expect($stats['existence_cache_size'])->toBe(2);
    expect($stats['content_cache_size'])->toBe(0);
});

it('returns cached existence results', function () {
    $file = __FILE__;

    // First call
    $result1 = FileSystemCache::exists($file);

    // Second call should return cached result
    $result2 = FileSystemCache::exists($file);

    expect($result1)->toBeTrue()
        ->and($result2)->toBeTrue()
        ->and($result1)->toBe($result2);
});

it('caches file contents', function () {
    $file = __FILE__;

    $content1 = FileSystemCache::getContents($file);
    $content2 = FileSystemCache::getContents($file);

    expect($content1)->not->toBeNull()
        ->and($content2)->toBe($content1);

    $stats = FileSystemCache::getCacheStats();
    expect($stats['content_cache_size'])->toBe(1);
});

it('returns null for non-existent file content', function () {
    $nonExistentFile = '/non-existent-file.txt';

    $content = FileSystemCache::getContents($nonExistentFile);

    expect($content)->toBeNull();
});

it('limits existence cache size', function () {
    // Create many cache entries to trigger cleanup
    foreach (range(0, 149) as $i) {
        FileSystemCache::exists('/fake-path-' . $i);
    }

    $stats = FileSystemCache::getCacheStats();
    expect($stats['existence_cache_size'])->toBeLessThanOrEqual(100);
});

it('limits content cache size', function () {
    // Create temporary files for testing
    $tempFiles = [];
    foreach (range(0, 29) as $i) {
        $testFile = tempnam(sys_get_temp_dir(), 'test_content_');
        file_put_contents($testFile, "test content $i");
        FileSystemCache::getContents($testFile);
        $tempFiles[] = $testFile;
    }

    $stats = FileSystemCache::getCacheStats();
    expect($stats['content_cache_size'])->toBeLessThanOrEqual(20);

    // Cleanup
    foreach ($tempFiles as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

it('clears all caches', function () {
    FileSystemCache::exists(__FILE__);
    FileSystemCache::getContents(__FILE__);

    $statsBeforeClear = FileSystemCache::getCacheStats();
    expect($statsBeforeClear['existence_cache_size'])->toBeGreaterThan(0);
    expect($statsBeforeClear['content_cache_size'])->toBeGreaterThan(0);

    FileSystemCache::clearCache();

    $statsAfterClear = FileSystemCache::getCacheStats();
    expect($statsAfterClear['existence_cache_size'])->toBe(0);
    expect($statsAfterClear['content_cache_size'])->toBe(0);
});

it('provides cache statistics', function () {
    $file = __FILE__;

    FileSystemCache::exists($file);
    FileSystemCache::getContents($file);

    $stats = FileSystemCache::getCacheStats();

    expect($stats)->toBeArray()
        ->toHaveKeys(['existence_cache_size', 'content_cache_size', 'memory_usage_estimate'])
        ->and($stats['existence_cache_size'])->toBe(1)
        ->and($stats['content_cache_size'])->toBe(1)
        ->and($stats['memory_usage_estimate'])->toBeGreaterThan(0);
});

it('can warm cache with multiple paths', function () {
    $paths = [__FILE__, __DIR__];

    FileSystemCache::warmCache($paths);

    $stats = FileSystemCache::getCacheStats();
    expect($stats['existence_cache_size'])->toBe(2);
});

it('estimates memory usage correctly', function () {
    $file = __FILE__;

    FileSystemCache::exists($file);
    FileSystemCache::getContents($file);

    $stats = FileSystemCache::getCacheStats();
    $memoryEstimate = $stats['memory_usage_estimate'];

    // Should include both existence entry (~200 bytes) and content size
    expect($memoryEstimate)->toBeGreaterThan(200);
});
