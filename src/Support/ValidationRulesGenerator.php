<?php

declare(strict_types=1);

namespace Webmonks\LaravelApiModules\Support;

use Illuminate\Support\Str;

/**
 * Generates validation rules based on model analysis and common patterns
 */
class ValidationRulesGenerator
{
    /**
     * Generate validation rules for a model
     * @return array<int|string, array<int, string>>
     */
    public static function generateForModel(string $modelPath, string $operation = 'create'): array
    {
        if (!file_exists($modelPath)) {
            return [];
        }

        $content = file_get_contents($modelPath);
        if ($content === false) {
            return [];
        }

        $rules = [];

        // Extract fillable fields
        if (preg_match('/protected\s+\$fillable\s*=\s*\[(.*?)\]/s', $content, $matches)) {
            $fillable = $matches[1];
            preg_match_all("/'([^']+)'/", $fillable, $fields);

            foreach ($fields[1] as $field) {
                $rules[$field] = self::generateRuleForField($field, $operation);
            }
        }

        // Extract validation rules if defined in model
        if (preg_match('/protected\s+\$rules\s*=\s*\[(.*?)\]/s', $content, $matches)) {
            $modelRules = $matches[1];
            // Parse model rules and merge
            $parsedRules = self::parseModelRules($modelRules);
            $rules = array_merge($rules, $parsedRules);
        }

        return $rules;
    }

    /**
     * Generate validation rule for a specific field
     * @return array<int, string>
     */
    public static function generateRuleForField(string $field, string $operation = 'create'): array
    {
        $rules = [];

        // Required rules for create operations
        if ($operation === 'create') {
            $rules[] = 'required';
        } elseif ($operation === 'update') {
            $rules[] = 'sometimes';
        }

        // Field-specific rules based on naming patterns
        if ($field === 'email') {
            $rules[] = 'email';
            $rules[] = 'max:255';
            if ($operation === 'create') {
                $rules[] = 'unique:users,email';
            }
        } elseif (Str::endsWith($field, '_id')) {
            $rules[] = 'integer';
            $rules[] = 'exists:' . self::guessTableFromField($field) . ',id';
        } elseif (Str::contains($field, ['phone', 'mobile'])) {
            $rules[] = 'string';
            $rules[] = 'regex:/^[\d\s\+\-\(\)]+$/';
            $rules[] = 'max:20';
        } elseif (Str::contains($field, ['password'])) {
            $rules[] = 'string';
            $rules[] = 'min:8';
            if ($operation === 'create') {
                $rules[] = 'confirmed';
            }
        } elseif (Str::contains($field, ['url', 'website', 'link'])) {
            $rules[] = 'url';
            $rules[] = 'max:255';
        } elseif (Str::contains($field, ['date', 'born', 'birth'])) {
            $rules[] = 'date';
        } elseif (Str::contains($field, ['time'])) {
            $rules[] = 'date_format:H:i:s';
        } elseif (Str::startsWith($field, 'is_') || Str::startsWith($field, 'has_')) {
            $rules[] = 'boolean';
        } elseif (Str::contains($field, ['price', 'amount', 'cost', 'fee', 'total'])) {
            $rules[] = 'numeric';
            $rules[] = 'min:0';
        } elseif (Str::contains($field, ['quantity', 'count', 'number'])) {
            $rules[] = 'integer';
            $rules[] = 'min:0';
        } elseif (Str::contains($field, ['image', 'photo', 'avatar', 'picture'])) {
            $rules[] = 'image';
            $rules[] = 'mimes:jpeg,png,jpg,gif';
            $rules[] = 'max:2048'; // 2MB
        } elseif (Str::contains($field, ['file', 'document', 'attachment'])) {
            $rules[] = 'file';
            $rules[] = 'max:10240'; // 10MB
        } elseif (Str::contains($field, ['status'])) {
            $rules[] = 'in:active,inactive,pending,approved,rejected';
        } elseif (Str::contains($field, ['gender'])) {
            $rules[] = 'in:male,female,other';
        } elseif (Str::contains($field, ['type'])) {
            $rules[] = 'string';
            $rules[] = 'max:100';
        } elseif (Str::contains($field, ['name', 'title', 'subject'])) {
            $rules[] = 'string';
            $rules[] = 'max:255';
        } elseif (Str::contains($field, ['description', 'content', 'bio', 'about', 'message'])) {
            $rules[] = 'string';
            $rules[] = 'max:1000';
        } elseif (Str::contains($field, ['slug'])) {
            $rules[] = 'string';
            $rules[] = 'alpha_dash';
            $rules[] = 'max:255';
            if ($operation === 'create') {
                $rules[] = 'unique:' . self::guessTableName() . ',' . $field;
            }
        } else {
            // Default string rules
            $rules[] = 'string';
            $rules[] = 'max:255';
        }

        return $rules;
    }

