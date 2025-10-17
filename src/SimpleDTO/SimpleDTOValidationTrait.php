<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use RuntimeException;
use Throwable;

/**
 * Trait providing validation functionality for SimpleDTOs.
 *
 * This trait handles all validation logic including:
 * - Auto-inferring validation rules from PHP types
 * - Processing validation attributes
 * - Validating data before DTO creation
 * - Integration with Laravel Validator
 *
 * Responsibilities:
 * - Define validation rules via rules() method
 * - Auto-infer rules from property types
 * - Extract rules from validation attributes
 * - Validate data arrays
 * - Provide custom error messages
 *
 * Example:
 *   class UserDTO extends SimpleDTO {
 *       public function __construct(
 *           #[Required]
 *           #[Email]
 *           public readonly string $email,
 *
 *           #[Required]
 *           #[Min(3)]
 *           #[Max(50)]
 *           public readonly string $name,
 *
 *           #[Between(18, 120)]
 *           public readonly int $age,
 *       ) {}
 *   }
 *
 *   // Validate and create
 *   $user = UserDTO::validateAndCreate($request->all());
 */
trait SimpleDTOValidationTrait
{
    /** @var array<string, array<string>> Cache for validation rules */
    private static array $rulesCache = [];

    /**
     * Get custom validation rules for the DTO.
     *
     * Override this method to define additional custom validation rules.
     * These rules will be merged with auto-inferred rules and attribute rules.
     *
     * @return array<string, string|array<int, string>>
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * Override this method to provide custom error messages.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * Get custom attribute names for validation errors.
     *
     * Override this method to provide human-readable attribute names.
     *
     * @return array<string, string>
     */
    protected function attributes(): array
    {
        return [];
    }

    /**
     * Validate data and create DTO instance.
     *
     * Throws ValidationException if validation fails.
     *
     * @param array<string, mixed> $data
     * @return static
     * @throws ValidationException
     */
    public static function validateAndCreate(array $data): static
    {
        $validated = static::validate($data);

        return static::fromArray($validated);
    }

    /**
     * Validate data without creating DTO instance.
     *
     * Returns validated data if successful.
     * Throws ValidationException if validation fails.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public static function validate(array $data): array
    {
        $rules = static::getAllRules();
        $messages = static::getAllMessages();
        $attributes = static::getAllAttributes();

        // Get Laravel validator
        $validator = static::getValidator();

        // Validate
        $validated = $validator->make($data, $rules, $messages, $attributes);

        return $validated->validate();
    }

    /**
     * Get all validation rules (auto-inferred + attributes + custom).
     *
     * @return array<string, array<int, string>>
     */
    public static function getAllRules(): array
    {
        $cacheKey = static::class;

        if (isset(self::$rulesCache[$cacheKey])) {
            return self::$rulesCache[$cacheKey];
        }

        $rules = [];

        // Get auto-inferred rules from types
        $inferredRules = static::getInferredRules();

        // Get rules from validation attributes
        $attributeRules = static::getAttributeRules();

        // Get custom rules
        $customRules = static::getCustomRules();

        // Merge all rules
        foreach (array_keys($inferredRules + $attributeRules + $customRules) as $property) {
            $rules[$property] = array_unique(array_merge(
                $inferredRules[$property] ?? [],
                $attributeRules[$property] ?? [],
                is_array($customRules[$property] ?? null)
                    ? $customRules[$property]
                    : (isset($customRules[$property]) ? [$customRules[$property]] : [])
            ));
        }

        self::$rulesCache[$cacheKey] = $rules;

        return $rules;
    }

    /**
     * Get auto-inferred validation rules from property types.
     *
     * @return array<string, array<int, string>>
     */
    private static function getInferredRules(): array
    {
        $rules = [];

        try {
            $reflection = new ReflectionClass(static::class);
            $constructor = $reflection->getConstructor();

            if (null === $constructor) {
                return [];
            }

            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();

                if (!$type instanceof ReflectionNamedType) {
                    continue;
                }

                $propertyRules = [];
                $typeName = $type->getName();
                $isNullable = $type->allowsNull();

                // Add required rule if not nullable and no default value
                if (!$isNullable && !$parameter->isDefaultValueAvailable()) {
                    $propertyRules[] = 'required';
                }

                // Add type-specific rules
                $propertyRules = array_merge($propertyRules, match ($typeName) {
                    'string' => ['string'],
                    'int' => ['integer'],
                    'float' => ['numeric'],
                    'bool' => ['boolean'],
                    'array' => ['array'],
                    default => [],
                });

                // Check if type is a nested DTO
                if (class_exists($typeName)) {
                    try {
                        $typeReflection = new ReflectionClass($typeName);
                        // Check if it uses SimpleDTOTrait (which means it's a DTO)
                        if ($typeReflection->hasMethod('getAllRules')) {
                            $propertyRules[] = 'array';
                            // Get nested DTO rules and add them with dot notation
                            $nestedRules = $typeName::getAllRules();
                            foreach ($nestedRules as $nestedField => $nestedFieldRules) {
                                $rules[$parameter->getName() . '.' . $nestedField] = $nestedFieldRules;
                            }
                        }
                    } catch (Throwable) {
                        // Not a DTO, skip
                    }
                }

                if ([] !== $propertyRules) {
                    $rules[$parameter->getName()] = $propertyRules;
                }
            }
        } catch (Throwable) {
            // Ignore reflection errors
        }

