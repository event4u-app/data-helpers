<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use event4u\DataHelpers\Validation\Validator;
use Symfony\Component\Validator\Constraint;
use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\SimpleDTO\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
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

        // Try Symfony validator first (if available and has constraints)
        // But also validate with framework-independent validator for fields without Symfony constraints
        if (static::hasSymfonyValidator() && static::hasSymfonyConstraints()) {
            try {
                static::validateWithSymfony($data);
            } catch (ValidationException $e) {
                throw $e;
            }

            // Also validate with framework-independent validator for fields that don't have Symfony constraints
            // This handles cases like Same/Different attributes that need access to all fields
            $validator = new Validator($data, $rules, $messages, $attributes);
            return $validator->validate();
        }

        // Try Laravel validator
        if (static::hasLaravelValidator()) {
            try {
                $validator = static::getValidator();
                $validated = $validator->make($data, $rules, $messages, $attributes);
                return $validated->validate();
            } catch (LaravelValidationException $e) {
                // Convert Laravel ValidationException to our own
                throw ValidationException::withMessages($e->errors());
            }
        }

        // Fallback to framework-independent validator
        $validator = new Validator($data, $rules, $messages, $attributes);
        return $validator->validate();
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

            $instance = $reflection->newInstanceWithoutConstructor();

            return $method->invoke($instance);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Get Laravel validator instance.
     */
    private static function getValidator(): ValidationFactory
    {
        // Try to get validator from Laravel container
        if (function_exists('app') && app()->bound('validator')) {
            return app('validator');
        }

        // Fallback: Use framework-independent validator
        throw new RuntimeException(
            'Laravel Validator not available. Use validateData() or validateOrFail() for framework-independent validation.'
        );
    }

    /** Check if Laravel validator is available. */
    protected static function hasLaravelValidator(): bool
    {
        return function_exists('app') && app()->bound('validator');
    }

    /** Check if Symfony validator is available. */
    protected static function hasSymfonyValidator(): bool
    {
        return class_exists(Validation::class);
    }

    /** Check if DTO has any Symfony constraints. */
    protected static function hasSymfonyConstraints(): bool
    {
        try {
            $reflection = new ReflectionClass(static::class);
            $constructor = $reflection->getConstructor();

            if (null === $constructor) {
                return false;
            }

            foreach ($constructor->getParameters() as $parameter) {
                foreach ($parameter->getAttributes() as $attribute) {
                    try {
                        $instance = $attribute->newInstance();
                        if ($instance instanceof SymfonyConstraint) {
                            return true;
                        }
                    } catch (Throwable) {
                        continue;
                    }
                }
            }
        } catch (Throwable) {
            // Ignore reflection errors
        }

        return false;
    }

    /**
     * Validate data using Symfony Validator.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws ValidationException
     */
    protected static function validateWithSymfony(array $data): array
    {
        $validator = Validation::createValidator();
        $constraints = static::getSymfonyConstraints();

        // Create a Collection constraint with all field constraints
        $collectionConstraint = new Assert\Collection([
            'fields' => $constraints,
            'allowExtraFields' => true,
            'allowMissingFields' => false,
        ]);

        $violations = $validator->validate($data, $collectionConstraint);

        if (count($violations) > 0) {
            $errors = static::formatSymfonyViolations($violations);
            throw ValidationException::withMessages($errors);
        }

        return $data;
    }

    /**
     * Get Symfony constraints from validation attributes.
     *
     * @return array<string, Constraint|Constraint[]>
     */
    protected static function getSymfonyConstraints(): array
    {
        $constraints = [];

        try {
            $reflection = new ReflectionClass(static::class);
            $constructor = $reflection->getConstructor();

            if (null === $constructor) {
                return [];
            }

            foreach ($constructor->getParameters() as $parameter) {
                $propertyConstraints = [];

                foreach ($parameter->getAttributes() as $attribute) {
                    try {
                        $instance = $attribute->newInstance();

                        if ($instance instanceof SymfonyConstraint) {
                            $constraint = $instance->constraint();
                            $propertyConstraints = array_merge(
                                $propertyConstraints,
                                is_array($constraint) ? $constraint : [$constraint]
                            );
                        }
                    } catch (Throwable) {
                        continue;
                    }
                }

                if ([] !== $propertyConstraints) {
                    $constraints[$parameter->getName()] = $propertyConstraints;
                }
            }
        } catch (Throwable) {
            // Ignore reflection errors
        }

        return $constraints;
    }

    /**
     * Format Symfony constraint violations into Laravel-style error array.
     *
     * @return array<string, array<int, string>>
     */
    protected static function formatSymfonyViolations(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            // Remove [field] brackets from property path
            $propertyPath = preg_replace('/\[(\w+)\]/', '$1', $propertyPath);

            if (!isset($errors[$propertyPath])) {
                $errors[$propertyPath] = [];
            }

            $errors[$propertyPath][] = $violation->getMessage();
        }

        return $errors;
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

