<?php

namespace Webmonks\LaravelApiModules\Providers;

use Illuminate\Support\ServiceProvider;
use Webmonks\LaravelApiModules\Helpers\HelperAutoloader;

class HelperServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $helpersDir = app_path('Helpers/AutoloadFiles');
        if (is_dir($helpersDir)) {
            HelperAutoloader::loadHelpers($helpersDir);
        }
    }
}