        return $rules;
    }

    /**
     * Get validation rules from validation attributes.
     *
     * @return array<string, array<int, string>>
     */
    private static function getAttributeRules(): array
    {
        $rules = [];

        try {
            $reflection = new ReflectionClass(static::class);
            $constructor = $reflection->getConstructor();

            if (null === $constructor) {
                return [];
            }

            foreach ($constructor->getParameters() as $parameter) {
                $propertyRules = [];

                // Get all attributes from parameter
                $attributes = $parameter->getAttributes();

                foreach ($attributes as $attribute) {
                    try {
                        $instance = $attribute->newInstance();

                        if ($instance instanceof ValidationRule) {
                            $rule = $instance->rule();
                            $propertyRules = array_merge(
                                $propertyRules,
                                is_array($rule) ? $rule : [$rule]
                            );
                        }
                    } catch (Throwable) {
                        // Skip attributes that can't be instantiated
                        continue;
                    }
                }

                if ([] !== $propertyRules) {
                    $rules[$parameter->getName()] = $propertyRules;
                }
            }
        } catch (Throwable) {
            // Ignore reflection errors
        }

        return $rules;
    }

    /**
     * Get custom validation rules.
     *
     * @return array<string, string|array<int, string>>
     */
    private static function getCustomRules(): array
    {
        try {
            $reflection = new ReflectionClass(static::class);
            $method = $reflection->getMethod('rules');
            $method->setAccessible(true);

            $instance = $reflection->newInstanceWithoutConstructor();

            return $method->invoke($instance);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Get all custom error messages.
     *
     * @return array<string, string>
     */
    private static function getAllMessages(): array
    {
        $messages = [];

        // Get messages from validation attributes
        try {
            $reflection = new ReflectionClass(static::class);
            $constructor = $reflection->getConstructor();

            if (null !== $constructor) {
                foreach ($constructor->getParameters() as $parameter) {
                    foreach ($parameter->getAttributes() as $attribute) {
                        $instance = $attribute->newInstance();

                        if ($instance instanceof ValidationRule && null !== $instance->message()) {
                            $rule = $instance->rule();
                            $ruleName = is_array($rule) ? $rule[0] : $rule;
                            $ruleName = explode(':', $ruleName)[0];
                            $messages[$parameter->getName() . '.' . $ruleName] = $instance->message();
                        }
                    }
                }
            }
        } catch (Throwable) {
            // Ignore reflection errors
        }

        // Merge with custom messages
        try {
            $reflection = new ReflectionClass(static::class);
            $method = $reflection->getMethod('messages');
            $method->setAccessible(true);

            $instance = $reflection->newInstanceWithoutConstructor();
            $customMessages = $method->invoke($instance);

            $messages = array_merge($messages, $customMessages);
        } catch (Throwable) {
            // Ignore reflection errors
        }

        return $messages;
    }

    /**
     * Get all custom attribute names.
     *
     * @return array<string, string>
     */
    private static function getAllAttributes(): array
    {
        try {
            $reflection = new ReflectionClass(static::class);
            $method = $reflection->getMethod('attributes');
            $method->setAccessible(true);

            $instance = $reflection->newInstanceWithoutConstructor();

            return $method->invoke($instance);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Get Laravel validator instance.
     *
     * @return ValidationFactory
     */
    private static function getValidator(): ValidationFactory
    {
        // Try to get validator from Laravel container
        if (function_exists('app') && app()->bound('validator')) {
            return app('validator');
        }

        // Fallback: create validator manually
        throw new RuntimeException(
            'Laravel Validator not available. Make sure illuminate/validation is installed and configured.'
        );
    }

    /**
     * Clear the rules cache.
     *
     * Useful for testing or when you need to reset cached rules.
     */
    public static function clearRulesCache(): void
    {
        self::$rulesCache = [];
    }
}

