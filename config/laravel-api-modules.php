<?php

return [
    'modules_dir' => 'app/Modules',
    'core_interfaces_dir' => 'app/Core/Interfaces',
    'namespace' => 'App\\Modules',
    'interface_namespace' => 'App\\Core\\Interfaces',
    'stubs_path' => base_path('stubs/laravel-api-modules'),
    'publish_stubs' => true,
    'auto_discover_routes' => true,
    'generate_migration' => true,  // Generate migration files for models
    'migration_path' => 'database/migrations',
    'generate_tests' => true, // Generate tests for models and services
    'tests_dir' => 'tests/Feature', // Unit tests will go to tests/Unit

    'enable_base_model' => true,
    'enable_base_service' => true,
    'model_extends_base' => 'BaseModel',
    'model_use_base' => 'App\\Models\\BaseModel',

    'base_model_traits' => [
        'ApiResponser' => true,         // must include
        'ActivityLogHelper' => true,   // optional
        'PdfGeneratorTrait' => true,   // optional
        'SmsSender' => true,           // optional
        'UserUpdater' => true,         // optional
    ],
    'user_id_source' => [
        'type' => 'request', // options: 'request', 'auth', 'custom'
        'key' => 'user_details.id', // dot notation for request()->input(), default 'user_details.id'
        // 'resolver' => [App\Helpers\UserHelper::class, 'currentUserId'], // for 'custom'
    ],
];
