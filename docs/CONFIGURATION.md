# ⚙️ Configuration Reference

This comprehensive guide covers all configuration options available in `config/laravel-api-modules.php`.

The package works perfectly with zero configuration, but offers extensive customization to match your project's specific needs.

---

## 📋 Complete Configuration Options

### 🗂️ Directory Structure

Control where generated files are placed:

```php
return [
    // Module location - where all generated modules go
    'modules_dir' => 'app/Modules',
    
    // Core interfaces directory - repository contracts
    'core_interfaces_dir' => 'app/Core/Interfaces',
    
    // Migration files location
    'migration_path' => 'database/migrations',
    
    // Test files location  
    'tests_dir' => 'tests/Feature',
    'tests_unit_dir' => 'tests/Unit', // Auto-derived if not set
];
```

**Customization Examples:**
```php
// Domain-driven design structure
'modules_dir' => 'app/Domains',

// Different test organization
'tests_dir' => 'tests/Integration',
'tests_unit_dir' => 'tests/Spec',
```

---

### 🏷️ Namespace Configuration

Define how your classes are namespaced:

```php
return [
    // Root namespace for all modules
    'namespace' => 'App\\Modules',
    
    // Interface namespace
    'interface_namespace' => 'App\\Core\\Interfaces',
];
```

**Examples:**
```php
// Domain-driven namespacing
'namespace' => 'App\\Domains',
'interface_namespace' => 'App\\Contracts',

// Company-specific namespacing
'namespace' => 'YourCompany\\Modules',
'interface_namespace' => 'YourCompany\\Contracts',
```

---

### 🏗️ Base Class Configuration

Control inheritance and base class usage:

```php
return [
    // Enable BaseModel generation
    'enable_base_model' => true,
    
    // Enable BaseService generation
    'enable_base_service' => true,
    
    // What models should extend
    'model_extends_base' => 'BaseModel',
    'model_use_base' => 'App\\Models\\BaseModel',
    
    // What services should extend (if enabled)
    'service_extends_base' => 'BaseService',
    'service_use_base' => 'App\\Core\\Services\\BaseService',
];
```

**Custom Base Classes:**
```php
// Use your own base classes
'model_extends_base' => 'YourCustomBaseModel',
'model_use_base' => 'App\\Foundation\\YourCustomBaseModel',
```

---

### 🔧 Code Generation Options

Control what gets generated:

```php
return [
    // Generate migration files
    'generate_migration' => true,
    
    // Generate test files
    'generate_tests' => true,
    
    // Auto-discover and register module routes
    'auto_discover_routes' => true,
    
    // Custom stub path (advanced)
    'stubs_path' => null, // Uses default if null
    
    // Publish stubs for customization
    'publish_stubs' => true,
];
```

---

### 🏷️ Trait System Configuration

Configure which traits are included in your BaseModel:

```php
return [
    'base_model_traits' => [
        'ApiResponser' => true,         // ✅ Required - consistent API responses
        'ActivityLogHelper' => true,   // 📊 Track model changes
        'PdfGeneratorTrait' => true,   // 📄 PDF generation capabilities  
        'SmsSender' => true,           // 📱 SMS notification support
        'UserUpdater' => true,         // 👤 Auto-manage user tracking fields
    ],
];
```

**Custom Trait Configuration:**
```php
'base_model_traits' => [
    'ApiResponser' => true,           // Always required
    'ActivityLogHelper' => false,    // Disable if not needed
    'PdfGeneratorTrait' => false,    // Disable for API-only projects
    'SmsSender' => false,            // Disable if no SMS features
    'UserUpdater' => true,           // Enable user tracking
    'YourCustomTrait' => true,       // Add your own traits
],
```

---

### 👤 User Tracking Configuration

Configure how user IDs are resolved for audit trails:

```php
return [
    'user_id_source' => [
        'type' => 'request',              // Options: 'request', 'auth', 'custom'
        'key' => 'user_details.id',      // Dot notation for request data
        // 'resolver' => [App\Helpers\UserHelper::class, 'currentUserId'], // For custom type
    ],
];
```

**Configuration Options:**

| Type | Description | Configuration |
|------|-------------|---------------|
| **`request`** | Get user ID from request data | `'key' => 'user_details.id'` |
| **`auth`** | Use Laravel's authenticated user | `'key' => 'id'` (user model field) |
| **`custom`** | Custom resolver function | `'resolver' => [Class::class, 'method']` |

**Examples:**
```php
// Get from authenticated user
'user_id_source' => [
    'type' => 'auth',
    'key' => 'id', // User model field
],

// Custom resolver
'user_id_source' => [
    'type' => 'custom',
    'resolver' => [App\Services\UserService::class, 'getCurrentUserId'],
],

// Complex request path
'user_id_source' => [
    'type' => 'request',
    'key' => 'auth.user.id',
],
```

---

## 🎨 Advanced Customization

### Custom Stub Templates

Publish stubs for team-specific code generation:

```bash
# Publish stub templates
php artisan vendor:publish --tag=laravel-api-modules-stubs
```

Then customize any stub in `stubs/laravel-api-modules/`:

```php
// stubs/laravel-api-modules/controller.stub
<?php

namespace DummyNamespace\Controllers;

use App\Http\Controllers\Controller;
use DummyNamespace\Services\{{model}}Service;

class {{model}}Controller extends Controller
{
    // Your custom controller template
    // Add standard middleware, methods, etc.
}
```

