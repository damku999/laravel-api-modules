# 📚 API Reference

This guide provides comprehensive information about the generated API endpoints, their structure, and usage examples.

---

## 🎯 API Overview

Laravel API Modules generates clean, consistent REST APIs following industry standards. All endpoints return JSON responses with consistent structure and proper HTTP status codes.

### Base Response Structure

All API responses follow this consistent structure:

```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    // Response data here
  }
}
```

### Error Response Structure

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

---

## 🛣️ Generated Endpoints

### Simple Module (`php artisan make:module Blog`)

Generates a single list endpoint:

| Method | URI | Action | Description |
|--------|-----|--------|-------------|
| `GET` | `/api/blogs` | `list` | Retrieve paginated list of blogs |

### Resource Module (`php artisan make:module Product --resource`)

Generates full CRUD endpoints:

| Method | URI | Action | Description |
|--------|-----|--------|-------------|
| `GET` | `/api/products` | `list` | Retrieve paginated list of products |
| `GET` | `/api/products/{id}` | `view` | Retrieve single product by ID |
| `POST` | `/api/products` | `create` | Create new product |
| `PUT` | `/api/products/{id}` | `update` | Update existing product |
| `DELETE` | `/api/products/{id}` | `delete` | Delete product by ID |

---

## 📋 Detailed Endpoint Documentation

### List Endpoint (`GET /api/{modules}`)

Retrieve a paginated list of resources with optional filtering and sorting.

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page (default: 15, max: 100) |
| `search` | string | No | Search term for filtering results |
| `sort_by` | string | No | Field to sort by (default: 'id') |
| `sort_order` | string | No | Sort order: 'asc' or 'desc' (default: 'asc') |

#### Request Example

```bash
GET /api/products?page=1&per_page=10&search=laptop&sort_by=name&sort_order=asc
```

#### Response Example

```json
{
  "success": true,
  "message": "Products retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "MacBook Pro",
      "price": 1299.99,
      "description": "High-performance laptop",
      "created_at": "2024-01-15T10:30:00.000000Z",
      "updated_at": "2024-01-15T10:30:00.000000Z"
    },
    {
      "id": 2,
      "name": "Dell XPS 13",
      "price": 999.99,
      "description": "Compact ultrabook",
      "created_at": "2024-01-16T08:15:00.000000Z",
      "updated_at": "2024-01-16T08:15:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 25,
    "last_page": 3,
    "from": 1,
    "to": 10
  }
}
```

#### HTTP Status Codes

| Code | Description |
|------|-------------|
| `200` | Success - Resources retrieved |
| `400` | Bad Request - Invalid parameters |
| `500` | Internal Server Error |

---

### View Endpoint (`GET /api/{modules}/{id}`)

Retrieve a single resource by its ID.

#### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Unique identifier of the resource |

#### Request Example

```bash
GET /api/products/1
```

#### Response Example

```json
{
  "success": true,
  "message": "Product retrieved successfully",
  "data": {
    "id": 1,
    "name": "MacBook Pro",
    "price": 1299.99,
    "description": "High-performance laptop for professionals",
    "category_id": 3,
    "stock_quantity": 15,
    "is_active": true,
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

#### HTTP Status Codes

| Code | Description |
|------|-------------|
| `200` | Success - Resource found and returned |
| `404` | Not Found - Resource doesn't exist |
| `400` | Bad Request - Invalid ID format |

---

### Create Endpoint (`POST /api/{modules}`)

Create a new resource.

#### Request Headers

```
Content-Type: application/json
Accept: application/json
```

#### Request Body Example

```json
{
  "name": "iPhone 15 Pro",
  "price": 999.99,
  "description": "Latest iPhone with advanced features",
  "category_id": 1,
  "stock_quantity": 50,
  "is_active": true
}
```

#### Response Example (Success)

```json
{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "id": 15,
    "name": "iPhone 15 Pro",
    "price": 999.99,
    "description": "Latest iPhone with advanced features",
    "category_id": 1,
    "stock_quantity": 50,
    "is_active": true,
    "created_at": "2024-01-20T14:25:00.000000Z",
    "updated_at": "2024-01-20T14:25:00.000000Z"
  }
}
```

#### Response Example (Validation Error)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."],
    "price": ["The price must be a number greater than 0."],
    "category_id": ["The selected category is invalid."]
  }
}
```

#### HTTP Status Codes

