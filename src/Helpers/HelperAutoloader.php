<?php

declare(strict_types=1);

namespace Webmonks\LaravelApiModules\Helpers;

use InvalidArgumentException;
use Throwable;

class HelperAutoloader
{
    public static function loadHelpers(string $directory): void
    {
        if (!is_dir($directory) || !is_readable($directory)) {
            throw new InvalidArgumentException("The directory '$directory' is not valid or readable.");
        }

        $files = glob($directory . '/*.php');
        if ($files !== false) {
            foreach ($files as $filename) {
                try {
                    require_once $filename;
                } catch (Throwable $e) {
                    error_log("Failed to load helper file: $filename. Error: " . $e->getMessage());
                }
            }
        }
    }
}
