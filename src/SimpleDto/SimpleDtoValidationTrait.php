<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;
use event4u\DataHelpers\SimpleDto\Support\ConstructorMetadata;
use event4u\DataHelpers\SimpleDto\Support\SimpleEngine;
use event4u\DataHelpers\SimpleDto\ValidationErrorCollection;
use event4u\DataHelpers\Validation\ValidationResult;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;
use Stringable;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Throwable;

/**
 * Trait providing validation functionality for SimpleDtos.
 *
 * This trait handles all validation logic including:
 * - Auto-inferring validation rules from PHP types
 * - Processing validation attributes
 * - Validating data before Dto creation
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
 *   class UserDto extends SimpleDto {
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
 *   $user = UserDto::validateAndCreate($request->all());
 */
trait SimpleDtoValidationTrait
{
    /** @var array<string, array<string, array<int, string>>> Cache for validation rules */
    private static array $rulesCache = [];

    /** @var ValidationResult|null Last validation result */
    private ?ValidationResult $lastValidationResult = null;

    /**
     * Get custom validation rules for the Dto.
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
     * Validates the data using SimpleEngine and stores the ValidationResult in the DTO.
     * Throws ValidationException if validation fails.
     *
     * @param array<string, mixed>|string|object $data Data to validate
     * @throws ValidationException
     */
    public static function validateAndCreate(mixed $data): static
    {
        // Use SimpleEngine to validate (with caching, performance optimizations, etc.)
        $result = SimpleEngine::validate(static::class, $data);

        // Throw exception if validation failed
        if (!$result->isValid()) {
            throw new ValidationException(
                'Validation failed: ' . implode(', ', $result->allErrors())
            );
        }

        // Create DTO from validated data
        $dto = static::from($result->validated());

        // Store validation result in DTO
        $dto->lastValidationResult = $result;

        return $dto;
    }

    /**
     * Validate this DTO instance.
     *
     * Validates the current DTO and returns the validated data as an array.
     * Stores the ValidationResult for later retrieval via getLastValidationResult().
     *
     * @return array<string, mixed> Validated data
     * @throws ValidationException If validation fails
     */
    public function validate(): array
    {
        // Convert DTO to array for validation
        $data = $this->toArray();

        // Use SimpleEngine to validate (with caching, performance optimizations, etc.)
        $result = SimpleEngine::validate(static::class, $data);

        // Store validation result
        $this->lastValidationResult = $result;

        // Throw exception if validation failed
        if (!$result->isValid()) {
            throw new ValidationException(
                'Validation failed: ' . implode(', ', $result->allErrors())
            );
        }

        // Return validated data
        return $result->validated();
    }

    /**
     * Get the last validation result.
     *
     * Returns the ValidationResult from the last validation (either from validateAndCreate() or validate()).
     * Returns null if the DTO has not been validated yet.
     */
    public function getLastValidationResult(): ?ValidationResult
    {
        return $this->lastValidationResult;
    }

    /** Check if this DTO has been validated. */
    public function isValidated(): bool
    {
        return null !== $this->lastValidationResult;
    }

    /**
     * Check if this DTO is valid (based on last validation).
     *
     * Returns null if not validated yet.
     */
    public function isValid(): ?bool
    {
        return $this->lastValidationResult?->isValid();
    }

    /**
     * Get validation errors from the last validation.
     *
     * Returns a collection of validation errors. If the DTO has not been validated yet,
     * returns an empty collection.
     */
    public function getValidationErrors(): ValidationErrorCollection
    {
        $errors = $this->lastValidationResult?->errors() ?? [];
        return new ValidationErrorCollection($errors);
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
            $propertyRules = array_merge(
                $inferredRules[$property] ?? [],
                $attributeRules[$property] ?? []
            );

            // Add custom rules
            $customRule = $customRules[$property] ?? null;
            if (null !== $customRule) {
                if (is_array($customRule)) {
                    $propertyRules = array_merge($propertyRules, $customRule);
                } else {
                    $propertyRules[] = $customRule;
                }
            }

            // Remove duplicates (only works with string rules)
            $stringRules = array_filter($propertyRules, 'is_string');
            $arrayRules = array_filter($propertyRules, 'is_array');

            $rules[$property] = array_merge(
                array_values(array_unique($stringRules)),
                array_values($arrayRules)
            );
        }

        self::$rulesCache[$cacheKey] = $rules;