| Code | Description |
|------|-------------|
| `201` | Created - Resource successfully created |
| `400` | Bad Request - Invalid request data |
| `422` | Unprocessable Entity - Validation errors |
| `500` | Internal Server Error |

---

### Update Endpoint (`PUT /api/{modules}/{id}`)

Update an existing resource.

#### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Unique identifier of the resource to update |

#### Request Headers

```
Content-Type: application/json
Accept: application/json
```

#### Request Body Example

```json
{
  "name": "iPhone 15 Pro Max",
  "price": 1199.99,
  "description": "Updated description with new features",
  "stock_quantity": 25
}
```

#### Response Example (Success)

```json
{
  "success": true,
  "message": "Product updated successfully",
  "data": {
    "id": 15,
    "name": "iPhone 15 Pro Max",
    "price": 1199.99,
    "description": "Updated description with new features",
    "category_id": 1,
    "stock_quantity": 25,
    "is_active": true,
    "created_at": "2024-01-20T14:25:00.000000Z",
    "updated_at": "2024-01-20T16:45:00.000000Z"
  }
}
```

#### Response Example (Not Found)

```json
{
  "success": false,
  "message": "Product not found"
}
```

#### HTTP Status Codes

| Code | Description |
|------|-------------|
| `200` | Success - Resource updated |
| `404` | Not Found - Resource doesn't exist |
| `422` | Unprocessable Entity - Validation errors |
| `500` | Internal Server Error |

---

### Delete Endpoint (`DELETE /api/{modules}/{id}`)

Delete a resource by its ID.

#### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Unique identifier of the resource to delete |

#### Request Example

```bash
DELETE /api/products/15
```

#### Response Example (Success)

```json
{
  "success": true,
  "message": "Product deleted successfully",
  "data": {}
}
```

#### Response Example (Not Found)

```json
{
  "success": false,
  "message": "Product not found"
}
```

#### HTTP Status Codes

| Code | Description |
|------|-------------|
| `200` | Success - Resource deleted |
| `404` | Not Found - Resource doesn't exist |
| `409` | Conflict - Resource cannot be deleted |
| `500` | Internal Server Error |

---

## 🔐 Authentication & Authorization

### Authentication

The generated APIs can be easily integrated with Laravel's built-in authentication systems:

#### Using Laravel Sanctum

```php
// In your generated controller
class ProductController extends Controller
{
    public function __construct(ProductService $productService)
    {
        $this->middleware('auth:sanctum');
        $this->productService = $productService;
    }
}
```

#### Using JWT or Passport

```php
// Apply middleware in routes file
Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/products', [ProductController::class, 'list']);
    Route::post('/products', [ProductController::class, 'create']);
    // ... other routes
});
```

### Authorization

Implement authorization using Laravel's built-in policies:

```php
// Create policy
php artisan make:policy ProductPolicy --model=Product

// Use in controller
public function view(ViewProductRequest $request)
{
    $product = $this->productService->viewProduct($request->validated()['id']);
    
    $this->authorize('view', $product);
    
    return $this->successResponse($product, 'Product retrieved successfully');
}
```

---

## 📊 Filtering and Searching

### Basic Filtering

Generated services support basic filtering out of the box:

```bash
# Filter by specific field values
GET /api/products?category_id=1&is_active=true

# Date range filtering
GET /api/products?created_after=2024-01-01&created_before=2024-12-31
```

### Advanced Search

Implement advanced search in your service layer:

```php
// In ProductService.php
public function listProducts(array $filters = [])
{
    $query = $this->productRepository->query();
    
    // Search across multiple fields
    if (!empty($filters['search'])) {
        $query->where(function($q) use ($filters) {
            $q->where('name', 'LIKE', "%{$filters['search']}%")
              ->orWhere('description', 'LIKE', "%{$filters['search']}%");
        });
    }
    
    // Price range filtering
    if (!empty($filters['min_price'])) {
        $query->where('price', '>=', $filters['min_price']);
    }
    
    if (!empty($filters['max_price'])) {
        $query->where('price', '<=', $filters['max_price']);
    }
    
    return $query->paginate($filters['per_page'] ?? 15);
}
```

---

## 🚀 Performance Optimization

### Pagination

All list endpoints support pagination:

```bash
# Get page 2 with 20 items per page
GET /api/products?page=2&per_page=20
```

### Eager Loading

Optimize database queries by implementing eager loading:

