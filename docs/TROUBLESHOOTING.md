# 🔧 Troubleshooting Guide

This comprehensive guide helps you solve common issues and problems you might encounter when using Laravel API Modules.

---

## 🚨 Common Issues & Quick Solutions

### Installation Issues

#### **Issue**: Package not found during installation
```bash
# Error: Package webmonks/laravel-api-modules not found
```

**Solutions:**
```bash
# 1. Clear Composer cache
composer clear-cache

# 2. Update Composer
composer self-update

# 3. Try installing with specific version
composer require webmonks/laravel-api-modules:^1.0

# 4. Check your minimum stability
composer config minimum-stability dev
composer require webmonks/laravel-api-modules
```

#### **Issue**: Auto-discovery not working
```bash
# Error: Command make:module not found
```

**Solutions:**
```bash
# 1. Clear application cache
php artisan cache:clear
php artisan config:clear

# 2. Manually register the service provider (config/app.php)
'providers' => [
    // Other providers...
    Webmonks\LaravelApiModules\LaravelApiModulesServiceProvider::class,
    Webmonks\LaravelApiModules\Providers\HelperServiceProvider::class,
],

# 3. Dump autoload
composer dump-autoload

# 4. Check if command is registered
php artisan list | grep make:module
```

---

### Module Generation Issues

#### **Issue**: "Module already exists" error
```bash
# Error: Module Blog already exists!
```

**Solutions:**
```bash
# 1. Check for existing module directory
ls -la app/Modules/Blog

# 2. Remove existing module if safe to do so
rm -rf app/Modules/Blog
rm -f app/Core/Interfaces/BlogRepositoryInterface.php

# 3. Use different module name
php artisan make:module BlogPost

# 4. Check legacy paths (older versions)
ls -la app/Modules/ 
```

#### **Issue**: Permission denied when creating files
```bash
# Error: Cannot create directory: app/Modules
```

**Solutions:**
```bash
# 1. Check directory permissions
ls -la app/

# 2. Set proper permissions
sudo chmod 755 app/
sudo chown -R www-data:www-data app/

# 3. Create directories manually with proper permissions
mkdir -p app/Modules app/Core/Interfaces
chmod 755 app/Modules app/Core/Interfaces
```

#### **Issue**: Stub files not found
```bash
# Error: Stub template not found: .../controller.stub
```

**Solutions:**
```bash
# 1. Check if stubs are published
ls -la stubs/laravel-api-modules/

# 2. Publish stubs if missing
php artisan vendor:publish --tag=laravel-api-modules-stubs

# 3. Check package installation
composer show webmonks/laravel-api-modules

# 4. Clear config cache
php artisan config:cache
```

---

### Configuration Issues

#### **Issue**: Configuration not loading properly
```bash
# Error: Trying to access array offset on value of type null
```

**Solutions:**
```bash
# 1. Publish configuration file
php artisan vendor:publish --tag=laravel-api-modules-config

# 2. Clear config cache
php artisan config:clear
php artisan config:cache

# 3. Verify configuration exists
ls -la config/laravel-api-modules.php

# 4. Check configuration syntax
php artisan config:show laravel-api-modules
```

#### **Issue**: Wrong namespaces in generated files
```php
// Generated file has: namespace DummyNamespace\Controllers;
// Instead of: namespace App\Modules\Blog\Controllers;
```

**Solutions:**
```php
// 1. Check configuration (config/laravel-api-modules.php)
'namespace' => 'App\\Modules',  // Must use double backslashes

// 2. Clear config cache after changes
php artisan config:cache

// 3. Regenerate module with correct config
php artisan make:module TestModule
```

#### **Issue**: Traits not being included in BaseModel
```php
// Error: Trait 'ApiResponser' not found
```

**Solutions:**
```bash
# 1. Check if traits are published
ls -la app/Core/Traits/

# 2. Verify trait configuration
php artisan config:show laravel-api-modules.base_model_traits

# 3. Manually publish traits
php artisan vendor:publish --tag=laravel-api-modules-stubs

# 4. Check BaseModel exists and has proper use statements
cat app/Models/BaseModel.php
```

---

### Dependency Injection Issues

#### **Issue**: Repository interface not found
```php
// Error: Class App\Core\Interfaces\BlogRepositoryInterface not found
```

