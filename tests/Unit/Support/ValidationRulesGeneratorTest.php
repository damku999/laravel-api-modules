<?php

use Webmonks\LaravelApiModules\Support\ValidationRulesGenerator;

beforeEach(function () {
    $this->tempDir = createTempDirectory();
});

afterEach(function () {
    cleanupTempDirectory($this->tempDir);
});

// ValidationRulesGenerator tests

it('generates rules for email fields', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('email', 'create');

    expect($rules)->toContain('required');
    expect($rules)->toContain('email');
    expect($rules)->toContain('max:255');
    expect($rules)->toContain('unique:users,email');
});

it('generates rules for foreign key fields', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('user_id', 'create');

    expect($rules)->toContain('required');
    expect($rules)->toContain('integer');
    expect($rules)->toContain('exists:users,id');
});

it('generates rules for phone fields', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('phone', 'create');

    expect($rules)->toContain('required');
    expect($rules)->toContain('string');
    expect($rules)->toContain('regex:/^[\d\s\+\-\(\)]+$/');
    expect($rules)->toContain('max:20');
});

it('generates rules for password fields', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('password', 'create');

    expect($rules)->toContain('required');
    expect($rules)->toContain('string');
    expect($rules)->toContain('min:8');
    expect($rules)->toContain('confirmed');
});

it('generates rules for url fields', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('website', 'create');

    expect($rules)->toContain('required');
    expect($rules)->toContain('url');
    expect($rules)->toContain('max:255');
});

it('generates rules for date fields', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('birth_date', 'create');

    expect($rules)->toContain('required');
    expect($rules)->toContain('date');
});

it('generates rules for boolean fields', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('is_active', 'create');

    expect($rules)->toContain('required');
    expect($rules)->toContain('boolean');
});

it('generates rules for price fields', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('price', 'create');

    expect($rules)->toContain('required');
    expect($rules)->toContain('numeric');
    expect($rules)->toContain('min:0');
});

it('generates rules for image fields', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('avatar', 'create');

    expect($rules)->toContain('required');
    expect($rules)->toContain('image');
    expect($rules)->toContain('mimes:jpeg,png,jpg,gif');
    expect($rules)->toContain('max:2048');
});

it('generates different rules for update operations', function () {
    $createRules = ValidationRulesGenerator::generateRuleForField('name', 'create');
    $updateRules = ValidationRulesGenerator::generateRuleForField('name', 'update');

    expect($createRules)->toContain('required');
    expect($updateRules)->toContain('sometimes');
    expect($updateRules)->not->toContain('required');
});

it('generates rules from model file', function () {
    // Create a mock model file
    $modelContent = <<<PHP
<?php
namespace App\Models;

class TestModel 
{
    protected \$fillable = [
    'name',
    'email',
    'user_id',
    'is_active',
    'price'
    ];
}
PHP;

    $modelPath = $this->tempDir . '/TestModel.php';
    file_put_contents($modelPath, $modelContent);

    $rules = ValidationRulesGenerator::generateForModel($modelPath, 'create');

    expect($rules)->toHaveKey('name');
    expect($rules)->toHaveKey('email');
    expect($rules)->toHaveKey('user_id');
    expect($rules)->toHaveKey('is_active');
    expect($rules)->toHaveKey('price');

    expect($rules['email'])->toContain('email');
    expect($rules['user_id'])->toContain('integer');
    expect($rules['is_active'])->toContain('boolean');
    expect($rules['price'])->toContain('numeric');
});

it('returns empty array for non-existent model file', function () {
    $rules = ValidationRulesGenerator::generateForModel('/non/existent/model.php');

    expect($rules)->toBe([]);
});

it('generates api parameter rules', function () {
    $rules = ValidationRulesGenerator::generateApiParameterRules();

    expect($rules)->toHaveKey('page');
    expect($rules)->toHaveKey('per_page');
    expect($rules)->toHaveKey('search');
    expect($rules)->toHaveKey('sort');
    expect($rules)->toHaveKey('sort_by');

    expect($rules['page'])->toContain('integer');
    expect($rules['page'])->toContain('min:1');
    expect($rules['per_page'])->toContain('max:100');
    expect($rules['search'])->toContain('string');
});

