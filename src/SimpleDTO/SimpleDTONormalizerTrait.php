<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use event4u\DataHelpers\SimpleDTO\Normalizers\NormalizerInterface;

/**
 * Trait for normalizer support.
 *
 * This trait provides methods to normalize data before DTO creation.
 * Normalizers ensure data is in the correct format and type.
 *
 * Example:
 *   $user = UserDTO::fromArrayWithNormalizer($data, new TypeNormalizer([
 *       'age' => 'int',
 *       'active' => 'bool',
 *   ]));
 */
trait SimpleDTONormalizerTrait
{
    /**
     * Create a DTO from array with normalization.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArrayWithNormalizer(
        array $data,
        NormalizerInterface $normalizer
    ): static {
        $data = $normalizer->normalize($data);

        return static::fromArray($data);
    }

    /**
     * Create a DTO from array with multiple normalizers.
     *
     * @param array<string, mixed> $data
     * @param array<NormalizerInterface> $normalizers
     */
    public static function fromArrayWithNormalizers(
        array $data,
        array $normalizers
    ): static {
        foreach ($normalizers as $normalizer) {
            $data = $normalizer->normalize($data);
        }

        return static::fromArray($data);
    }

    /**
     * Normalize the DTO data.
     *
     * This creates a new DTO instance with normalized data.
     */
    public function normalizeWith(NormalizerInterface $normalizer): static
    {
        $data = $this->toArray();
        $data = $normalizer->normalize($data);

        return static::fromArray($data);
    }

    /**
     * Define normalizers to apply before DTO creation.
     *
     * Override this method to define default normalizers for your DTO.
     *
     * @return array<NormalizerInterface>
     */
    protected function inputNormalizers(): array
    {
        return [];
    }

    /**
     * Define normalizers to apply after DTO serialization.
     *
     * Override this method to define default normalizers for your DTO.
     *
     * @return array<NormalizerInterface>
     */
    protected function outputNormalizers(): array
    {
        return [];
    }
}