        return $rules;
    }

    /**
     * Get auto-inferred validation rules from property types.
     *
     * @return array<string, array<int, string>>
     */
    protected static function getInferredRules(): array
    {
        $rules = [];

        try {
            $reflection = new ReflectionClass(static::class);
            $constructor = $reflection->getConstructor();

            if (null === $constructor) {
                return [];
            }

            foreach ($constructor->getParameters() as $reflectionParameter) {
                $type = $reflectionParameter->getType();

                if (!$type instanceof ReflectionNamedType) {
                    continue;
                }

                $propertyRules = [];
                $typeName = $type->getName();
                $isNullable = $type->allowsNull();

                // Add required rule if not nullable and no default value
                if (!$isNullable && !$reflectionParameter->isDefaultValueAvailable()) {
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

                // Check if type is a nested Dto
                if (class_exists($typeName)) {
                    try {
                        $typeReflection = new ReflectionClass($typeName);
                        // Check if it uses SimpleDtoTrait (which means it's a Dto)
                        if ($typeReflection->hasMethod('getAllRules')) {
                            $propertyRules[] = 'array';
                            // Get nested Dto rules and add them with dot notation
                            $nestedRules = $typeName::getAllRules();
                            foreach ($nestedRules as $nestedField => $nestedFieldRules) {
                                $rules[$reflectionParameter->getName() . '.' . $nestedField] = $nestedFieldRules;
                            }
                        }
                    } catch (Throwable) {
                        // Not a Dto, skip
                    }
                }

                if ([] !== $propertyRules) {
                    $rules[$reflectionParameter->getName()] = $propertyRules;
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
    protected static function getAttributeRules(): array
    {
        $rules = [];

        try {
            $reflection = new ReflectionClass(static::class);
            $constructor = $reflection->getConstructor();

            if (null === $constructor) {
                return [];
            }

            foreach ($constructor->getParameters() as $reflectionParameter) {
                $propertyRules = [];

                // Get all attributes from parameter
                $attributes = $reflectionParameter->getAttributes();

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
                    $rules[$reflectionParameter->getName()] = $propertyRules;
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
    protected static function getCustomRules(): array
    {
        try {
            $reflection = new ReflectionClass(static::class);
            $method = $reflection->getMethod('rules');

            $instance = $reflection->newInstanceWithoutConstructor();

            $result = $method->invoke($instance);

            if (!is_array($result)) {
                return [];
            }

            /** @var array<string, string|array<int, string>> */
            return $result;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Get all custom error messages.
     *
     * @return array<string, string>
     */
    public static function getAllMessages(): array
    {
        $messages = [];

        // Get messages from validation attributes
        try {
            $reflection = new ReflectionClass(static::class);
            $constructor = $reflection->getConstructor();

            if (null !== $constructor) {
                foreach ($constructor->getParameters() as $reflectionParameter) {
                    foreach ($reflectionParameter->getAttributes() as $attribute) {
                        $instance = $attribute->newInstance();

                        if ($instance instanceof ValidationRule && null !== $instance->message()) {
                            $rule = $instance->rule();
                            $ruleName = is_array($rule) ? $rule[0] : $rule;
                            $ruleName = explode(':', $ruleName)[0];
                            $messages[$reflectionParameter->getName() . '.' . $ruleName] = $instance->message();
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

            if (is_array($customMessages)) {
                /** @var array<string, string> $customMessages */
                $messages = array_merge($messages, $customMessages);
            }
        } catch (Throwable) {
            // Ignore reflection errors
        }

        /** @var array<string, string> */
        return $messages;
    }

    /**
     * Get all custom attribute names.
     *
     * @return array<string, string>
     */
    public static function getAllAttributes(): array
    {
        try {
            $reflection = new ReflectionClass(static::class);
            $method = $reflection->getMethod('attributes');

            $instance = $reflection->newInstanceWithoutConstructor();

            $result = $method->invoke($instance);

            if (!is_array($result)) {
                return [];
            }

            /** @var array<string, string> */
            return $result;
        } catch (Throwable) {
            return [];
        }
    }

    /** Get Laravel validator instance. */
    protected static function getValidator(): ValidationFactory
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

    /** Check if Dto has any Symfony constraints. */
    protected static function hasSymfonyConstraints(): bool
    {
        try {
            // Use centralized metadata cache
            $metadata = ConstructorMetadata::get(static::class);

            foreach ($metadata['parameters'] as $param) {
                foreach ($param['attributes'] as $attribute) {
                    if ($attribute instanceof SymfonyConstraint) {
                        return true;
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
        $collectionConstraint = new Assert\Collection(
            fields: $constraints,
            allowExtraFields: true,
            allowMissingFields: false,
        );

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

            foreach ($constructor->getParameters() as $reflectionParameter) {
                $propertyConstraints = [];

                foreach ($reflectionParameter->getAttributes() as $attribute) {
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
                    $constraints[$reflectionParameter->getName()] = $propertyConstraints;
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
            if (null === $propertyPath) {
                $propertyPath = $violation->getPropertyPath();
            }

            if (!isset($errors[$propertyPath])) {
                $errors[$propertyPath] = [];
            }

            $message = $violation->getMessage();
            if ($message instanceof Stringable) {
                $message = (string)$message;
            }

            $errors[$propertyPath][] = $message;
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