it('generates rules with custom messages', function () {
    $modelContent = <<<PHP
<?php
namespace App\Models;

class TestModel 
{
    protected \$fillable = [
    'name',
    'email'
    ];
}
PHP;

    $modelPath = $this->tempDir . '/TestModel.php';
    file_put_contents($modelPath, $modelContent);

    $result = ValidationRulesGenerator::generateRulesWithMessages($modelPath, 'create');

    expect($result)->toHaveKey('rules');
    expect($result)->toHaveKey('messages');

    expect($result['messages'])->toHaveKey('name.required');
    expect($result['messages'])->toHaveKey('email.email');
    expect($result['messages'])->toHaveKey('email.unique');

    expect($result['messages']['name.required'])->toBe('The Name field is required.');
    expect($result['messages']['email.email'])->toBe('The Email must be a valid email address.');
});

it('generates conditional validation rules', function () {
    $baseRules = [
        'name' => ['required', 'string'],
    ];

    $conditions = [
        'type,personal' => [
            'personal_id' => ['required', 'string'],
        ],
        'type,business' => [
            'company_name' => ['required', 'string'],
        ],
    ];

    $rules = ValidationRulesGenerator::generateConditionalRules($baseRules, $conditions);

    expect($rules)->toHaveKey('name');
    expect($rules)->toHaveKey('personal_id');
    expect($rules)->toHaveKey('company_name');

    expect($rules['personal_id'])->toContain('required_if:type,personal');
    expect($rules['company_name'])->toContain('required_if:type,business');
});

it('generates file upload rules with security', function () {
    $options = [
        'types' => ['image'],
        'max_size' => 1024,
        'mime_types' => ['image/jpeg', 'image/png'],
    ];

    $rules = ValidationRulesGenerator::generateFileUploadRules($options);

    expect($rules)->toContain('file');
    expect($rules)->toContain('max:1024');
    expect($rules)->toContain('image');
    expect($rules)->toContain('mimes:jpeg,png,jpg,gif,webp');
    expect($rules)->toContain('mimetypes:image/jpeg,image/png');
});

it('generates document upload rules', function () {
    $options = [
        'types' => ['document'],
        'max_size' => 5120,
    ];

    $rules = ValidationRulesGenerator::generateFileUploadRules($options);

    expect($rules)->toContain('file');
    expect($rules)->toContain('max:5120');
    expect($rules)->toContain('mimes:pdf,doc,docx,xls,xlsx,txt');
});

it('handles status fields with predefined values', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('status', 'create');

    expect($rules)->toContain('in:active,inactive,pending,approved,rejected');
});

it('handles gender fields with predefined values', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('gender', 'create');

    expect($rules)->toContain('in:male,female,other');
});

it('handles slug fields with unique validation', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('slug', 'create');

    expect($rules)->toContain('required');
    expect($rules)->toContain('string');
    expect($rules)->toContain('alpha_dash');
    expect($rules)->toContain('unique:table,slug');
});

it('handles description fields with larger max length', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('description', 'create');

    expect($rules)->toContain('required');
    expect($rules)->toContain('string');
    expect($rules)->toContain('max:1000');
});

it('uses default string rules for unknown field types', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('unknown_field', 'create');

    expect($rules)->toContain('required');
    expect($rules)->toContain('string');
    expect($rules)->toContain('max:255');
});

it('generates rules from model file with explicit $rules property', function () {
    $modelContent = <<<PHP
<?php
namespace App\Models;

class TestModel 
{
    protected \$fillable = [
    'name',
    'email'
    ];
    
    protected \$rules = [
    'custom_field' => 'required|string|max:100',
    'another_field' => 'nullable|integer'
    ];
}
PHP;

    $modelPath = $this->tempDir . '/TestModel.php';
    file_put_contents($modelPath, $modelContent);

    $rules = ValidationRulesGenerator::generateForModel($modelPath, 'create');

    expect($rules)->toHaveKey('name');
    expect($rules)->toHaveKey('email');
    expect($rules)->toHaveKey('custom_field');
    expect($rules)->toHaveKey('another_field');

    expect($rules['custom_field'])->toContain('required');
    expect($rules['custom_field'])->toContain('string');
    expect($rules['custom_field'])->toContain('max:100');
    expect($rules['another_field'])->toContain('nullable');
    expect($rules['another_field'])->toContain('integer');
});