### Environment-Specific Configuration

Different configurations for different environments:

```php
// config/laravel-api-modules.php
return [
    'generate_tests' => env('LARAVEL_MODULES_GENERATE_TESTS', true),
    'generate_migration' => env('LARAVEL_MODULES_GENERATE_MIGRATION', true),
    
    // Development vs Production differences
    'base_model_traits' => [
        'ApiResponser' => true,
        'ActivityLogHelper' => env('APP_ENV') !== 'testing', // Disable in tests
        'PdfGeneratorTrait' => env('ENABLE_PDF_FEATURES', true),
    ],
];
```

---

## 📊 Configuration Examples

### Minimal Configuration (API-Only)
```php
// Lightweight setup for pure API projects
return [
    'generate_migration' => true,
    'generate_tests' => true,
    'enable_base_model' => true,
    'enable_base_service' => true,
    
    'base_model_traits' => [
        'ApiResponser' => true,         // Essential for APIs
        'ActivityLogHelper' => false,  // Skip if not needed
        'PdfGeneratorTrait' => false,  // Not needed for APIs
        'SmsSender' => false,          // Not needed for APIs
        'UserUpdater' => true,         // Good for audit trails
    ],
];
```

### Full-Featured Configuration
```php
// Complete setup with all features
return [
    'modules_dir' => 'app/Modules',
    'core_interfaces_dir' => 'app/Core/Interfaces',
    'namespace' => 'App\\Modules',
    'interface_namespace' => 'App\\Core\\Interfaces',
    
    'generate_migration' => true,
    'generate_tests' => true,
    'auto_discover_routes' => true,
    
    'enable_base_model' => true,
    'enable_base_service' => true,
    
    'base_model_traits' => [
        'ApiResponser' => true,
        'ActivityLogHelper' => true,
        'PdfGeneratorTrait' => true,
        'SmsSender' => true,
        'UserUpdater' => true,
    ],
    
    'user_id_source' => [
        'type' => 'request',
        'key' => 'user_details.id',
    ],
];
```

### Enterprise Configuration
```php
// Enterprise-grade setup with custom namespacing
return [
    'modules_dir' => 'src/Modules',
    'core_interfaces_dir' => 'src/Core/Contracts',
    'namespace' => 'YourCompany\\Application\\Modules',
    'interface_namespace' => 'YourCompany\\Application\\Contracts',
    
    'generate_migration' => true,
    'generate_tests' => true,
    'auto_discover_routes' => true,
    
    'enable_base_model' => true,
    'enable_base_service' => true,
    'model_extends_base' => 'EnterpriseBaseModel',
    'model_use_base' => 'YourCompany\\Foundation\\EnterpriseBaseModel',
    
    'base_model_traits' => [
        'ApiResponser' => true,
        'ActivityLogHelper' => true,
        'PdfGeneratorTrait' => false,   // Custom PDF solution
        'SmsSender' => false,          // Custom messaging solution
        'UserUpdater' => true,
        'AuditLogger' => true,         // Custom enterprise trait
        'CacheManager' => true,        // Custom caching trait
    ],
];
```

---

## 🔧 Configuration Best Practices

### 1. **Start Simple, Grow Complex**
Begin with default configuration and customize as your project grows:

```php
// Start with this
'base_model_traits' => [
    'ApiResponser' => true,
    'UserUpdater' => true,
],

// Add features as needed
'base_model_traits' => [
    'ApiResponser' => true,
    'UserUpdater' => true,
    'ActivityLogHelper' => true, // Added when audit requirements appear
],
```

### 2. **Environment Awareness**
Use environment variables for settings that change between environments:

```php
'generate_tests' => env('GENERATE_MODULE_TESTS', true),
'auto_discover_routes' => env('AUTO_DISCOVER_ROUTES', true),
```

### 3. **Team Consistency**
Establish configuration standards for your team:

```php
// Team convention: Always generate tests and migrations
'generate_migration' => true,
'generate_tests' => true,

// Team convention: Use company namespace
'namespace' => 'YourCompany\\Modules',
```

### 4. **Performance Considerations**
Optimize configuration for your use case:

```php
// High-performance API: Minimal traits
'base_model_traits' => [
    'ApiResponser' => true,
    'UserUpdater' => false,     // Skip if not needed
    'ActivityLogHelper' => false, // Can be resource intensive
],

// Feature-rich application: Full traits
'base_model_traits' => [
    'ApiResponser' => true,
    'UserUpdater' => true,
    'ActivityLogHelper' => true,
    'PdfGeneratorTrait' => true,
],
```

---

## 💡 Configuration Tips

### Validation
After making configuration changes:

```bash
# Clear config cache
php artisan config:cache

# Verify configuration
php artisan config:show laravel-api-modules

# Test with a new module
php artisan make:module TestConfig --resource
```

### Troubleshooting Common Issues

**Issue: Traits not loading**
```php
// Ensure proper trait configuration
'base_model_traits' => [
    'ApiResponser' => true, // Must be boolean, not string
],
```

**Issue: Wrong namespaces in generated code**
```php
// Check namespace configuration
'namespace' => 'App\\Modules',  // Double backslashes required
```

**Issue: Custom stubs not working**
```bash
# Ensure stubs are published first
php artisan vendor:publish --tag=laravel-api-modules-stubs
```

---

This configuration system provides the flexibility to adapt Laravel API Modules to any project structure or team convention while maintaining consistency and best practices.