```php
// In ProductRepository.php
public function getAllProducts(array $filters = [])
{
    return Product::with(['category', 'tags'])
        ->when(!empty($filters['search']), function($query) use ($filters) {
            return $query->where('name', 'LIKE', "%{$filters['search']}%");
        })
        ->paginate($filters['per_page'] ?? 15);
}
```

### Caching

Implement caching for frequently accessed data:

```php
// In ProductService.php
public function viewProduct(int $id)
{
    return Cache::remember("product.{$id}", 3600, function () use ($id) {
        return $this->productRepository->getProductById($id);
    });
}
```

---

## 📝 Request Validation

### Validation Rules

Each endpoint has dedicated request classes with validation rules:

```php
// CreateProductRequest.php
public function rules()
{
    return [
        'name' => 'required|string|max:255|unique:products,name',
        'price' => 'required|numeric|min:0.01|max:999999.99',
        'description' => 'required|string|min:10|max:1000',
        'category_id' => 'required|exists:categories,id',
        'stock_quantity' => 'required|integer|min:0',
        'is_active' => 'boolean',
    ];
}
```

### Custom Validation Messages

```php
public function messages()
{
    return [
        'name.required' => 'Product name is required',
        'name.unique' => 'A product with this name already exists',
        'price.required' => 'Price is required',
        'price.min' => 'Price must be at least $0.01',
    ];
}
```

### Conditional Validation

```php
public function rules()
{
    $rules = [
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0.01',
    ];
    
    // Different rules for update vs create
    if ($this->isMethod('PUT')) {
        $rules['name'] .= '|unique:products,name,' . $this->route('id');
    } else {
        $rules['name'] .= '|unique:products,name';
    }
    
    return $rules;
}
```

---

## 🔧 Customization Examples

### Custom Response Format

Modify the ApiResponser trait to change response format:

```php
// In app/Core/Traits/ApiResponser.php
public function successResponse($data, $message = 'Success', $code = 200)
{
    return response()->json([
        'status' => 'success',
        'message' => $message,
        'data' => $data,
        'timestamp' => now()->toISOString(),
    ], $code);
}
```

### Adding Metadata

Include additional metadata in responses:

```php
public function listProducts(array $filters = [])
{
    $products = $this->productRepository->getAllProducts($filters);
    
    return [
        'items' => $products->items(),
        'pagination' => [
            'current_page' => $products->currentPage(),
            'total_pages' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total_items' => $products->total(),
        ],
        'filters_applied' => array_filter($filters),
        'generated_at' => now()->toISOString(),
    ];
}
```

### Custom Error Handling

Implement custom error responses:

```php
// In your Controller
public function create(CreateProductRequest $request)
{
    try {
        $result = $this->productService->createProduct($request->validated());
        
        if (isset($result['action_status']) && $result['action_status'] === 'fail') {
            return $this->errorResponse(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $result['error'] ?? 'Failed to create product',
                $result['validation_errors'] ?? []
            );
        }
        
        return $this->successResponse(
            $result,
            'Product created successfully',
            Response::HTTP_CREATED
        );
        
    } catch (ValidationException $e) {
        return $this->errorResponse(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Validation failed',
            $e->errors()
        );
    } catch (\Exception $e) {
        Log::error('Product creation failed', [
            'error' => $e->getMessage(),
            'request_data' => $request->validated()
        ]);
        
        return $this->errorResponse(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'An unexpected error occurred'
        );
    }
}
```

---

## 🧪 Testing Your APIs

### Feature Testing

Test your generated endpoints:

```php
// tests/Feature/Modules/Product/ProductFeatureTest.php
class ProductFeatureTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_list_products()
    {
        Product::factory()->count(5)->create();
        
        $response = $this->getJson('/api/products');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        '*' => ['id', 'name', 'price', 'description']
                    ]
                ]);
    }
    
    public function test_can_create_product()
    {
        $productData = [
            'name' => 'Test Product',
            'price' => 99.99,
            'description' => 'Test product description',
            'category_id' => Category::factory()->create()->id,
        ];
        
        $response = $this->postJson('/api/products', $productData);
        
        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Product created successfully'
                ]);
                
        $this->assertDatabaseHas('products', $productData);
    }
}
```

---

This API reference provides a comprehensive overview of the generated endpoints. Each module will follow these patterns while allowing for customization based on your specific requirements.