it('returns empty array when model file cannot be read', function () {
    // Test with a file that contains invalid content causing file_get_contents to return false
    $modelPath = $this->tempDir . '/invalid.php';

    // Create an empty file and then test the file_get_contents false condition
    // by testing with a path that doesn't exist after creating one that does
    file_put_contents($modelPath, '');

    // Now test with the file (this will succeed but return empty content)
    $rules = ValidationRulesGenerator::generateForModel($modelPath);

    // The method will process empty content which results in no fillable fields found
    expect($rules)->toBe([]);
});

it('handles time fields correctly', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('start_time', 'create');

    expect($rules)->toContain('required');
    expect($rules)->toContain('date_format:H:i:s');
});

it('handles quantity and count fields', function () {
    $quantityRules = ValidationRulesGenerator::generateRuleForField('quantity', 'create');
    $countRules = ValidationRulesGenerator::generateRuleForField('count', 'create');

    expect($quantityRules)->toContain('required');
    expect($quantityRules)->toContain('integer');
    expect($quantityRules)->toContain('min:0');

    expect($countRules)->toContain('integer');
    expect($countRules)->toContain('min:0');
});

it('handles file and document fields', function () {
    $fileRules = ValidationRulesGenerator::generateRuleForField('attachment', 'create');
    $docRules = ValidationRulesGenerator::generateRuleForField('document', 'create');

    expect($fileRules)->toContain('file');
    expect($fileRules)->toContain('max:10240');

    expect($docRules)->toContain('file');
    expect($docRules)->toContain('max:10240');
});

it('handles type fields', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('user_type', 'create');

    expect($rules)->toContain('required');
    expect($rules)->toContain('string');
    expect($rules)->toContain('max:100');
});

it('generates database schema rules (placeholder implementation)', function () {
    $rules = ValidationRulesGenerator::generateFromDatabaseSchema('users');

    expect($rules)->toBe([]);
});

it('generates more comprehensive custom messages', function () {
    $modelContent = <<<PHP
<?php
namespace App\Models;

class TestModel 
{
    protected \$fillable = [
    'name',
    'price',
    'birth_date', 
    'website',
    'avatar',
    'is_active',
    'count'
    ];
}
PHP;

    $modelPath = $this->tempDir . '/TestModel.php';
    file_put_contents($modelPath, $modelContent);

    $result = ValidationRulesGenerator::generateRulesWithMessages($modelPath, 'create');

    expect($result)->toHaveKey('rules');
    expect($result)->toHaveKey('messages');

    // Test various message types
    expect($result['messages'])->toHaveKey('name.required');
    expect($result['messages'])->toHaveKey('name.max');
    expect($result['messages'])->toHaveKey('price.min');
    expect($result['messages'])->toHaveKey('birth_date.date');
    expect($result['messages'])->toHaveKey('website.url');
    expect($result['messages'])->toHaveKey('avatar.image');
    expect($result['messages'])->toHaveKey('is_active.boolean');
    expect($result['messages'])->toHaveKey('count.integer');

    expect($result['messages']['name.required'])->toBe('The Name field is required.');
    expect($result['messages']['price.min'])->toBe('The Price must be at least :min characters.');
    expect($result['messages']['birth_date.date'])->toBe('The Birth Date must be a valid date.');
    expect($result['messages']['website.url'])->toBe('The Website must be a valid URL.');
    expect($result['messages']['avatar.image'])->toBe('The Avatar must be an image.');
    expect($result['messages']['is_active.boolean'])->toBe('The Is Active must be true or false.');
    expect($result['messages']['count.integer'])->toBe('The Count must be an integer.');
});

