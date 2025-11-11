<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\OptionalSymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * Validation attribute: Value must be a valid IP address.
 *
 * Supports IPv4 and IPv6 addresses.
 *
 * Example:
 * ```php
 * class ServerDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Ip]
 *         public readonly string $ipAddress,
 *
 *         #[Ip(version: 'ipv4')]
 *         public readonly string $ipv4Address,
 *
 *         #[Ip(version: 'ipv6')]
 *         public readonly string $ipv6Address,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Ip implements ValidationAttribute, ValidationRule, SymfonyConstraint
{
    use OptionalSymfonyConstraint;

    /** @param string|null $version IP version: 'ipv4', 'ipv6' or null for both */
    public function __construct(
        public readonly ?string $version = null,
    ) {}

    /** Validate the value. */
    public function validate(mixed $value, string $propertyName): bool
    {
        // Skip validation for null values (use Required attribute to enforce non-null)
        if (null === $value) {
            return true;
        }

        // Must be a string
        if (!is_string($value)) {
            return false;
        }

        // Validate based on version
        if (null === $this->version) {
            // Accept both IPv4 and IPv6
            return false !== filter_var($value, FILTER_VALIDATE_IP);
        }

        if ('ipv4' === $this->version) {
            return false !== filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        }

        if ('ipv6' === $this->version) {
            return false !== filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
        }

        return false;
    }

    /** Get validation error message. */
    public function getErrorMessage(string $propertyName): string
    {
        if (null === $this->version) {
            return sprintf('The %s must be a valid IP address.', $propertyName);
        }

        $version = strtoupper(str_replace('ipv', 'IPv', $this->version));
        return sprintf('The %s must be a valid %s address.', $propertyName, $version);
    }

    /** Convert to Laravel validation rule. */
    public function rule(): string
    {
        if (null === $this->version) {
            return 'ip';
        }

        return $this->version;
    }

    public function constraint(): object
    {
        // Determine version constant value
        if (null === $this->version) {
            // Use ALL to accept both IPv4 and IPv6
            $version = 'all';
        } elseif ('ipv4' === $this->version) {
            $version = '4';
        } else {
            $version = '6';
        }

        return $this->createConstraint(
            "\\Symfony\\Component\\Validator\\Constraints\\Ip",
            ['version' => $version]
        );
    }

    /** Get validation error message. */
    public function message(): ?string
    {
        if (null === $this->version) {
            return 'The attribute must be a valid IP address.';
        }

        $version = strtoupper(str_replace('ipv', 'IPv', $this->version));
        return sprintf('The attribute must be a valid %s address.', $version);
    }
}