**Solutions:**
```bash
# 1. Check if interface was created
ls -la app/Core/Interfaces/BlogRepositoryInterface.php

# 2. Check RepositoryServiceProvider
cat app/Core/Providers/RepositoryServiceProvider.php

# 3. Clear application cache
php artisan cache:clear
composer dump-autoload

# 4. Manually register binding if needed (AppServiceProvider)
$this->app->bind(
    \App\Core\Interfaces\BlogRepositoryInterface::class,
    \App\Modules\Blog\Repositories\BlogRepository::class
);
```

#### **Issue**: Service not being injected properly
```php
// Error: Cannot resolve dependency [Parameter #0 [ $blogService ]]
```

**Solutions:**
```bash
# 1. Check if service exists
ls -la app/Modules/Blog/Services/BlogService.php

# 2. Verify service implements interface (if applicable)
# 3. Clear cache and dump autoload
php artisan cache:clear
composer dump-autoload

# 4. Check constructor parameter type hints
```

---

### Route Issues

#### **Issue**: Routes not being discovered
```bash
# Routes don't appear in: php artisan route:list
```

**Solutions:**
```bash
# 1. Check auto-discovery setting
php artisan config:show laravel-api-modules.auto_discover_routes

# 2. Check route files exist
find app/Modules -name "routes.php"

# 3. Clear route cache
php artisan route:clear
php artisan route:cache

# 4. Manually load routes in RouteServiceProvider if needed
Route::group(['prefix' => 'api'], function () {
    $modules = glob(app_path('Modules/*/routes.php'));
    foreach ($modules as $routeFile) {
        require $routeFile;
    }
});
```

#### **Issue**: Route model binding not working
```php
// Error: No query results for model [App\Modules\Blog\Models\Blog] 1
```

**Solutions:**
```php
// 1. Check route parameter names match model
Route::get('/blogs/{blog}', [BlogController::class, 'view']);

// 2. Verify model uses correct primary key
class Blog extends Model
{
    protected $primaryKey = 'id';
}

// 3. Add explicit route model binding (RouteServiceProvider)
Route::model('blog', \App\Modules\Blog\Models\Blog::class);
```

---

### Database Issues

#### **Issue**: Migration not found or not running
```bash
# Error: Migration not found or table doesn't exist
```

**Solutions:**
```bash
# 1. Check if migration was generated
ls -la database/migrations/*_create_blogs_table.php

# 2. Run migrations
php artisan migrate

# 3. Check migration status
php artisan migrate:status

# 4. Manually create migration if missing
php artisan make:migration create_blogs_table
```

#### **Issue**: Model not found or wrong namespace
```php
// Error: Class 'App\Modules\Blog\Models\Blog' not found
```

**Solutions:**
```bash
# 1. Check model exists
ls -la app/Modules/Blog/Models/Blog.php

# 2. Verify namespace in model file
head -10 app/Modules/Blog/Models/Blog.php

# 3. Check autoload
composer dump-autoload

# 4. Verify configuration namespace settings
php artisan config:show laravel-api-modules.namespace
```

---

### Testing Issues

#### **Issue**: Tests not being generated
```bash
# No test files in tests/Feature/Modules/
```

**Solutions:**
```bash
# 1. Check test generation setting
php artisan config:show laravel-api-modules.generate_tests

# 2. Enable test generation (config/laravel-api-modules.php)
'generate_tests' => true,

# 3. Regenerate module with tests enabled
php artisan make:module TestBlog --resource

# 4. Check test directories exist
ls -la tests/Feature/Modules/
ls -la tests/Unit/Modules/
```

#### **Issue**: Tests failing with dependency errors
```php
// Error in tests: Cannot resolve dependency
```

**Solutions:**
```php
// 1. Ensure TestCase sets up application properly
class BlogFeatureTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        // Additional setup
    }
}

// 2. Mock dependencies if needed
$this->mock(BlogRepositoryInterface::class, function ($mock) {
    $mock->shouldReceive('getAllBlogs')->andReturn([]);
});

// 3. Use Feature tests for HTTP endpoints
// Use Unit tests for individual components
```

---

### Helper System Issues

#### **Issue**: Helpers not being loaded
```php
// Error: Call to undefined function my_helper_function()
```