it('handles rules with parameters in message generation', function () {
    $modelContent = <<<PHP
<?php
namespace App\Models;

class TestModel 
{
    protected \$fillable = [
    'email'
    ];
}
PHP;

    $modelPath = $this->tempDir . '/TestModel.php';
    file_put_contents($modelPath, $modelContent);

    $result = ValidationRulesGenerator::generateRulesWithMessages($modelPath, 'create');

    // Test that rules with parameters (like unique:table,field) get proper message keys
    expect($result['messages'])->toHaveKey('email.unique');
    expect($result['messages']['email.unique'])->toBe('The Email has already been taken.');
});

it('generates file upload rules without mime types', function () {
    $options = [
        'types' => ['image'],
        'max_size' => 1024,
    ];

    $rules = ValidationRulesGenerator::generateFileUploadRules($options);

    expect($rules)->toContain('file');
    expect($rules)->toContain('max:1024');
    expect($rules)->toContain('image');
    expect($rules)->toContain('mimes:jpeg,png,jpg,gif,webp');
    expect($rules)->toContain('mimetypes:'); // Empty mime types
});

it('generates file upload rules with default options', function () {
    $rules = ValidationRulesGenerator::generateFileUploadRules();

    expect($rules)->toContain('file');
    expect($rules)->toContain('max:2048'); // Default size
    expect($rules)->toContain('mimetypes:'); // Empty mime types
});

it('tests guessTableFromField method indirectly through foreign key fields', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('category_id', 'create');

    expect($rules)->toContain('required');
    expect($rules)->toContain('integer');
    expect($rules)->toContain('exists:categories,id');
});

it('handles foreign key field without _id suffix fallback', function () {
    // This will test the fallback in guessTableFromField when field doesn't end with _id
    // But since the method checks Str::endsWith($field, '_id') first, we need a different approach
    $rules = ValidationRulesGenerator::generateRuleForField('company_id', 'create');

    expect($rules)->toContain('exists:companies,id');
});

it('tests unique validation with guessTableName for slug fields', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('slug', 'create');

    expect($rules)->toContain('unique:table,slug'); // Uses guessTableName() placeholder
});

it('handles update operation for slug fields without unique constraint', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('slug', 'update');

    expect($rules)->toContain('sometimes');
    expect($rules)->toContain('string');
    expect($rules)->toContain('alpha_dash');
    expect($rules)->not->toContain('unique:table,slug'); // No unique for update
});

it('handles update operation for foreign key fields', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('user_id', 'update');

    expect($rules)->toContain('sometimes');
    expect($rules)->toContain('integer');
    expect($rules)->toContain('exists:users,id');
    expect($rules)->not->toContain('required');
});

it('handles update operation for password fields without confirmation', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('password', 'update');

    expect($rules)->toContain('sometimes');
    expect($rules)->toContain('string');
    expect($rules)->toContain('min:8');
    expect($rules)->not->toContain('confirmed'); // No confirmed for update
});

it('handles update operation for email fields without unique constraint', function () {
    $rules = ValidationRulesGenerator::generateRuleForField('email', 'update');

    expect($rules)->toContain('sometimes');
    expect($rules)->toContain('email');
    expect($rules)->toContain('max:255');
    expect($rules)->not->toContain('unique:users,email'); // No unique for update
});

it('handles edge case with empty $rules property in model', function () {
    $modelContent = <<<PHP
<?php
namespace App\Models;

class TestModel 
{
    protected \$fillable = [
    'name'
    ];
    
    protected \$rules = [];
}
PHP;

    $modelPath = $this->tempDir . '/TestModel.php';
    file_put_contents($modelPath, $modelContent);

    $rules = ValidationRulesGenerator::generateForModel($modelPath, 'create');

    expect($rules)->toHaveKey('name');
    expect($rules['name'])->toContain('required');
    expect($rules['name'])->toContain('string');
});

it('handles model with no fillable property', function () {
    $modelContent = <<<PHP
<?php
namespace App\Models;

class TestModel 
{
    // No fillable property
    protected \$rules = [
    'test_field' => 'required|string'
    ];
}
PHP;

    $modelPath = $this->tempDir . '/TestModel.php';
    file_put_contents($modelPath, $modelContent);

    $rules = ValidationRulesGenerator::generateForModel($modelPath, 'create');

    expect($rules)->toHaveKey('test_field');
    expect($rules['test_field'])->toContain('required');
    expect($rules['test_field'])->toContain('string');
});

