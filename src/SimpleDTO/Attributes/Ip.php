<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Concerns\RequiresSymfonyValidator;
use event4u\DataHelpers\SimpleDTO\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation attribute: Value must be a valid IP address.
 *
 * Supports IPv4 and IPv6 addresses.
 *
 * Example:
 * ```php
 * class ServerDTO extends SimpleDTO
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
class Ip implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    /** @param string|null $version IP version: 'ipv4', 'ipv6', or null for both */
    public function __construct(
        public readonly ?string $version = null,
    ) {}

    /** Convert to Laravel validation rule. */
    public function rule(): string
    {
        if (null === $this->version) {
            return 'ip';
        }

        return $this->version;
    }

    /**
     * Get validation error message.
     *
     * @param string $attribute
     * @return string
     */

    public function constraint(): Constraint|array
    {
        $this->ensureSymfonyValidatorAvailable();

        if (null === $this->version) {
            // Use ALL to accept both IPv4 and IPv6
            return new Assert\Ip(version: Assert\Ip::ALL);
        }
        return new Assert\Ip(
            version: 'ipv4' === $this->version ? Assert\Ip::V4 : Assert\Ip::V6
        );
    }
    public function message(): ?string
    {
        if (null === $this->version) {
            return "The attribute must be a valid IP address.";
        }

        $version = strtoupper(str_replace('ipv', 'IPv', $this->version));
        return sprintf('The attribute must be a valid %s address.', $version);
    }
}

