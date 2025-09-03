<?php

namespace Webmonks\LaravelApiModules\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Webmonks\LaravelApiModules\Support\FileSystemCache;

/**
 * Artisan command for removing Laravel API modules safely.
 *
 * Removes controllers, models, repositories, services, requests, tests,
 * migrations, and interfaces with safety checks and backup functionality.
 */
class RemoveModuleCommand extends Command
{
    protected $signature = 'remove:module {name} ' .
        '{--force : Skip all confirmation prompts} ' .
        '{--no-backup : Skip creating backup before deletion} ' .
        '{--preview : Show what would be deleted without actually deleting}';
    protected $description = 'Remove an existing API module with safety checks and backup';

    protected ?Filesystem $files;

    public function __construct(?Filesystem $files = null)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Get the filesystem instance, using container if available
     */
    protected function getFilesystem(): Filesystem
    {
        return $this->laravel->make(Filesystem::class);
    }

    /**
     * Check if directory is empty or only contains empty subdirectories
     */
    protected function isDirectoryEmpty(string $dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            return false; // Found at least one file
        }

        return true; // No files found
    }

    /**
     * Execute the console command to remove an existing API module.
     *
     * @return int Exit code (0 for success, 1 for error)
     */
    public function handle(): int
    {
        $config = config('laravel-api-modules');
        $rawModuleNameArg = $this->argument('name');

        if (!is_string($rawModuleNameArg) || empty($rawModuleNameArg)) {
            $this->error('Module name is required and must be a string.');

            return 1;
        }

        // Security: Validate module name to prevent path traversal
        if (!$this->isValidModuleName($rawModuleNameArg)) {
            $this->error('Invalid module name. Only alphanumeric characters and underscores are allowed.');

            return 1;
        }

        $moduleName = Str::studly($rawModuleNameArg);
        $moduleSnake = Str::snake($moduleName);

        // Check if this is preview mode
        $isPreview = $this->option('preview');
        $skipBackup = $this->option('no-backup');
        $force = $this->option('force');

        if ($isPreview) {
            $this->info("🔍 Preview mode - showing what would be deleted for module: {$moduleName}");
        } else {
            $this->info("🗑️  Preparing to remove module: {$moduleName}");
        }

        // Paths (support absolute or relative configured paths)
        $modulesDir = $this->resolveConfiguredPath($config['modules_dir'] ?? 'app/Modules');
        $coreInterfacesDir = $this->resolveConfiguredPath($config['core_interfaces_dir'] ?? 'app/Core/Interfaces');
        $migrationDir = $this->resolveConfiguredPath($config['migration_path'] ?? 'database/migrations');
        $testsFeatureDir = $this->resolveConfiguredPath($config['tests_dir'] ?? 'tests/Feature');
        $testsUnitDir = $this->resolveConfiguredPath($config['tests_unit_dir'] ?? (dirname($testsFeatureDir) . '/Unit'));

        $moduleBasePath = $modulesDir . "/{$moduleName}";

        // Check if module exists
        if (!$this->moduleExists($moduleBasePath, $moduleName)) {
            $this->error("Module {$moduleName} does not exist!");

            return 1;
        }

        // Discover all files to be removed
        $filesToRemove = $this->discoverModuleFiles($moduleName, $moduleSnake, $moduleBasePath, $coreInterfacesDir, $migrationDir, $testsFeatureDir, $testsUnitDir);

        // Check if there are actually any files or non-empty directories
        $hasFilesToRemove = !empty($filesToRemove['files']);
        $hasDirectoriesToRemove = false;

        foreach ($filesToRemove['directories'] as $dir) {
            if (is_dir($dir) && !$this->isDirectoryEmpty($dir)) {
                $hasDirectoriesToRemove = true;

                break;
            }
        }

        if (!$hasFilesToRemove && !$hasDirectoriesToRemove) {
            $this->warn("No files found for module {$moduleName}");

            return 1;
        }

        // Display what will be removed
        $this->displayFilesToRemove($filesToRemove, (bool)$isPreview);

        // In preview mode, just show and exit
        if ($isPreview) {
            $this->info("📊 Preview complete. {$this->countFiles($filesToRemove)} files and {$this->countDirectories($filesToRemove)} directories would be removed.");

            return 0;
        }

        // Safety confirmations (unless --force is used)
        if (!$force && !$this->confirmRemoval($moduleName, $filesToRemove)) {
            $this->info('❌ Operation cancelled by user.');

            return 1;
        }

        // Create backup if requested
        $backupPath = null;
        if (!$skipBackup) {
            $backupPath = $this->createBackup($moduleName, $filesToRemove);
            if ($backupPath) {
                $this->info("📦 Backup created at: {$backupPath}");
            } else {
                $this->warn("⚠️  Could not create backup. Continue anyway? [y/N]");
                if (!$force && !$this->confirm('Continue without backup?', false)) {
                    $this->info('❌ Operation cancelled.');

                    return 1;
                }
            }
        }

        // Perform the removal
        $success = $this->removeModuleFiles($filesToRemove);

        if ($success) {
            $this->info("✅ Module {$moduleName} removed successfully!");
            if ($backupPath) {
                $this->info("💡 Backup is available at: {$backupPath}");
            }

            // Cleanup repository service provider bindings
            $this->cleanupRepositoryBindings($moduleName);

            return 0;
        } else {
            $this->error("❌ Failed to remove module {$moduleName}. Some files may remain.");
            if ($backupPath) {
                $this->info("💡 You can restore from backup: {$backupPath}");
            }

            return 1;
        }
    }

    /**
     * Check if module exists in the configured path or legacy path
     */
    protected function moduleExists(string $moduleBasePath, string $moduleName): bool
    {
        $fs = $this->getFilesystem();

        // Check configured path
        if (is_dir($moduleBasePath) || FileSystemCache::exists($moduleBasePath)) {
            return true;
        }

        // Check legacy path
        $legacyPath = base_path("app/Modules/{$moduleName}");

        try {
            return $fs->exists($legacyPath) || is_dir($legacyPath);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Discover all files that belong to the module
     */
    /**
     * @return array{directories: string[], files: string[]}
     */
    protected function discoverModuleFiles(string $moduleName, string $moduleSnake, string $moduleBasePath, string $coreInterfacesDir, string $migrationDir, string $testsFeatureDir, string $testsUnitDir): array
    {
        $filesToRemove = [
            'directories' => [],
            'files' => [],
        ];

        // Main module directory
        if (is_dir($moduleBasePath)) {
            $filesToRemove['directories'][] = $moduleBasePath;
        }

        // Legacy module directory
        $legacyPath = base_path("app/Modules/{$moduleName}");
        if (is_dir($legacyPath) && $legacyPath !== $moduleBasePath) {
            $filesToRemove['directories'][] = $legacyPath;
        }

        // Interface files
        $interfaceFile = $coreInterfacesDir . "/{$moduleName}RepositoryInterface.php";
        if (file_exists($interfaceFile)) {
            $filesToRemove['files'][] = $interfaceFile;
        }

        // Migration files
        $migrationFiles = glob($migrationDir . "/*_create_{$moduleSnake}_table.php");
        if ($migrationFiles) {
            $filesToRemove['files'] = array_merge($filesToRemove['files'], $migrationFiles);
        }

        // Test files
        $featureTestDir = $testsFeatureDir . "/Modules/{$moduleName}";
        if (is_dir($featureTestDir)) {
            $filesToRemove['directories'][] = $featureTestDir;
        }

        $unitTestDir = $testsUnitDir . "/Modules/{$moduleName}";
        if (is_dir($unitTestDir)) {
            $filesToRemove['directories'][] = $unitTestDir;
        }

        return $filesToRemove;
    }

    /**
     * Display files that will be removed
     * @param array{directories: string[], files: string[]} $filesToRemove
     */
    protected function displayFilesToRemove(array $filesToRemove, bool $isPreview): void
    {
        $verb = $isPreview ? 'would be removed' : 'will be removed';

        $this->info("📋 The following {$verb}:");

        if (!empty($filesToRemove['directories'])) {
            $this->info('📁 Directories:');
            foreach ($filesToRemove['directories'] as $dir) {
                $relativePath = str_replace([base_path() . DIRECTORY_SEPARATOR, base_path() . '/'], '', str_replace('\\', '/', $dir));
                $this->line("   - {$relativePath}");
            }
        }

        if (!empty($filesToRemove['files'])) {
            $this->info('📄 Files:');
            foreach ($filesToRemove['files'] as $file) {
                $relativePath = str_replace([base_path() . DIRECTORY_SEPARATOR, base_path() . '/'], '', str_replace('\\', '/', $file));
                $this->line("   - {$relativePath}");
            }
        }
    }

    /**
     * Confirm removal with user
     * @param array{directories: string[], files: string[]} $filesToRemove
     */
    protected function confirmRemoval(string $moduleName, array $filesToRemove): bool
    {
        $fileCount = $this->countFiles($filesToRemove);
        $dirCount = $this->countDirectories($filesToRemove);

        $this->warn("⚠️  This will permanently delete {$fileCount} files and {$dirCount} directories for module '{$moduleName}'.");

        if (!$this->confirm('Are you sure you want to continue?', false)) {
            return false;
        }

        // Second confirmation for safety
        if (!$this->confirm("Type the module name '{$moduleName}' to confirm deletion:", false)) {
            return false;
        }

        $userInput = $this->ask("Please type '{$moduleName}' exactly:");

        return $userInput === $moduleName;
    }

    /**
     * Create backup of module files before deletion
     * @param array{directories: string[], files: string[]} $filesToRemove
     */
    protected function createBackup(string $moduleName, array $filesToRemove): ?string
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $backupDir = base_path("storage/laravel-api-modules-backups/{$moduleName}_{$timestamp}");

        try {
            if (!is_dir(dirname($backupDir))) {
                mkdir(dirname($backupDir), 0755, true);
            }
            mkdir($backupDir, 0755, true);

            // Backup directories
            foreach ($filesToRemove['directories'] as $dir) {
                if (is_dir($dir)) {
                    $relativePath = str_replace([base_path() . DIRECTORY_SEPARATOR, base_path() . '/'], '', str_replace('\\', '/', $dir));
                    $backupPath = $backupDir . DIRECTORY_SEPARATOR . $relativePath;
                    $this->copyDirectory($dir, $backupPath);
                }
            }

            // Backup individual files
            foreach ($filesToRemove['files'] as $file) {
                if (file_exists($file)) {
                    $relativePath = str_replace([base_path() . DIRECTORY_SEPARATOR, base_path() . '/'], '', str_replace('\\', '/', $file));
                    $backupPath = $backupDir . DIRECTORY_SEPARATOR . $relativePath;
                    $this->ensureDirectoryExists(dirname($backupPath));
                    copy($file, $backupPath);
                }
            }

            return $backupDir;
        } catch (\Exception $e) {
            $this->warn("Failed to create backup: " . $e->getMessage());

            return null;
        }
    }

    /**
     * Remove module files and directories
     * @param array{directories: string[], files: string[]} $filesToRemove
     */
    protected function removeModuleFiles(array $filesToRemove): bool
    {
        $success = true;

        try {
            // Remove individual files first
            foreach ($filesToRemove['files'] as $file) {
                if (file_exists($file)) {
                    if (!unlink($file)) {
                        $this->error("Failed to delete file: {$file}");
                        $success = false;
                    }
                }
            }

            // Remove directories (with all contents)
            foreach ($filesToRemove['directories'] as $dir) {
                if (is_dir($dir)) {
                    if (!$this->removeDirectory($dir)) {
                        $this->error("Failed to delete directory: {$dir}");
                        $success = false;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("Error during removal: " . $e->getMessage());
            $success = false;
        }

        return $success;
    }

    /**
     * Clean up repository service provider bindings
     */
    protected function cleanupRepositoryBindings(string $moduleName): void
    {
        $providerPath = app_path('Core/Providers/RepositoryServiceProvider.php');

        if (!file_exists($providerPath)) {
            return;
        }

        try {
            $content = file_get_contents($providerPath);
            if ($content === false) {
                throw new \RuntimeException("Could not read provider file: {$providerPath}");
            }

            $interfaceName = "{$moduleName}RepositoryInterface";
            $repositoryName = "{$moduleName}Repository";

            // Remove binding lines (with some flexibility in formatting)
            $patterns = [
                '/\s*\$this->app->bind\(\s*' . preg_quote($interfaceName) . '::class,\s*' . preg_quote($repositoryName) . '::class\s*\);\s*\n?/',
                '/\s*use\s+.*\\\\' . preg_quote($interfaceName) . ';\s*\n?/',
                '/\s*use\s+.*\\\\' . preg_quote($repositoryName) . ';\s*\n?/',
            ];

            foreach ($patterns as $pattern) {
                $result = preg_replace($pattern, '', $content);
                if ($result !== null) {
                    $content = $result;
                }
            }

            file_put_contents($providerPath, $content);
            $this->info("🧹 Cleaned up repository bindings in RepositoryServiceProvider");
        } catch (\Exception $e) {
            $this->warn("Could not clean up repository bindings: " . $e->getMessage());
        }
    }

    /**
     * Count files in the removal array
     * @param array{directories: string[], files: string[]} $filesToRemove
     */
    protected function countFiles(array $filesToRemove): int
    {
        $count = count($filesToRemove['files']);

        // Count files in directories
        foreach ($filesToRemove['directories'] as $dir) {
            if (is_dir($dir)) {
                $count += $this->countFilesInDirectory($dir);
            }
        }

        return $count;
    }

    /**
     * Count directories in the removal array
     * @param array{directories: string[], files: string[]} $filesToRemove
     */
    protected function countDirectories(array $filesToRemove): int
    {
        $count = count($filesToRemove['directories']);

        // Count subdirectories
        foreach ($filesToRemove['directories'] as $dir) {
            if (is_dir($dir)) {
                $count += $this->countSubdirectories($dir);
            }
        }

        return $count;
    }

    /**
     * Recursively count files in a directory
     */
    protected function countFilesInDirectory(string $dir): int
    {
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Recursively count subdirectories
     */
    protected function countSubdirectories(string $dir): int
    {
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Recursively remove a directory and all its contents
     */
    protected function removeDirectory(string $dir): bool
    {
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    rmdir($item->getRealPath());
                } else {
                    unlink($item->getRealPath());
                }
            }

            return rmdir($dir);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Copy directory recursively
     */
    protected function copyDirectory(string $source, string $destination): bool
    {
        try {
            if (!is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $destPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();

                if ($item->isDir()) {
                    if (!is_dir($destPath)) {
                        mkdir($destPath, 0755, true);
                    }
                } else {
                    $this->ensureDirectoryExists(dirname($destPath));
                    copy($item->getRealPath(), $destPath);
                }
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate module name to prevent path traversal attacks
     */
    private function isValidModuleName(string $moduleName): bool
    {
        return preg_match('/^[a-zA-Z0-9_-]+$/', $moduleName)
            && strpos($moduleName, '..') === false
            && strpos($moduleName, '/') === false
            && strpos($moduleName, '\\') === false
            && strlen($moduleName) <= 50;
    }

    /**
     * Determine if a path is absolute (Unix root, Windows drive, or UNC path)
     */
    private function isAbsolutePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        // Unix root
        if ($path[0] === '/') {
            return true;
        }

        // Windows UNC path \\server\share
        if (strlen($path) >= 2 && $path[0] === '\\' && $path[1] === '\\') {
            return true;
        }

        // Windows drive letter, e.g., C:\ or C:/
        return strlen($path) > 2
            && ctype_alpha($path[0])
            && $path[1] === ':'
            && ($path[2] === '\\' || $path[2] === '/');
    }

    /**
     * Resolve configured path: use as-is if absolute, otherwise resolve relative to base_path()
     */
    private function resolveConfiguredPath(string $path): string
    {
        return $this->isAbsolutePath($path) ? $path : base_path($path);
    }

    /**
     * Ensure directory exists using cached checks for performance
     */
    private function ensureDirectoryExists(string $dir): void
    {
        if (!FileSystemCache::exists($dir) && !is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new \RuntimeException("Cannot create directory: {$dir}");
            }
        }
    }
}