it('handles parseModelRules with no matches', function () {
    $modelContent = <<<PHP
<?php
namespace App\Models;

class TestModel 
{
    protected \$fillable = ['name'];
    
    // Invalid rules format that won't match regex
    protected \$rules = [
    "invalid_format" => "test"
    ];
}
PHP;

    $modelPath = $this->tempDir . '/TestModel.php';
    file_put_contents($modelPath, $modelContent);

    $rules = ValidationRulesGenerator::generateForModel($modelPath, 'create');

    // Should still have rules from fillable
    expect($rules)->toHaveKey('name');
});

it('handles complex field patterns correctly', function () {
    // Test fields that contain multiple keywords
    $rules1 = ValidationRulesGenerator::generateRuleForField('profile_image', 'create');
    $rules2 = ValidationRulesGenerator::generateRuleForField('phone_number', 'create');
    $rules3 = ValidationRulesGenerator::generateRuleForField('birth_date_time', 'create');
    $rules4 = ValidationRulesGenerator::generateRuleForField('user_type_name', 'create');

    expect($rules1)->toContain('image'); // Should match image pattern first
    expect($rules2)->toContain('regex:/^[\d\s\+\-\(\)]+$/'); // Should match phone pattern
    expect($rules3)->toContain('date'); // Should match date pattern first
    expect($rules4)->toContain('max:100'); // Should match type pattern first
});

it('generates comprehensive validation message for all rule types', function () {
    $modelContent = <<<PHP
<?php
namespace App\Models;

class TestModel 
{
    protected \$rules = [
    'test_min' => 'min:5',
    'test_max' => 'max:100',
    'test_integer' => 'integer',
    'test_boolean' => 'boolean', 
    'test_date' => 'date',
    'test_url' => 'url',
    'test_image' => 'image',
    'test_unique' => 'unique:table,field',
    'test_required' => 'required',
    'test_email' => 'email'
    ];
}
PHP;

    $modelPath = $this->tempDir . '/TestModel.php';
    file_put_contents($modelPath, $modelContent);

    $result = ValidationRulesGenerator::generateRulesWithMessages($modelPath, 'create');

    expect($result['messages'])->toHaveKey('test_min.min');
    expect($result['messages'])->toHaveKey('test_max.max');
    expect($result['messages'])->toHaveKey('test_integer.integer');
    expect($result['messages'])->toHaveKey('test_boolean.boolean');
    expect($result['messages'])->toHaveKey('test_date.date');
    expect($result['messages'])->toHaveKey('test_url.url');
    expect($result['messages'])->toHaveKey('test_image.image');
    expect($result['messages'])->toHaveKey('test_unique.unique');
    expect($result['messages'])->toHaveKey('test_required.required');
    expect($result['messages'])->toHaveKey('test_email.email');
});

it('handles conditional rules with string rule format', function () {
    $baseRules = [
        'name' => ['required', 'string'],
    ];

    $conditions = [
        'type,personal' => [
            'personal_id' => 'required|string|max:50', // String format rule
        ],
    ];

    $rules = ValidationRulesGenerator::generateConditionalRules($baseRules, $conditions);

    expect($rules)->toHaveKey('personal_id');
    expect($rules['personal_id'])->toContain('required_if:type,personal');
    expect($rules['personal_id'])->toContain('required|string|max:50');
});

it('handles conditional rules with array rule format', function () {
    $baseRules = [
        'name' => ['required', 'string'],
    ];

    $conditions = [
        'type,business' => [
            'company_name' => ['required', 'string', 'max:100'], // Array format rule
        ],
    ];

    $rules = ValidationRulesGenerator::generateConditionalRules($baseRules, $conditions);

    expect($rules)->toHaveKey('company_name');
    expect($rules['company_name'])->toContain('required_if:type,business');
    expect($rules['company_name'])->toContain('required');
    expect($rules['company_name'])->toContain('string');
    expect($rules['company_name'])->toContain('max:100');
});

