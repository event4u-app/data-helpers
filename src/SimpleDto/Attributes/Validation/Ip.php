<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;

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
class Ip implements ValidationAttribute
{
    /** @param string|null $version IP version: 'ipv4', 'ipv6', or null for both */
    public function __construct(
        public readonly ?string $version = null,
    ) {}

    public function validate(mixed $value, string $propertyName): bool
    {
        // Skip validation if value is null
        if (null === $value) {
            return true;
        }

        // Value must be a string
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

    public function getErrorMessage(string $propertyName): string
    {
        if (null === $this->version) {
            return sprintf('The %s field must be a valid IP address.', $propertyName);
        }

        $version = strtoupper(str_replace('ipv', 'IPv', $this->version));
        return sprintf('The %s field must be a valid %s address.', $propertyName, $version);
    }
}
