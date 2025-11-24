<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Exceptions;

use RuntimeException;

/**
 * Exception thrown when a DTO uses an attribute from the wrong namespace.
 *
 * This exception is thrown when:
 * - A SimpleDto uses a LiteDto-specific attribute
 * - A LiteDto uses a SimpleDto-specific attribute
 */
class InvalidAttributeUsageException extends RuntimeException
{
    /**
     * Create exception for SimpleDto using LiteDto attribute.
     *
     * @param class-string $dtoClass
     * @param class-string $attributeClass
     */
    public static function simpleDtoUsesLiteDtoAttribute(
        string $dtoClass,
        string $attributeClass,
        string $propertyName
    ): self
    {
        $simpleDtoAttribute = str_replace('LiteDto\\Attributes\\', 'SimpleDto\\Attributes\\', $attributeClass);

        return new self(sprintf(
            'SimpleDto "%s" uses LiteDto attribute "%s" on property "%s". ' .
            'Use the SimpleDto version instead: "%s"',
            $dtoClass,
            $attributeClass,
            $propertyName,
            $simpleDtoAttribute
        ));
    }

    /**
     * Create exception for LiteDto using SimpleDto attribute.
     *
     * @param class-string $dtoClass
     * @param class-string $attributeClass
     */
    public static function liteDtoUsesSimpleDtoAttribute(
        string $dtoClass,
        string $attributeClass,
        string $propertyName
    ): self
    {
        // Check if there's a LiteDto equivalent
        $liteDtoAttribute = str_replace('SimpleDto\\Attributes\\', 'LiteDto\\Attributes\\', $attributeClass);
        $liteDtoAttributeExists = class_exists($liteDtoAttribute);

        if ($liteDtoAttributeExists) {
            return new self(sprintf(
                'LiteDto "%s" uses SimpleDto attribute "%s" on property "%s". ' .
                'Use the LiteDto version instead: "%s"',
                $dtoClass,
                $attributeClass,
                $propertyName,
                $liteDtoAttribute
            ));
        }

        return new self(sprintf(
            'LiteDto "%s" uses SimpleDto-only attribute "%s" on property "%s". ' .
            'This attribute is not supported in LiteDto. ' .
            'LiteDto only supports: Map, MapFrom, MapTo, Hidden, ConvertEmptyToNull, ConverterMode, DateTimeFormat, EnumSerialize, CastWith, UltraFast, HasDto, HasEntity, HasModel, HasObject',
            $dtoClass,
            $attributeClass,
            $propertyName
        ));
    }
}
