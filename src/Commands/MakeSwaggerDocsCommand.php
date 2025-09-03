<?php

declare(strict_types=1);

namespace Webmonks\LaravelApiModules\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Artisan command for generating OpenAPI/Swagger documentation for API modules.
 */
class MakeSwaggerDocsCommand extends Command
{
    protected $signature = 'make:api-docs {module?} {--all} {--format=yaml}';
    protected $description = 'Generate OpenAPI/Swagger documentation for API modules';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $config = config('laravel-api-modules');
        if (!is_array($config)) {
            $config = [];
        }

        $format = $this->option('format');
        $formatString = is_string($format) ? $format : 'yaml';

        $moduleName = $this->argument('module');
        $moduleNameString = is_string($moduleName) ? $moduleName : null;

        $generateAll = $this->option('all');

        if (!$moduleNameString && !$generateAll) {
            $this->error('Please specify a module name or use --all flag');

            return 1;
        }

        $modulesDir = base_path($config['modules_dir'] ?? 'app/Modules');

        if (!is_dir($modulesDir)) {
            $this->error('Modules directory not found: ' . $modulesDir);

            return 1;
        }

        $docsDir = base_path('docs/api');
        if (!is_dir($docsDir)) {
            $this->files->makeDirectory($docsDir, 0755, true);
        }

        if ($generateAll) {
            return $this->generateAllModuleDocs($modulesDir, $docsDir, $formatString);
        }

        if ($moduleNameString === null) {
            $this->error('Module name is required');

            return 1;
        }

