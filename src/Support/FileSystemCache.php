<?php

declare(strict_types=1);

namespace Webmonks\LaravelApiModules\Support;

/**
 * File System Cache for optimizing repeated file operations.
 *
 * Provides in-memory caching for file existence checks and content loading
 * to reduce I/O operations during module generation.
 */
class FileSystemCache
{
    /**
     * @var array<string,bool>
     */
    private static array $existenceCache = [];

    /**
     * @var array<string,string>
     */
    private static array $contentCache = [];
    private static int $maxCacheSize = 100;
    private static int $contentMaxSize = 20;

    /**
     * Check if a file exists, with caching.
     *
     * @param string $path File path to check
     * @return bool True if file exists
     */
    public static function exists(string $path): bool
    {
        // Prevent memory bloat by limiting cache size
        if (count(self::$existenceCache) > self::$maxCacheSize) {
            self::$existenceCache = array_slice(self::$existenceCache, -50, null, true);
        }

        return self::$existenceCache[$path] ??= file_exists($path);
    }

    /**
     * Get file contents with caching.
     *
     * @param string $path File path to read
     * @return string|null File contents or null if file doesn't exist
     */
    public static function getContents(string $path): ?string
    {
        if (!self::exists($path)) {
            return null;
        }

        // Keep content cache smaller due to memory usage
        if (count(self::$contentCache) > self::$contentMaxSize) {
            self::$contentCache = array_slice(self::$contentCache, -10, null, true);
        }

        if (isset(self::$contentCache[$path])) {
            return self::$contentCache[$path];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        self::$contentCache[$path] = $content;

        return $content;
    }

    /**
     * Clear all caches (useful for testing or memory management).
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$existenceCache = [];
        self::$contentCache = [];
    }

    /**
     * Get cache statistics for monitoring.
     *
     * @return array Cache statistics
     */
    /**
     * @return array{existence_cache_size:int,content_cache_size:int,memory_usage_estimate:int}
     */
    public static function getCacheStats(): array
    {
        return [
            'existence_cache_size' => count(self::$existenceCache),
            'content_cache_size' => count(self::$contentCache),
            'memory_usage_estimate' => self::estimateMemoryUsage(),
        ];
    }

    /**
     * Estimate current memory usage of caches.
     *
     * @return int Estimated memory usage in bytes
     */
    private static function estimateMemoryUsage(): int
    {
        $existenceMemory = count(self::$existenceCache) * 200; // ~200 bytes per path entry
        $contentMemory = array_sum(array_map('strlen', self::$contentCache)); // actual content size

        return $existenceMemory + $contentMemory;
    }

    /**
     * Warm up cache with commonly accessed paths.
     *
     * @param array $paths Array of paths to pre-cache
     * @return void
     */
    /**
     * @param array<int,string> $paths
     */
    public static function warmCache(array $paths): void
    {
        foreach ($paths as $path) {
            self::exists($path);
        }
    }
}
