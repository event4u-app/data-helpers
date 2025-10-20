<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use event4u\DataHelpers\SimpleDTO;

// Create stub classes if Doctrine is not installed
if (!class_exists('Doctrine\DBAL\Types\Type')) {
    abstract class Type
    {
        /**
         * @param array<string, mixed> $column
         * @param mixed $platform
         */
        abstract public function getSQLDeclaration(array $column, mixed $platform): string;

        /**
         * @param mixed $value
         * @param mixed $platform
         * @return mixed
         */
        abstract public function convertToPHPValue(mixed $value, mixed $platform): mixed;

        /**
         * @param mixed $value
         * @param mixed $platform
         * @return mixed
         */
        abstract public function convertToDatabaseValue(mixed $value, mixed $platform): mixed;

        abstract public function getName(): string;
    }
}

if (!class_exists('Doctrine\DBAL\Platforms\AbstractPlatform')) {
    abstract class AbstractPlatform
    {
        /**
         * @param array<string, mixed> $column
         */
        public function getJsonTypeDeclarationSQL(array $column): string
        {
            return 'JSON';
        }
    }
}

// Use the real classes if available, otherwise use stubs
if (class_exists('Doctrine\DBAL\Types\Type')) {
    class_alias('Doctrine\DBAL\Types\Type', 'event4u\DataHelpers\SimpleDTO\Type');
    class_alias('Doctrine\DBAL\Platforms\AbstractPlatform', 'event4u\DataHelpers\SimpleDTO\AbstractPlatform');
}

/**
 * Doctrine DBAL Type for SimpleDTOs.
 *
 * This allows storing SimpleDTOs as JSON in Doctrine entities.
 *
 * @template TDto of SimpleDTO
 */
class SimpleDTODoctrineType extends Type
{
    /** @var class-string<TDto> */
    private string $dtoClass;

    /**
     * Create a new Doctrine Type for a specific DTO class.
     *
     * @param class-string<TDto> $dtoClass
     */
    public static function createForDTO(string $dtoClass): self
    {
        $type = new self();
        $type->dtoClass = $dtoClass;

        return $type;
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $column
     * @param mixed $platform
     */
    public function getSQLDeclaration(array $column, mixed $platform): string
    {
        return $platform->getJsonTypeDeclarationSQL($column);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param mixed $platform
     * @return TDto|null
     */
    public function convertToPHPValue(mixed $value, mixed $platform): ?SimpleDTO
    {
        if (null === $value || '' === $value) {
            return null;
        }

        // If already a DTO instance, return it
        if ($value instanceof SimpleDTO) {
            return $value;
        }

        // If string, decode JSON
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (!is_array($decoded)) {
                return null;
            }
            $value = $decoded;
        }

        // If array, create DTO
        if (is_array($value)) {
            /** @var class-string<TDto> $dtoClass */
            $dtoClass = $this->dtoClass;

            return $dtoClass::fromArray($value);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param mixed $platform
     */
    public function convertToDatabaseValue(mixed $value, mixed $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        // If DTO, convert to JSON
        if ($value instanceof SimpleDTO) {
            return json_encode($value->toArray());
        }

        // If array, encode directly
        if (is_array($value)) {
            return json_encode($value);
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

    /** {@inheritdoc} */
    public function requiresSQLCommentHint($platform): bool
    {
        return true;
    }
}