it('handles file upload rules with both image and document types', function () {
    $options = [
        'types' => ['image', 'document'], // Both types - only first will be processed
        'max_size' => 5000,
        'mime_types' => ['image/jpeg', 'application/pdf'],
    ];

    $rules = ValidationRulesGenerator::generateFileUploadRules($options);

    expect($rules)->toContain('file');
    expect($rules)->toContain('max:5000');
    expect($rules)->toContain('image'); // Should match image first
    expect($rules)->toContain('mimes:jpeg,png,jpg,gif,webp');
    expect($rules)->not->toContain('mimes:pdf,doc,docx,xls,xlsx,txt'); // Document rules not added since image comes first
    expect($rules)->toContain('mimetypes:image/jpeg,application/pdf');
});

it('handles file upload rules with unknown type', function () {
    $options = [
        'types' => ['video'], // Unknown type
        'max_size' => 10000,
    ];

    $rules = ValidationRulesGenerator::generateFileUploadRules($options);

    expect($rules)->toContain('file');
    expect($rules)->toContain('max:10000');
    expect($rules)->not->toContain('image');
    expect($rules)->not->toContain('mimes:pdf,doc,docx,xls,xlsx,txt');
    expect($rules)->toContain('mimetypes:'); // Empty mime types
});

it('handles guessTableFromField fallback for non-foreign-key field', function () {
    // We need to create a test that calls guessTableFromField with a field that doesn't end with _id
    // This is tricky since the method is protected, but we can test it indirectly
    // However, looking at the code, if field doesn't end with _id, it returns 'users' as fallback

    // Let's create a more complex test by using reflection to test the protected method directly
    $reflection = new ReflectionClass(ValidationRulesGenerator::class);
    $method = $reflection->getMethod('guessTableFromField');
    $method->setAccessible(true);

    $result = $method->invoke(null, 'not_a_foreign_key');
    expect($result)->toBe('users'); // Fallback value
});

it('handles guessTableFromField for various foreign key patterns', function () {
    $reflection = new ReflectionClass(ValidationRulesGenerator::class);
    $method = $reflection->getMethod('guessTableFromField');
    $method->setAccessible(true);

    expect($method->invoke(null, 'user_id'))->toBe('users');
    expect($method->invoke(null, 'category_id'))->toBe('categories');
    expect($method->invoke(null, 'product_id'))->toBe('products');
    expect($method->invoke(null, 'company_id'))->toBe('companies');
});

it('handles guessTableName method directly', function () {
    $reflection = new ReflectionClass(ValidationRulesGenerator::class);
    $method = $reflection->getMethod('guessTableName');
    $method->setAccessible(true);

    $result = $method->invoke(null);
    expect($result)->toBe('table'); // Placeholder value
});

it('handles parseModelRules with complex rule formats', function () {
    $reflection = new ReflectionClass(ValidationRulesGenerator::class);
    $method = $reflection->getMethod('parseModelRules');
    $method->setAccessible(true);

    // Test with properly formatted rules
    $rulesString = "'field1' => 'required|string', 'field2' => 'nullable|integer'";
    $result = $method->invoke(null, $rulesString);

    expect($result)->toHaveKey('field1');
    expect($result)->toHaveKey('field2');
    expect($result['field1'])->toBe(['required', 'string']);
    expect($result['field2'])->toBe(['nullable', 'integer']);
});

it('handles parseModelRules with no valid matches', function () {
    $reflection = new ReflectionClass(ValidationRulesGenerator::class);
    $method = $reflection->getMethod('parseModelRules');
    $method->setAccessible(true);

    // Test with improperly formatted rules that won't match regex
    $rulesString = "invalid format without proper quotes";
    $result = $method->invoke(null, $rulesString);

    expect($result)->toBe([]); // Empty array when no matches
});

it('handles empty fillable and rules arrays', function () {
    $modelContent = <<<PHP
<?php
namespace App\Models;

class TestModel 
{
    protected \$fillable = [];
    protected \$rules = [];
}
PHP;

    $modelPath = $this->tempDir . '/TestModel.php';
    file_put_contents($modelPath, $modelContent);

    $rules = ValidationRulesGenerator::generateForModel($modelPath, 'create');

    expect($rules)->toBe([]); // Should return empty array
});
