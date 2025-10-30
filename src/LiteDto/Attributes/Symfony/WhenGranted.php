<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Symfony;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ConditionalProperty;

/**
 * Attribute to conditionally include a property based on Symfony security grants.
 *
 * Checks authorization in two ways:
 * 1. Context: Pass user/security and check if granted
 * 2. Symfony Security: Use AuthorizationCheckerInterface->isGranted() (if available)
 *
 * Supports multiple syntaxes:
 * - WhenGranted('EDIT') - Check if user is granted EDIT attribute
 * - WhenGranted('EDIT', 'post') - Check if user is granted EDIT on subject from context
 *
 * @example With context
 * ```php
 * class PostDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $title,
 *
 *         #[WhenGranted('EDIT')]
 *         public readonly string $editLink = '/edit',
 *     ) {}
 * }
 *
 * $user = (object)['grants' => ['EDIT', 'VIEW']];
 * $dto = new PostDto('My Post');
 * $dto->withContext(['user' => $user])->toArray();
 * ```
 *
 * @example With Symfony Security
 * ```php
 * // Automatically uses Security->isGranted()
 * $dto->toArray();
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenGranted implements ConditionalProperty
{
    /**
     * @param string $attribute Attribute to check (e.g., 'EDIT', 'VIEW', 'DELETE')
     * @param string|null $subject Subject context key or class name
     */
    public function __construct(
        public readonly string $attribute,
        public readonly ?string $subject = null,
    ) {}

    /**
     * Check if the property should be included based on security grants.
     *
     * @param mixed $value Property value
     * @param object $dto Dto instance
     * @param array<string, mixed> $context Context data
     */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        // Check context first
        if (array_key_exists('user', $context)) {
            $user = $context['user'];

            // No user = no permission
            if (null === $user) {
                return false;
            }

            // Get subject if specified
            $subjectValue = $this->getSubject($context);

            // Check if user has 'isGranted' method (Symfony User with Security trait)
            if (is_object($user) && method_exists($user, 'isGranted')) {
                return null !== $subjectValue
                    ? $user->isGranted($this->attribute, $subjectValue)
                    : $user->isGranted($this->attribute);
            }

            // Check if user has 'grants' array
            if (is_object($user) && isset($user->grants)) {
                return in_array($this->attribute, $user->grants, true);
            }

            // Check if user has 'permissions' array
            if (is_object($user) && isset($user->permissions)) {
                return in_array($this->attribute, $user->permissions, true);
            }

            // Default to false if user doesn't have grant info
            return false;
        }

        // Check if 'security' is in context (Symfony AuthorizationCheckerInterface)
        if (array_key_exists('security', $context)) {
            $security = $context['security'];

            if (null !== $security && is_object($security) && method_exists($security, 'isGranted')) {
                $subjectValue = $this->getSubject($context);

                return null !== $subjectValue
                    ? $security->isGranted($this->attribute, $subjectValue)
                    : $security->isGranted($this->attribute);
            }
        }

        // Default to false if no context
        return false;
    }

    /**
     * Get subject from context.
     *
     * @param array<string, mixed> $context Context data
     */
    private function getSubject(array $context): mixed
    {
        if (null === $this->subject) {
            return null;
        }

        // If subject is a class name, return it
        if (class_exists($this->subject)) {
            return $this->subject;
        }

        // Otherwise, try to get it from context
        return $context[$this->subject] ?? null;
    }
}