**Solutions:**
```bash
# 1. Check helper directory exists
ls -la app/Helpers/AutoloadFiles/

# 2. Verify HelperServiceProvider is registered
php artisan config:show app.providers | grep Helper

# 3. Check helper file syntax
php -l app/Helpers/AutoloadFiles/my_helpers.php

# 4. Clear application cache
php artisan cache:clear
```

#### **Issue**: Function already exists errors
```php
// Error: Cannot redeclare function my_function()
```

**Solutions:**
```php
// Always wrap helper functions in existence checks
if (!function_exists('my_function')) {
    function my_function($param) {
        // Function code here
    }
}
```

---

## 🔍 Diagnostic Commands

### Check Package Installation
```bash
# Verify package is installed
composer show webmonks/laravel-api-modules

# Check available commands
php artisan list | grep make:module

# Verify service providers
php artisan config:show app.providers | grep LaravelApiModules
```

### Check Configuration
```bash
# View all configuration
php artisan config:show laravel-api-modules

# Check specific settings
php artisan config:show laravel-api-modules.namespace
php artisan config:show laravel-api-modules.generate_tests
```

### Check Generated Files
```bash
# List all modules
find app/Modules -type d -maxdepth 1

# Check specific module structure
tree app/Modules/Blog

# Verify interfaces exist
ls -la app/Core/Interfaces/

# Check service provider
cat app/Core/Providers/RepositoryServiceProvider.php
```

### Check Routes
```bash
# List all routes
php artisan route:list

# Check module routes specifically
php artisan route:list | grep -i blog

# Clear route cache
php artisan route:clear
```

### Check Database
```bash
# Check migration status
php artisan migrate:status

# List tables
php artisan db:show --counts

# Check specific table
php artisan db:table blogs
```

---

## 📊 Environment-Specific Issues

### Development Environment

#### **Issue**: Changes not reflecting
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload
```

#### **Issue**: Debug mode problems
```php
// Enable debug in .env
APP_DEBUG=true

// Check logs
tail -f storage/logs/laravel.log
```

### Production Environment

#### **Issue**: Performance issues
```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer dump-autoload -o
```

#### **Issue**: File permissions
```bash
# Set proper permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## 🛠️ Advanced Troubleshooting

### Custom Debugging

Add debugging to MakeModuleCommand:

```php
// In your AppServiceProvider boot method
if (app()->environment('local')) {
    app()->bind(\Webmonks\LaravelApiModules\Commands\MakeModuleCommand::class, 
        function ($app) {
            return new class extends \Webmonks\LaravelApiModules\Commands\MakeModuleCommand {
                public function handle(): int
                {
                    $this->info("Debug: Starting module generation...");
                    $result = parent::handle();
                    $this->info("Debug: Module generation completed with result: $result");
                    return $result;
                }
            };
        }
    );
}
```

### Logging Issues

Enable detailed logging:

```php
// In your AppServiceProvider
public function boot()
{
    if (config('app.debug')) {
        \Log::listen(function ($level, $message, $context) {
            if (str_contains($message, 'laravel-api-modules')) {
                \Log::info("Laravel API Modules Debug: $message", $context);
            }
        });
    }
}
```

---

## 📞 Getting Help

### Before Reporting Issues

1. **Check this troubleshooting guide**
2. **Update to the latest version**: `composer update webmonks/laravel-api-modules`
3. **Clear all caches**: Run the diagnostic commands above
4. **Test with a minimal example**: Try creating a simple test module
5. **Check the error logs**: `storage/logs/laravel.log`

### Reporting Issues

When reporting issues, include:

1. **Laravel version**: `php artisan --version`
2. **Package version**: `composer show webmonks/laravel-api-modules`
3. **PHP version**: `php --version`
4. **Full error message and stack trace**
5. **Steps to reproduce the issue**
6. **Configuration file** (sanitized)
7. **Relevant code snippets**

### Community Resources

- **GitHub Issues**: [Report bugs and feature requests](https://github.com/webmonks/laravel-api-modules/issues)
- **Documentation**: [Complete guides and examples](https://github.com/webmonks/laravel-api-modules/tree/main/docs)
- **Stack Overflow**: Tag questions with `laravel-api-modules`

---

This troubleshooting guide covers the most common issues. If you encounter a problem not listed here, please check our GitHub issues or create a new one with detailed information.