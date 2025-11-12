<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Frameworks\Symfony\Serializer;

use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\LiteDto\Support\LiteEngine;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Support\SimpleEngine;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Symfony Serializer Normalizer for SimpleDto and LiteDto.
 *
 * This normalizer ensures that DTOs are properly serialized using their
 * toJsonArray() method, which respects #[DateTimeFormat] attributes and
 * formats DateTime objects as strings.
 *
 * Usage in Symfony:
 * Register in config/services.yaml:
 * ```yaml
 * services:
 *     event4u\DataHelpers\Frameworks\Symfony\Serializer\DtoNormalizer:
 *         tags:
 *             - { name: serializer.normalizer, priority: 64 }
 * ```
 *
 * Or register in DtoBundle.php:
 * ```php
 * $container->services()
 *     ->set('event4u.data_helpers.dto_normalizer', DtoNormalizer::class)
 *     ->tag('serializer.normalizer', ['priority' => 64]);
 * ```
 *
 * Example:
 * ```php
 * use Symfony\Component\Serializer\SerializerInterface;
 *
 * class EventDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $title,
 *
 *         #[DateTimeFormat('Y-m-d H:i:s')]
 *         public readonly DateTime $startDate,
 *     ) {}
 * }
 *
 * $dto = new EventDto('Conference', new DateTime('2024-01-15 10:30:00'));
 *
 * // Symfony Serializer will use DtoNormalizer
 * $json = $serializer->serialize($dto, 'json');
 * // {"title":"Conference","startDate":"2024-01-15 10:30:00"}
 * ```
 */
class DtoNormalizer implements NormalizerInterface
{
    /**
     * Normalize a DTO to an array.
     *
     * Uses toJsonArray() which formats DateTime objects according to
     * #[DateTimeFormat] attributes.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        if ($object instanceof SimpleDto) {
            return SimpleEngine::toJsonArray($object, $context);
        }

        if ($object instanceof LiteDto) {
            return LiteEngine::toJsonArray($object, $context);
        }

        return [];
    }

    /**
     * Check if this normalizer supports the given data.
     *
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof SimpleDto || $data instanceof LiteDto;
    }

    /**
     * Get supported types for Symfony Serializer 6.3+.
     *
     * @return array<string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            SimpleDto::class => true,
            LiteDto::class => true,
        ];
    }
}