        return $this->generateModuleDocs($moduleNameString, $modulesDir, $docsDir, $formatString);
    }

    protected function generateAllModuleDocs(string $modulesDir, string $docsDir, string $format): int
    {
        $modules = glob($modulesDir . '/*', GLOB_ONLYDIR);
        if ($modules === false) {
            $this->error('Failed to scan modules directory');

            return 1;
        }

        $success = 0;

        foreach ($modules as $moduleDir) {
            $moduleName = basename($moduleDir);
            if ($this->generateModuleDocs($moduleName, $modulesDir, $docsDir, $format) === 0) {
                $success++;
            }
        }

        $this->info("Generated documentation for {$success} modules");

        // Generate master OpenAPI spec
        $this->generateMasterSpec($docsDir, $format);

        return 0;
    }

    protected function generateModuleDocs(string $moduleName, string $modulesDir, string $docsDir, string $format): int
    {
        $moduleDir = $modulesDir . '/' . $moduleName;

        if (!is_dir($moduleDir)) {
            $this->error("Module not found: {$moduleName}");

            return 1;
        }

        $controllerFile = $moduleDir . '/Controllers/' . $moduleName . 'Controller.php';
        $modelFile = $moduleDir . '/Models/' . $moduleName . '.php';

        if (!file_exists($controllerFile)) {
            $this->warn("Controller not found for module: {$moduleName}");

            return 1;
        }

        $spec = $this->generateOpenAPISpec($moduleName, $controllerFile, $modelFile);

        $filename = $format === 'json' ? "{$moduleName}.json" : "{$moduleName}.yaml";
        $filepath = $docsDir . '/' . $filename;

        $content = $format === 'json' ?
            json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) :
            $this->arrayToYaml($spec);

        if ($content === false) {
            $this->error("Failed to generate content for {$moduleName}");

            return 1;
        }

        $this->files->put($filepath, $content);
        $this->info("Generated documentation: {$filepath}");

        return 0;
    }

    /**
     * @return array<string, mixed>
     */
    protected function generateOpenAPISpec(string $moduleName, string $controllerFile, string $modelFile): array
    {
        $moduleSnake = Str::snake($moduleName);
        $modulePlural = Str::pluralStudly($moduleName);
        $moduleKebab = Str::kebab($moduleName);

        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => "{$moduleName} API",
                'description' => "API documentation for {$moduleName} module",
                'version' => '1.0.0',
            ],
            'servers' => [
                ['url' => url('/api'), 'description' => 'API Server'],
            ],
            'paths' => [],
            'components' => [
                'schemas' => [],
                'responses' => $this->getStandardResponses(),
                'parameters' => $this->getStandardParameters(),
            ],
        ];

        // Analyze controller to determine available endpoints
        $controllerContent = file_get_contents($controllerFile);
        if ($controllerContent === false) {
            $controllerContent = '';
        }

        $hasResource = str_contains($controllerContent, 'public function index') ||
                      str_contains($controllerContent, 'public function store') ||
                      str_contains($controllerContent, 'public function show') ||
                      str_contains($controllerContent, 'public function update') ||
                      str_contains($controllerContent, 'public function destroy');

        if ($hasResource) {
            $spec['paths'] = $this->generateResourcePaths($moduleKebab, $moduleName, $moduleSnake, $modulePlural);
        } else {
            $spec['paths'] = $this->generateListPath($moduleKebab, $moduleName, $moduleSnake);
        }

        // Generate model schema
        $spec['components']['schemas'][$moduleName] = $this->generateModelSchema($moduleName, $modelFile);
        $spec['components']['schemas']["{$moduleName}List"] = [
            'type' => 'object',
            'properties' => [
                'data' => [
                    'type' => 'array',
                    'items' => ['$ref' => "#/components/schemas/{$moduleName}"],
                ],
                'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
            ],
        ];

        $spec['components']['schemas']['PaginationMeta'] = [
            'type' => 'object',
            'properties' => [
                'current_page' => ['type' => 'integer'],
                'last_page' => ['type' => 'integer'],
                'per_page' => ['type' => 'integer'],
                'total' => ['type' => 'integer'],
            ],
        ];

        return $spec;
    }

    /**
     * @return array<string, mixed>
     */
    protected function generateResourcePaths(string $moduleKebab, string $moduleName, string $moduleSnake, string $modulePlural): array
    {
        return [
            "/api/{$moduleKebab}" => [
                'get' => [
                    'summary' => "List {$modulePlural}",
                    'description' => "Get a paginated list of {$modulePlural}",
                    'tags' => [$moduleName],
                    'parameters' => [
                        ['$ref' => '#/components/parameters/Page'],
                        ['$ref' => '#/components/parameters/PerPage'],
                        ['$ref' => '#/components/parameters/Search'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Success',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => "#/components/schemas/{$moduleName}List"],
                                ],
                            ],
                        ],
                        '422' => ['$ref' => '#/components/responses/ValidationError'],
                    ],
                ],
                'post' => [
                    'summary' => "Create {$moduleName}",
                    'description' => "Create a new {$moduleName}",
                    'tags' => [$moduleName],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => "#/components/schemas/{$moduleName}"],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Created',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => "#/components/schemas/{$moduleName}"],
                                ],
                            ],
                        ],
                        '422' => ['$ref' => '#/components/responses/ValidationError'],
                    ],
                ],
            ],
            "/api/{$moduleKebab}/{id}" => [
                'get' => [
                    'summary' => "Get {$moduleName}",
                    'description' => "Get a specific {$moduleName} by ID",
                    'tags' => [$moduleName],
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Success',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => "#/components/schemas/{$moduleName}"],
                                ],
                            ],
                        ],
                        '404' => ['$ref' => '#/components/responses/NotFound'],
                    ],
                ],
                'put' => [
                    'summary' => "Update {$moduleName}",
                    'description' => "Update a specific {$moduleName}",
                    'tags' => [$moduleName],
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => "#/components/schemas/{$moduleName}"],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Updated',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => "#/components/schemas/{$moduleName}"],
                                ],
                            ],
                        ],
                        '404' => ['$ref' => '#/components/responses/NotFound'],
                        '422' => ['$ref' => '#/components/responses/ValidationError'],
                    ],
                ],
                'delete' => [
                    'summary' => "Delete {$moduleName}",
                    'description' => "Delete a specific {$moduleName}",
                    'tags' => [$moduleName],
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'responses' => [
                        '204' => ['description' => 'Deleted'],
                        '404' => ['$ref' => '#/components/responses/NotFound'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function generateListPath(string $moduleKebab, string $moduleName, string $moduleSnake): array
    {
        return [
            "/api/{$moduleKebab}" => [
                'get' => [
                    'summary' => "List {$moduleName}",
                    'description' => "Get a list of {$moduleName}",
                    'tags' => [$moduleName],
                    'parameters' => [
                        ['$ref' => '#/components/parameters/Page'],
                        ['$ref' => '#/components/parameters/PerPage'],
                        ['$ref' => '#/components/parameters/Search'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Success',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => "#/components/schemas/{$moduleName}List"],
                                ],
                            ],
                        ],
                        '422' => ['$ref' => '#/components/responses/ValidationError'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function generateModelSchema(string $moduleName, string $modelFile): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Unique identifier',
                    'readOnly' => true,
                ],
                'created_at' => [
                    'type' => 'string',
                    'format' => 'date-time',
                    'description' => 'Creation timestamp',
                    'readOnly' => true,
                ],
                'updated_at' => [
                    'type' => 'string',
                    'format' => 'date-time',
                    'description' => 'Last update timestamp',
                    'readOnly' => true,
                ],
            ],
        ];

        // Try to analyze model file for additional properties
        if (file_exists($modelFile)) {
            $modelContent = file_get_contents($modelFile);
            if ($modelContent === false) {
                return $schema;
            }

            // Look for fillable array with multiple patterns
            $patterns = [
                '/protected\s+\$fillable\s*=\s*\[(.*?)\]/s',
                '/\$fillable\s*=\s*\[(.*?)\]/s',
                '/fillable.*?\[(.*?)\]/s',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $modelContent, $matches)) {
                    $fillable = $matches[1];
                    // Try multiple quote patterns
                    $quotePatterns = [
                        "/'([^']+)'/",
                        '/"([^"]+)"/',
                    ];

                    foreach ($quotePatterns as $qPattern) {
                        if (preg_match_all($qPattern, $fillable, $fields)) {
                            foreach ($fields[1] as $field) {
                                if (!isset($schema['properties'][$field])) {
                                    $schema['properties'][$field] = [
                                        'type' => $this->guessFieldType($field),
                                        'description' => ucfirst(str_replace('_', ' ', $field)),
                                    ];
                                }
                            }

                            break 2; // Break both loops if we found fields
                        }
                    }
                }
            }
        }

        return $schema;
    }

    protected function guessFieldType(string $fieldName): string
    {
        // Common patterns for field types
        if (Str::endsWith($fieldName, '_id') || $fieldName === 'id') {
            return 'integer';
        }

        if (Str::contains($fieldName, ['email'])) {
            return 'string'; // Could add format: email
        }

        if (Str::contains($fieldName, ['phone', 'mobile'])) {
            return 'string';
        }

        if (Str::contains($fieldName, ['date', 'time'])) {
            return 'string'; // Could add format: date-time
        }

        if (Str::contains($fieldName, ['price', 'amount', 'cost'])) {
            return 'number';
        }

        if (Str::contains($fieldName, ['is_', 'has_', 'active', 'enabled'])) {
            return 'boolean';
        }

        return 'string';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getStandardResponses(): array
    {
        return [
            'ValidationError' => [
                'description' => 'Validation error',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'Message' => ['type' => 'string'],
                                'Status' => ['type' => 'string', 'example' => 'Fail'],
                                'Error' => ['type' => 'object'],
                                'HttpStatus' => ['type' => 'integer', 'example' => 422],
                                'Data' => ['type' => 'array'],
                            ],
                        ],
                    ],
                ],
            ],
            'NotFound' => [
                'description' => 'Resource not found',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'Message' => ['type' => 'string', 'example' => 'Resource not found'],
                                'Status' => ['type' => 'string', 'example' => 'Fail'],
                                'HttpStatus' => ['type' => 'integer', 'example' => 404],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getStandardParameters(): array
    {
        return [
            'Page' => [
                'name' => 'page',
                'in' => 'query',
                'description' => 'Page number',
                'required' => false,
                'schema' => ['type' => 'integer', 'minimum' => 1, 'default' => 1],
            ],
            'PerPage' => [
                'name' => 'per_page',
                'in' => 'query',
                'description' => 'Items per page',
                'required' => false,
                'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 15],
            ],
            'Search' => [
                'name' => 'search',
                'in' => 'query',
                'description' => 'Search term',
                'required' => false,
                'schema' => ['type' => 'string'],
            ],
        ];
    }

    protected function generateMasterSpec(string $docsDir, string $format): void
    {
        $masterSpec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Laravel API Modules Documentation',
                'description' => 'Complete API documentation for all modules',
                'version' => '1.0.0',
            ],
            'servers' => [
                ['url' => url('/api'), 'description' => 'API Server'],
            ],
            'paths' => [],
            'components' => [
                'schemas' => [],
                'responses' => $this->getStandardResponses(),
                'parameters' => $this->getStandardParameters(),
            ],
        ];

        // Merge all module specs
        $extension = $format === 'json' ? '*.json' : '*.yaml';
        $moduleSpecs = glob($docsDir . '/' . $extension);
        if ($moduleSpecs === false) {
            $moduleSpecs = [];
        }

        foreach ($moduleSpecs as $specFile) {
            if (basename($specFile) === "master.{$format}") {
                continue; // Skip master file
            }

            $content = file_get_contents($specFile);
            if ($content === false) {
                continue;
            }

            $spec = $format === 'json' ? json_decode($content, true) : $this->yamlToArray($content);

            if ($spec && isset($spec['paths'])) {
                $masterSpec['paths'] = array_merge($masterSpec['paths'], $spec['paths']);
            }

            if ($spec && isset($spec['components']['schemas'])) {
                $masterSpec['components']['schemas'] = array_merge(
                    $masterSpec['components']['schemas'],
                    $spec['components']['schemas']
                );
            }
        }

        $filename = "master.{$format}";
        $filepath = $docsDir . '/' . $filename;

        $content = $format === 'json' ?
            json_encode($masterSpec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) :
            $this->arrayToYaml($masterSpec);

        if ($content === false) {
            $this->error("Failed to generate master specification");

            return;
        }

        $this->files->put($filepath, $content);
        $this->info("Generated master specification: {$filepath}");
    }

    /**
     * @param array<string, mixed> $array
     */
    protected function arrayToYaml(array $array, int $indent = 0): string
    {
        $yaml = '';
        $indentStr = str_repeat('  ', $indent);

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (array_keys($value) === range(0, count($value) - 1)) {
                    // Indexed array
                    $yaml .= $indentStr . $key . ":\n";
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $yaml .= $indentStr . "  -\n";
                            $yaml .= $this->arrayToYaml($item, $indent + 2);
                        } else {
                            $yaml .= $indentStr . "  - " . $this->formatYamlValue($item) . "\n";
                        }
                    }
                } else {
                    // Associative array
                    $yaml .= $indentStr . $key . ":\n";
                    $yaml .= $this->arrayToYaml($value, $indent + 1);
                }
            } else {
                $yaml .= $indentStr . $key . ': ' . $this->formatYamlValue($value) . "\n";
            }
        }

        return $yaml;
    }

    /**
     * @param mixed $value
     */
    protected function formatYamlValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_string($value) && (str_contains($value, ':') || str_contains($value, '#') || str_contains($value, '"'))) {
            return '"' . addslashes($value) . '"';
        }

        return (string) $value;
    }

    /**
     * @return array<string, mixed>
     */
    protected function yamlToArray(string $yaml): array
    {
        // Simple YAML parser for basic cases
        // In production, you'd want to use a proper YAML library
        return [];
    }
}
