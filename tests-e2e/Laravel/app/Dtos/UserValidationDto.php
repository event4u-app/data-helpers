<?php

declare(strict_types=1);

namespace E2E\Laravel\Dtos;

use E2E\Laravel\Models\User;
use event4u\DataHelpers\SimpleDto\Attributes\Validation\Email;
use event4u\DataHelpers\SimpleDto\Attributes\Validation\ExistsCallback;
use event4u\DataHelpers\SimpleDto\Attributes\Validation\Required;
use event4u\DataHelpers\SimpleDto\Attributes\Validation\UniqueCallback;
use event4u\DataHelpers\SimpleDto;

/**
 * DTO for testing callback-based validation attributes with Laravel.
 */
class UserValidationDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Email]
        #[UniqueCallback([self::class, 'validateUniqueEmail'])]
        public readonly string $email,

        #[Required]
        public readonly string $name,

        #[ExistsCallback([self::class, 'validateManagerExists'])]
        public readonly ?int $managerId = null,

        public readonly ?int $id = null,
    ) {}

    public static function validateUniqueEmail(mixed $value, array $data): bool
    {
        return !User::where('email', $value)
            ->when(isset($data['id']), fn($q) => $q->where('id', '!=', $data['id']))
            ->exists();
    }

    public static function validateManagerExists(mixed $value): bool
    {
        return User::where('id', $value)->exists();
    }
}

