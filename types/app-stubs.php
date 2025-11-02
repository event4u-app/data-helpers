<?php

declare(strict_types=1);

namespace App\Entity;

class User
{
    public function getId(): ?int
    {
        return null;
    }

    public function setEmail(string $email): self
    {
        return $this;
    }

    public function setName(string $name): self
    {
        return $this;
    }
}

class Product
{
    public function getId(): ?int
    {
        return null;
    }

    public function setSku(string $sku): self
    {
        return $this;
    }

    public function setName(string $name): self
    {
        return $this;
    }

    public function setActive(bool $active): self
    {
        return $this;
    }
}

namespace App\Dto;

use Doctrine\ORM\EntityManagerInterface;
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\Validation\ValidationResult;

class UserValidationDto extends LiteDto
{
    public static function setEntityManager(EntityManagerInterface $em): void
    {
    }

    /** @param array<string, mixed> $data */
    public static function validate(array $data): ValidationResult
    {
        return ValidationResult::success($data);
    }

    /** @param array<string, mixed> $data */
    public static function validateAndCreate(array $data): self
    {
        return new self();
    }
}

class ProductValidationDto extends LiteDto
{
    public static function setEntityManager(EntityManagerInterface $em): void
    {
    }

    /** @param array<string, mixed> $data */
    public static function validate(array $data): ValidationResult
    {
        return ValidationResult::success($data);
    }

    /** @param array<string, mixed> $data */
    public static function validateAndCreate(array $data): self
    {
        return new self();
    }
}

class FileUploadDto extends LiteDto
{
    /** @param array<string, mixed> $data */
    public static function validate(array $data): ValidationResult
    {
        return ValidationResult::success($data);
    }

    /** @param array<string, mixed> $data */
    public static function validateAndCreate(array $data): self
    {
        return new self();
    }
}

namespace E2ESymfony;

use event4u\DataHelpers\LiteDto\LiteDto;

class SymfonyRoleDto extends LiteDto
{
}

namespace event4u\DataHelpers\LiteDto\Attributes\Laravel;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class WhenAuth
{
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class WhenGuest
{
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class WhenRole
{
    public function __construct(public readonly string $role)
    {
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class WhenCan
{
    public function __construct(public readonly string $ability)
    {
    }
}

namespace event4u\DataHelpers\LiteDto\Attributes\Symfony;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class WhenGranted
{
    public function __construct(public readonly string $attribute)
    {
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class WhenRole
{
    /** @param string|array<string> $role */
    public function __construct(public readonly string|array $role)
    {
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class WhenSymfonyRole
{
    public function __construct(public readonly string $role)
    {
    }
}
