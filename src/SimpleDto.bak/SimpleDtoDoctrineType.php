<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\SimpleDto;
use InvalidArgumentException;
use RuntimeException;

// Create stub classes if Doctrine is not installed
if (!class_exists('Doctrine\DBAL\Types\Type')) {
    abstract class Type
    {
        /** @param array<string, mixed> $column */
        abstract public function getSQLDeclaration(array $column, mixed $platform): string;

        abstract public function convertToPHPValue(mixed $value, mixed $platform): mixed;

        abstract public function convertToDatabaseValue(mixed $value, mixed $platform): mixed;

        abstract public function getName(): string;
    }
}

if (!class_exists('Doctrine\DBAL\Platforms\AbstractPlatform')) {
    abstract class AbstractPlatform
    {
        /** @param array<string, mixed> $column */
        public function getJsonTypeDeclarationSQL(array $column): string
        {
            return 'JSON';
        }
    }
}

// Use the real classes if available, otherwise use stubs
if (class_exists('Doctrine\DBAL\Types\Type')) {
    class_alias('Doctrine\DBAL\Types\Type', 'event4u\DataHelpers\SimpleDto\Type');
    class_alias('Doctrine\DBAL\Platforms\AbstractPlatform', 'event4u\DataHelpers\SimpleDto\AbstractPlatform');
}

/**
 * Doctrine DBAL Type for SimpleDtos.
 *
 * This allows storing SimpleDtos as JSON in Doctrine entities.
 *
 * @template TDto of SimpleDto
 */
class SimpleDtoDoctrineType extends Type
{
    /** @var class-string<TDto> */
    private string $dtoClass;

    /**
     * Create a new Doctrine Type for a specific Dto class.
     *
     * @template TDtoClass of SimpleDto
     * @param class-string<TDtoClass> $dtoClass
     * @return self<TDtoClass>
     */
    public static function createForDto(string $dtoClass): self
    {
        $type = new self();
        $type->dtoClass = $dtoClass;

        // @phpstan-ignore return.type (Generic type limitation with static factory methods)
        return $type;
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $column
     */
    public function getSQLDeclaration(array $column, mixed $platform): string
    {
        if (!is_object($platform) || !method_exists($platform, 'getJsonTypeDeclarationSQL')) {
            throw new InvalidArgumentException('Platform must have getJsonTypeDeclarationSQL method');
        }

        return $platform->getJsonTypeDeclarationSQL($column);
    }

    /**
     * {@inheritdoc}
     *
     * @return TDto|null
     */
    public function convertToPHPValue(mixed $value, mixed $platform): ?SimpleDto
    {
        if (null === $value || '' === $value) {
            return null;
        }

        // If already a Dto instance, check if it's the correct type
        if ($value instanceof SimpleDto) {
            /** @var class-string<TDto> $dtoClass */
            $dtoClass = $this->dtoClass;

            if ($value instanceof $dtoClass) {
                return $value;
            }

            // If it's a different Dto type, convert via array
            $value = $value->toArray();
        }

        // If string, decode JSON
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (!is_array($decoded)) {
                return null;
            }
            $value = $decoded;
        }

        // If array, create Dto
        if (is_array($value)) {
            /** @var class-string<TDto> $dtoClass */
            $dtoClass = $this->dtoClass;

            /** @var array<string, mixed> $value */
            return $dtoClass::fromArray($value);
        }

        return null;
    }

    /** {@inheritdoc} */
    public function convertToDatabaseValue(mixed $value, mixed $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        // If Dto, convert to JSON
        if ($value instanceof SimpleDto) {
            $json = json_encode($value->toArray());
            if (false === $json) {
                throw new RuntimeException('Failed to encode Dto to JSON: ' . json_last_error_msg());
            }
            return $json;
        }

        // If array, encode directly
        if (is_array($value)) {
            $json = json_encode($value);
            if (false === $json) {
                throw new RuntimeException('Failed to encode array to JSON: ' . json_last_error_msg());
            }
            return $json;
        }

        // If already string, return as-is
        if (is_string($value)) {
            return $value;
        }

        return null;
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'simple_dto';
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $platform
     */
    public function requiresSQLCommentHint($platform): bool
    {
        return true;
    }
}
