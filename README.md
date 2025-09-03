# 🚀 Laravel API Modules

[![Latest Version on Packagist](https://img.shields.io/packagist/v/webmonks/laravel-api-modules.svg?style=flat-square)](https://packagist.org/packages/webmonks/laravel-api-modules)
[![Total Downloads](https://img.shields.io/packagist/dt/webmonks/laravel-api-modules.svg?style=flat-square)](https://packagist.org/packages/webmonks/laravel-api-modules)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-8.x%20to%2012.x-ff2d20?style=flat-square&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-7.4%20to%208.3-777bb4?style=flat-square&logo=php)](https://php.net)

**A powerful SOLID-principle Laravel modular code generator specifically designed for API-first projects.**

Transform your Laravel development with intelligent module generation that creates clean, maintainable, and scalable API architectures following industry best practices.

---

## ✨ Why Laravel API Modules?

### 🎯 **Built for API Excellence**
- **Zero Frontend Concerns**: Pure API-focused architecture
- **SOLID Principles**: Every generated component follows dependency injection and single responsibility
- **Repository Pattern**: Clean separation of data access logic
- **Service Layer**: Business logic abstraction for better testability

### ⚡ **Intelligent Generation**
- **Complete Module Scaffold**: Controllers, Models, Services, Repositories, Interfaces, Requests, Migrations, Tests
- **Two Generation Modes**: Simple list APIs or full CRUD resources
- **Auto-Wired Dependencies**: Everything is pre-configured and ready to use
- **Smart Naming**: Consistent naming conventions across all components

### 🔧 **Developer Experience**
- **One Command Setup**: Generate complete modules with a single artisan command
- **Zero Configuration**: Works out of the box with sensible defaults
- **Full Customization**: Publish and modify all templates to match your standards
- **IDE Friendly**: Proper type hints and interfaces for better development experience

---

## 📦 Installation

### Requirements

| Component   | Version                                           |
| ----------- | ------------------------------------------------- |
| **PHP**     | `^7.4` \| `^8.0` \| `^8.1` \| `^8.2` \| `^8.3`    |
| **Laravel** | `^8.0` \| `^9.0` \| `^10.0` \| `^11.0` \| `^12.0` |

### Quick Install

```bash
# Install the package
composer require webmonks/laravel-api-modules
```

### Configuration (Optional)

```bash
# Publish configuration for customization
php artisan vendor:publish --tag=laravel-api-modules-config

# Publish stub templates for team-specific modifications
php artisan vendor:publish --tag=laravel-api-modules-stubs
```

That's it! The package is auto-discovered and ready to use. 🎉

---

## 🚀 Quick Start

### Generate Your First Module

```bash
# Create a simple list-only API module
php artisan make:module Blog

# Create a full CRUD resource module
php artisan make:module Product --resource
```

### Remove Modules Safely

```bash
# Preview what would be removed (dry-run)
php artisan remove:module Blog --preview

# Remove with interactive confirmation
php artisan remove:module Blog

# Remove with automatic backup (default)
php artisan remove:module Product

# Remove without confirmations
php artisan remove:module Product --force

# Remove without creating backup
php artisan remove:module Product --force --no-backup
```

### What Gets Generated?

#### Simple Module (`php artisan make:module Blog`)
```
app/Modules/Blog/
├── Controllers/BlogController.php     # List endpoint only
├── Models/Blog.php                    # Eloquent model with traits
├── Services/BlogService.php          # Business logic layer
├── Repositories/BlogRepository.php   # Data access layer
├── Request/ListBlogRequest.php       # Validation for list endpoint
└── routes.php                        # Auto-registered routes

app/Core/
├── Interfaces/BlogRepositoryInterface.php  # Repository contract
└── Providers/RepositoryServiceProvider.php # Auto-generated bindings

database/migrations/
└── xxxx_xx_xx_xxxxxx_create_blogs_table.php

tests/
├── Feature/Modules/Blog/BlogFeatureTest.php
└── Unit/Modules/Blog/BlogUnitTest.php
```

#### Resource Module (`php artisan make:module Product --resource`)
```
app/Modules/Product/
├── Controllers/ProductController.php          # Full CRUD endpoints
├── Models/Product.php                        # Eloquent model
├── Services/ProductService.php               # Complete business logic
├── Repositories/ProductRepository.php        # Full data operations
├── Request/
│   ├── ListProductRequest.php               # List validation
│   ├── ViewProductRequest.php               # View validation
│   ├── CreateProductRequest.php             # Create validation
│   ├── UpdateProductRequest.php             # Update validation
│   └── DeleteProductRequest.php             # Delete validation
└── routes.php                               # All CRUD routes
```

### Your Project Structure

After generating your first module, your Laravel application will have this enhanced structure:

```
app/
├── Core/                                    # 🏗️ Architecture Layer
│   ├── Interfaces/                         # Repository contracts
│   │   └── BlogRepositoryInterface.php
│   ├── Providers/                          # Auto-generated service bindings
│   │   └── RepositoryServiceProvider.php
│   ├── Services/                           # Shared base services
│   │   └── BaseService.php
│   └── Traits/                             # Reusable functionality
│       ├── ApiResponser.php               # Standard API responses
│       ├── ActivityLogHelper.php          # Model activity tracking
│       ├── PdfGeneratorTrait.php          # PDF generation
│       ├── SmsSender.php                  # SMS notifications
│       └── UserUpdater.php                # Auto user tracking
├── Helpers/                                # 🔧 Utility Functions
│   └── AutoloadFiles/                     # Auto-loaded helpers
│       └── string_helpers.php
├── Models/                                 # 🗃️ Shared Models
│   └── BaseModel.php                      # Enhanced base model
└── Modules/                               # 🎯 Your API Modules
    └── Blog/
        ├── Controllers/BlogController.php
        ├── Models/Blog.php
        ├── Repositories/BlogRepository.php
        ├── Services/BlogService.php
        ├── Request/ListBlogRequest.php
        └── routes.php                     # Auto-discovered routes

config/
└── laravel-api-modules.php               # Package configuration

database/migrations/
└── 2024_01_01_000000_create_blogs_table.php

tests/
├── Feature/Modules/Blog/BlogFeatureTest.php
└── Unit/Modules/Blog/BlogUnitTest.php
```

---

## 🔧 Core Features

### 🗑️ **Safe Module Removal**
**Complete cleanup with safety checks!** The package provides:

- **🔍 Preview Mode**: See exactly what files will be removed before deletion
- **📦 Automatic Backup**: Creates timestamped backups before removal (optional)
- **⚠️ Multi-stage Confirmations**: Multiple safety prompts prevent accidental deletion
- **🧹 Complete Cleanup**: Removes all related files (controllers, models, tests, migrations, interfaces)
- **🔄 Repository Binding Cleanup**: Automatically cleans up service provider bindings
- **🚨 Security Validation**: Prevents path traversal and validates module names

```bash
# Safe removal with all protections
php artisan remove:module UserProfile

# Quick preview of what would be removed
php artisan remove:module UserProfile --preview

# Force removal without confirmations
php artisan remove:module UserProfile --force --no-backup
```

### 🔗 Automatic Repository Binding

**Zero Configuration Required!** The package automatically:

- Generates `RepositoryServiceProvider.php` to bind interfaces to implementations
- Registers the provider in Laravel's service container
- Creates proper dependency injection for all your modules

```php
// This happens automatically - no manual binding needed!
$this->app->bind(
    BlogRepositoryInterface::class,
    BlogRepository::class
);
```

### 🏷️ Smart Traits System

The package includes battle-tested traits for common API functionality:

| Trait                   | Purpose                                       | Auto-Included |
| ----------------------- | --------------------------------------------- | ------------- |
| **`ApiResponser`**      | Consistent API response format                | ✅ Required    |
| **`ActivityLogHelper`** | Track model changes and actions               | ⚙️ Optional    |
| **`PdfGeneratorTrait`** | Generate PDFs from Blade templates            | ⚙️ Optional    |
| **`SmsSender`**         | Send SMS via Twilio with logging              | ⚙️ Optional    |
| **`UserUpdater`**       | Auto-manage `created_by`, `updated_by` fields | ⚙️ Optional    |

### 🔄 Helper Autoloader

Drop any PHP helper files into `app/Helpers/AutoloadFiles/` and they're automatically available throughout your application:

```php
// app/Helpers/AutoloadFiles/api_helpers.php
function transform_response($data, $message = 'Success') {
    return ['data' => $data, 'message' => $message];
}

// Available everywhere in your app automatically!
return transform_response($users, 'Users retrieved successfully');
```

---

## ⚙️ Configuration

The package works perfectly with zero configuration, but offers extensive customization options:

<details>
<summary><strong>📋 View Configuration Options</strong></summary>

```php
// config/laravel-api-modules.php
return [
    // Directory Structure
    'modules_dir' => 'app/Modules',
    'core_interfaces_dir' => 'app/Core/Interfaces',
    
    // Namespaces
    'namespace' => 'App\\Modules',
    'interface_namespace' => 'App\\Core\\Interfaces',
    
    // Base Classes
    'enable_base_model' => true,        // Generate BaseModel
    'enable_base_service' => true,      // Generate BaseService
    'model_extends_base' => 'BaseModel',
    
    // Code Generation
    'generate_migration' => true,       // Create migrations
    'generate_tests' => true,          // Create test files
    'auto_discover_routes' => true,    // Auto-register routes
    
    // Traits Configuration
    'base_model_traits' => [
        'ApiResponser' => true,         // Required
        'ActivityLogHelper' => true,   // Optional
        'PdfGeneratorTrait' => true,   // Optional
        'SmsSender' => true,           // Optional
        'UserUpdater' => true,         // Optional
    ],
];
```

</details>

---

## 💡 Usage Examples

### Example 1: Blog API Module

```bash
# Generate a simple blog list API
php artisan make:module Blog

# Remove the blog module safely (with backup)
php artisan remove:module Blog
```

Generated controller will have a clean, testable structure:

```php
// app/Modules/Blog/Controllers/BlogController.php
class BlogController extends Controller
{
    protected $blogService;

    public function __construct(BlogService $blogService)
    {
        $this->blogService = $blogService; // Auto-injected
    }

    public function list(ListBlogRequest $request)
    {
        $response = $this->blogService->listBlogs($request->validated());
        return $this->successResponse(
            $response, 
            'Blogs retrieved successfully', 
            Response::HTTP_OK
        );
    }
}
```

### Example 2: Full CRUD Product API

```bash
# Generate a complete product management API
php artisan make:module Product --resource

# Preview what would be removed before deletion
php artisan remove:module Product --preview

# Remove with force (skip confirmations)
php artisan remove:module Product --force
```

This creates a full API with endpoints:
- `GET /api/products` - List products with filtering
- `GET /api/products/{id}` - Get single product
- `POST /api/products` - Create new product
- `PUT /api/products/{id}` - Update product
- `DELETE /api/products/{id}` - Delete product

### Example 3: Custom Helper Integration

```php
// app/Helpers/AutoloadFiles/product_helpers.php
function calculate_discount($original_price, $discount_percent) {
    return $original_price * (1 - $discount_percent / 100);
}

function format_currency($amount) {
    return '$' . number_format($amount, 2);
}
```

```php
// Use anywhere in your application
$discounted_price = calculate_discount($product->price, 15);
$formatted_price = format_currency($discounted_price);
```

---

## 🔧 Advanced Customization

### Custom Stub Templates

Publish stubs and modify them to match your team's conventions:

```bash
php artisan vendor:publish --tag=laravel-api-modules-stubs
```

Edit any stub in `stubs/laravel-api-modules/` to customize generated code:

```php
// stubs/laravel-api-modules/controller.stub
class {{model}}Controller extends Controller
{
    // Your custom controller template
    // Add your standard methods, middleware, etc.
}
```

### Extending Base Classes

The generated `BaseModel` and `BaseService` can be extended with your common functionality:

```php
// app/Models/BaseModel.php - Auto-generated, customize as needed
abstract class BaseModel extends Model
{
    use ApiResponser, ActivityLogHelper, UserUpdater;
    
    // Add your common model methods here
    public function scopeActive($query) {
        return $query->where('is_active', true);
    }
}
```

---

## 🧪 Testing

The package generates comprehensive test files for each module:

```php
// tests/Feature/Modules/Blog/BlogFeatureTest.php
class BlogFeatureTest extends TestCase
{
    public function test_can_list_blogs()
    {
        $response = $this->getJson('/api/blogs');
        $response->assertStatus(200)
                ->assertJsonStructure(['data', 'message']);
    }
}
```

Run tests for your modules:

```bash
# Run all tests
php artisan test

# Run specific module tests
php artisan test tests/Feature/Modules/Blog/
php artisan test tests/Unit/Modules/Blog/
```

---

## 📚 Documentation

### Complete Guides
- **[📁 Directory Structure Deep Dive](docs/STRUCTURE.md)** - Understand the generated architecture
- **[🔧 Helper System Guide](docs/HELPERS.md)** - Master the auto-loader and create custom helpers  
- **[🏷️ Traits Reference](docs/TRAITS.md)** - Leverage built-in traits and create your own
- **[⚙️ Configuration Reference](docs/CONFIGURATION.md)** - Customize every aspect of generation
- **[🚀 Migration Guide](docs/UPGRADE.md)** - Upgrade between versions smoothly

### Resources
- **[📋 Changelog](docs/CHANGELOG.md)** - Track all changes and improvements
- **[🤝 Contributing Guidelines](docs/CONTRIBUTING.md)** - Join our development community
- **[🔐 Security Policy](SECURITY.md)** - Report security vulnerabilities
- **[⚖️ Code of Conduct](CODE_OF_CONDUCT.md)** - Community standards

---

## 🤝 Contributing

We welcome contributions from the community! Whether it's:

- 🐛 **Bug Reports**: Found an issue? Let us know!
- 💡 **Feature Requests**: Have ideas for improvements?
- 🔧 **Code Contributions**: Submit pull requests with enhancements
- 📖 **Documentation**: Help improve our guides and examples

See our [Contributing Guidelines](docs/CONTRIBUTING.md) for details.

---

## 🏆 Credits

**Laravel API Modules** is crafted with ❤️ by [WebMonks Technologies](https://webmonks.in)

- **Lead Developer**: [Darshan Baraiya](mailto:darshan@webmonks.in)
- **Company**: [WebMonks Technologies](https://webmonks.in)

### Built With
- **Laravel** - The PHP Framework for Web Artisans
- **PHP** - A popular general-purpose scripting language
- **SOLID Principles** - Object-oriented design principles
- **Repository Pattern** - Clean architecture pattern

---

## 📄 License

This package is open-sourced software licensed under the [MIT License](LICENSE).

---

<div align="center">

### 🌟 Star this repository if it helped you!

**Made with ❤️ for the Laravel community**

[⭐ Give us a star](https://github.com/webmonks/laravel-api-modules) • [📦 View on Packagist](https://packagist.org/packages/webmonks/laravel-api-modules) • [🐛 Report Issues](https://github.com/webmonks/laravel-api-modules/issues)

</div>
