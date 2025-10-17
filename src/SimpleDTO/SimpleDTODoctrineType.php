<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;

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

    /** {@inheritdoc} */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getJsonTypeDeclarationSQL($column);
    }

    /**
     * {@inheritdoc}
     *
     * @return TDto|null
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?SimpleDTO
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

    /** {@inheritdoc} */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
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
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}