    /**
     * Generate validation rules for API parameters
     * @return array<int|string, array<int, string>>
     */
    public static function generateApiParameterRules(): array
    {
        return [
            'page' => ['integer', 'min:1'],
            'per_page' => ['integer', 'min:1', 'max:100'],
            'search' => ['string', 'max:255'],
            'sort' => ['string', 'in:asc,desc'],
            'sort_by' => ['string', 'max:50'],
            'filter' => ['array'],
            'include' => ['string'],
            'fields' => ['string'],
        ];
    }

    /**
     * Generate rules with localized messages
     * @return array<string, mixed>
     */
    public static function generateRulesWithMessages(string $modelPath, string $operation = 'create'): array
    {
        $rules = self::generateForModel($modelPath, $operation);
        $messages = [];

        foreach ($rules as $field => $fieldRules) {
            $fieldName = Str::title(str_replace('_', ' ', (string)$field));

            foreach ($fieldRules as $rule) {
                $ruleKey = $rule;

                // Handle rules that contain parameters (like unique:table,field)
                if (str_contains($ruleKey, ':')) {
                    $ruleKey = explode(':', $ruleKey)[0];
                }

                switch ($ruleKey) {
                    case 'required':
                        $messages["{$field}.required"] = "The {$fieldName} field is required.";

                        break;
                    case 'email':
                        $messages["{$field}.email"] = "The {$fieldName} must be a valid email address.";

                        break;
                    case 'unique':
                        $messages["{$field}.unique"] = "The {$fieldName} has already been taken.";

                        break;
                    case 'min':
                        $messages["{$field}.min"] = "The {$fieldName} must be at least :min characters.";

                        break;
                    case 'max':
                        $messages["{$field}.max"] = "The {$fieldName} may not be greater than :max characters.";

                        break;
                    case 'integer':
                        $messages["{$field}.integer"] = "The {$fieldName} must be an integer.";

                        break;
                    case 'boolean':
                        $messages["{$field}.boolean"] = "The {$fieldName} must be true or false.";

                        break;
                    case 'date':
                        $messages["{$field}.date"] = "The {$fieldName} must be a valid date.";

                        break;
                    case 'url':
                        $messages["{$field}.url"] = "The {$fieldName} must be a valid URL.";

                        break;
                    case 'image':
                        $messages["{$field}.image"] = "The {$fieldName} must be an image.";

                        break;
                }
            }
        }

        return [
            'rules' => $rules,
            'messages' => $messages,
        ];
    }

    /**
     * Guess table name from foreign key field
     */
    protected static function guessTableFromField(string $field): string
    {
        if (Str::endsWith($field, '_id')) {
            $tableName = Str::beforeLast($field, '_id');

            return Str::plural($tableName);
        }

        return 'users'; // Default fallback
    }

    /**
     * Guess table name (placeholder - would need model context)
     */
    protected static function guessTableName(): string
    {
        return 'table'; // Placeholder
    }

    /**
     * Parse model rules from string format
     * @return array<int|string, array<int, string>>
     */
    protected static function parseModelRules(string $rulesString): array
    {
        $rules = [];

        // Basic parsing of array format rules
        preg_match_all("/'([^']+)'\s*=>\s*'([^']+)'/", $rulesString, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $field = $match[1];
            $ruleString = $match[2];
            $rules[$field] = explode('|', $ruleString);
        }

        return $rules;
    }

    /**
     * Generate smart validation based on database schema
     * @return array<int|string, array<int, string>>
     */
    public static function generateFromDatabaseSchema(string $table): array
    {
        // This would connect to database and analyze schema
        // Implementation would use Laravel's Schema facade
        return [];
    }

    /**
     * Generate conditional validation rules
     * @param array<string, array<int, string>> $baseRules
     * @param array<string, array<string, mixed>> $conditions
     * @return array<int|string, array<int, string>>
     */
    public static function generateConditionalRules(array $baseRules, array $conditions): array
    {
        $rules = $baseRules;

        foreach ($conditions as $condition => $conditionalRules) {
            foreach ($conditionalRules as $field => $rule) {
                if (!isset($rules[$field])) {
                    $rules[$field] = [];
                }

                // Add conditional rule
                $rules[$field][] = "required_if:{$condition}";
                $rules[$field] = array_merge($rules[$field], (array) $rule);
            }
        }

        return $rules;
    }

    /**
     * Generate rules for file uploads with security checks
     * @param array<string, mixed> $options
     * @return array<int, string>
     */
    public static function generateFileUploadRules(array $options = []): array
    {
        $rules = ['file', 'max:' . ($options['max_size'] ?? 2048)];

        if (isset($options['types'])) {
            if (in_array('image', $options['types'])) {
                $rules[] = 'image';
                $rules[] = 'mimes:jpeg,png,jpg,gif,webp';
            } elseif (in_array('document', $options['types'])) {
                $rules[] = 'mimes:pdf,doc,docx,xls,xlsx,txt';
            }
        }

        // Security rules
        $rules[] = 'mimetypes:' . implode(',', $options['mime_types'] ?? []);

        return $rules;
    }
}
