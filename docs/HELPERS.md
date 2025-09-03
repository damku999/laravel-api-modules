# 🔧 Helper System Guide

The Laravel API Modules package includes a powerful helper auto-loading system that makes utility functions instantly available throughout your application with zero configuration.

---

## 🎯 What Is The Helper System?

The helper system automatically loads PHP files containing global functions, constants, and utility classes, making them available anywhere in your Laravel application without manual `require` statements or service registration.

### Key Benefits
- ✅ **Zero Configuration**: Drop files and they're instantly available
- ✅ **Performance Optimized**: Uses `require_once` to prevent duplicate loading
- ✅ **Error Safe**: Failed loads are logged but won't crash your app
- ✅ **Development Friendly**: Perfect for utility functions and constants
- ✅ **Team Collaboration**: Easy to share common utilities across projects

---

## 🚀 How It Works

### Automatic Service Provider Registration
```php
// Auto-registered by the package (no action needed)
class HelperServiceProvider extends ServiceProvider
{
    public function boot()
    {
        HelperAutoloader::loadHelpers(app_path('Helpers/AutoloadFiles'));
    }
}
```

### File Discovery Process
1. **Scan Directory**: Automatically scans `app/Helpers/AutoloadFiles/`
2. **Load Files**: Includes all `.php` files using `require_once`
3. **Error Handling**: Logs errors without stopping execution
4. **Cache Friendly**: Efficient loading with duplicate protection

---

## 📁 Usage Guide

### Basic Usage

Simply create PHP files in the auto-load directory:

```bash
# Create the directory if it doesn't exist
mkdir -p app/Helpers/AutoloadFiles
```

### Example Helper Files

#### String Manipulation Helpers
```php
// app/Helpers/AutoloadFiles/string_helpers.php
<?php

if (!function_exists('snake_to_camel')) {
    function snake_to_camel($string) {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
    }
}

if (!function_exists('camel_to_snake')) {
    function camel_to_snake($string) {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
    }
}

if (!function_exists('title_case')) {
    function title_case($string) {
        return ucwords(str_replace(['_', '-'], ' ', $string));
    }
}
```

#### API Response Helpers  
```php
// app/Helpers/AutoloadFiles/api_helpers.php
<?php

if (!function_exists('success_response')) {
    function success_response($data, $message = 'Success', $code = 200) {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}

if (!function_exists('error_response')) {
    function error_response($message, $errors = [], $code = 400) {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}

if (!function_exists('paginated_response')) {
    function paginated_response($data, $message = 'Success') {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
            ],
        ]);
    }
}
```

#### Validation Helpers
```php
// app/Helpers/AutoloadFiles/validation_helpers.php
<?php

if (!function_exists('is_valid_uuid')) {
    function is_valid_uuid($uuid) {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }
}

if (!function_exists('sanitize_filename')) {
    function sanitize_filename($filename) {
        return preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    }
}

if (!function_exists('is_valid_phone')) {
    function is_valid_phone($phone) {
        return preg_match('/^[+]?[\d\s\-\(\)]{10,}$/', $phone);
    }
}
```

#### Database Helpers
```php
// app/Helpers/AutoloadFiles/database_helpers.php
<?php

if (!function_exists('build_search_query')) {
    function build_search_query($query, $searchTerm, $fields) {
        return $query->where(function ($q) use ($searchTerm, $fields) {
            foreach ($fields as $field) {
                $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
            }
        });
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($query, $filters) {
        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }
        }
        return $query;
    }
}
```

---

## 🎨 Advanced Usage

### Constants and Configuration
```php
// app/Helpers/AutoloadFiles/constants.php
<?php

// API Constants
define('API_SUCCESS_CODE', 200);
define('API_ERROR_CODE', 400);
define('API_UNAUTHORIZED_CODE', 401);

// File Upload Constants
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// Pagination Constants
define('DEFAULT_PER_PAGE', 15);
define('MAX_PER_PAGE', 100);
```

### Utility Classes
```php
// app/Helpers/AutoloadFiles/utility_classes.php
<?php

class DateHelper 
{
    public static function formatForApi($date) {
        return $date ? $date->format('Y-m-d H:i:s') : null;
    }
    
    public static function parseFromApi($dateString) {
        return $dateString ? Carbon::createFromFormat('Y-m-d H:i:s', $dateString) : null;
    }
}

class ArrayHelper 
{
    public static function removeEmpty($array) {
        return array_filter($array, function($value) {
            return !empty($value) || $value === 0 || $value === '0';
        });
    }
    
    public static function onlyKeys($array, $keys) {
        return array_intersect_key($array, array_flip($keys));
    }
}
```

### Complex Business Logic Helpers
```php
// app/Helpers/AutoloadFiles/business_helpers.php
<?php

if (!function_exists('calculate_tax')) {
    function calculate_tax($amount, $taxRate = 0.10) {
        return round($amount * $taxRate, 2);
    }
}

if (!function_exists('format_currency')) {
    function format_currency($amount, $currency = 'USD') {
        return number_format($amount, 2) . ' ' . $currency;
    }
}

if (!function_exists('generate_invoice_number')) {
    function generate_invoice_number($prefix = 'INV') {
        return $prefix . '-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
```

---

## 🔧 Customization and Extension

### Custom Autoloader Location

You can customize the helper directory in your application:

```php
// In your custom service provider
public function boot()
{
    // Load from multiple directories
    HelperAutoloader::loadHelpers(app_path('Helpers/Global'));
    HelperAutoloader::loadHelpers(app_path('Helpers/Utilities'));
    HelperAutoloader::loadHelpers(app_path('Helpers/Business'));
}
```

### Conditional Loading
```php
// app/Helpers/AutoloadFiles/environment_specific.php
<?php

// Only load in development
if (app()->environment('local', 'development')) {
    if (!function_exists('debug_log')) {
        function debug_log($message, $data = null) {
            logger("DEBUG: $message", $data ? ['data' => $data] : []);
        }
    }
}

// Only load in production
if (app()->environment('production')) {
    if (!function_exists('performance_log')) {
        function performance_log($operation, $duration) {
            logger("PERFORMANCE: $operation took {$duration}ms");
        }
    }
}
```

### Extending the Autoloader

Create your own enhanced autoloader:

```php
// app/Services/EnhancedHelperAutoloader.php
<?php

namespace App\Services;

use Webmonks\LaravelApiModules\Helpers\HelperAutoloader;

class EnhancedHelperAutoloader extends HelperAutoloader
{
    public static function loadHelpers($directory)
    {
        if (!is_dir($directory)) {
            return;
        }

        // Load from subdirectories too
        $files = glob($directory . '/{,**/}*.php', GLOB_BRACE);
        
        foreach ($files as $file) {
            try {
                require_once $file;
            } catch (\Exception $e) {
                logger("Helper autoload error: {$file}", ['error' => $e->getMessage()]);
            }
        }
    }
}
```

---

## 📊 Organization Strategies

### Strategy 1: By Functionality
```
app/Helpers/AutoloadFiles/
├── api_helpers.php          # API response helpers
├── string_helpers.php       # String manipulation
├── validation_helpers.php   # Input validation
├── database_helpers.php     # Database utilities
└── file_helpers.php         # File operations
```

### Strategy 2: By Domain
```
app/Helpers/AutoloadFiles/
├── user_helpers.php         # User-related utilities
├── order_helpers.php        # Order processing
├── payment_helpers.php      # Payment utilities
├── notification_helpers.php # Notification helpers
└── reporting_helpers.php    # Reporting functions
```

### Strategy 3: By Layer
```
app/Helpers/AutoloadFiles/
├── controller_helpers.php   # Controller utilities
├── service_helpers.php      # Service layer helpers
├── model_helpers.php        # Model utilities
└── view_helpers.php         # View formatting
```

---

## 🧪 Testing Helpers

You can also create test-specific helpers:

```php
// app/Helpers/AutoloadFiles/test_helpers.php
<?php

if (!function_exists('create_test_user')) {
    function create_test_user($attributes = []) {
        return \App\Models\User::factory()->create($attributes);
    }
}

if (!function_exists('mock_api_response')) {
    function mock_api_response($data, $status = 200) {
        return response()->json([
            'success' => true,
            'data' => $data,
        ], $status);
    }
}
```

---

## 🔍 Troubleshooting

### Common Issues and Solutions

#### **Issue**: Helpers not loading
```php
// Check directory exists
if (!is_dir(app_path('Helpers/AutoloadFiles'))) {
    mkdir(app_path('Helpers/AutoloadFiles'), 0755, true);
}
```

#### **Issue**: Function already exists errors
```php
// Always wrap functions in existence checks
if (!function_exists('my_helper_function')) {
    function my_helper_function($param) {
        // Function code here
    }
}
```

#### **Issue**: Autoloader not running
```php
// Manually trigger in AppServiceProvider if needed
public function boot()
{
    \Webmonks\LaravelApiModules\Helpers\HelperAutoloader::loadHelpers(
        app_path('Helpers/AutoloadFiles')
    );
}
```

### Debugging
```php
// Add debugging to see what's being loaded
public function boot()
{
    $helperDir = app_path('Helpers/AutoloadFiles');
    logger("Loading helpers from: $helperDir");
    
    if (is_dir($helperDir)) {
        $files = glob($helperDir . '/*.php');
        logger("Helper files found:", $files);
    }
    
    HelperAutoloader::loadHelpers($helperDir);
}
```

---

## 💡 Best Practices

### 1. **Function Naming**
- Use descriptive, clear function names
- Prefix with your app name for uniqueness: `myapp_format_currency()`
- Follow Laravel naming conventions

### 2. **Error Handling**
```php
if (!function_exists('safe_divide')) {
    function safe_divide($numerator, $denominator) {
        if ($denominator == 0) {
            throw new InvalidArgumentException('Division by zero');
        }
        return $numerator / $denominator;
    }
}
```

### 3. **Documentation**
```php
/**
 * Format a number as currency with proper locale support
 *
 * @param float $amount The amount to format
 * @param string $currency Currency code (USD, EUR, etc.)
 * @param string $locale Locale for formatting
 * @return string Formatted currency string
 */
if (!function_exists('format_money')) {
    function format_money($amount, $currency = 'USD', $locale = 'en_US') {
        // Implementation here
    }
}
```

### 4. **Type Safety**
```php
if (!function_exists('calculate_percentage')) {
    function calculate_percentage(float $value, float $total): float {
        if ($total == 0) {
            return 0.0;
        }
        return ($value / $total) * 100;
    }
}
```

---

The helper system is a powerful way to create reusable utility functions that enhance your Laravel API modules while maintaining clean, organized code.
