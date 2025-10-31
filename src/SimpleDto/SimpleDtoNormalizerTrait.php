<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\SimpleDto\Normalizers\NormalizerInterface;

/**
 * Trait for normalizer support.
 *
 * This trait provides methods to normalize data before Dto creation.
 * Normalizers ensure data is in the correct format and type.
 *
 * Example:
 *   $user = UserDto::fromArrayWithNormalizer($data, new TypeNormalizer([
 *       'age' => 'int',
 *       'active' => 'bool',
 *   ]));
 */
trait SimpleDtoNormalizerTrait
{
    /**
     * Create a Dto from array with normalization.
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
     * Create a Dto from array with multiple normalizers.
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
     * Normalize the Dto data.
     *
     * This creates a new Dto instance with normalized data.
     */
    public function normalizeWith(NormalizerInterface $normalizer): static
    {
        $data = $this->toArray();
        $data = $normalizer->normalize($data);

        return static::fromArray($data);
    }

    /**
     * Define normalizers to apply before Dto creation.
     *
     * Override this method to define default normalizers for your Dto.
     *
     * @return array<NormalizerInterface>
     */
    protected function inputNormalizers(): array
    {
        return [];
    }

    /**
     * Define normalizers to apply after Dto serialization.
     *
     * Override this method to define default normalizers for your Dto.
     *
     * @return array<NormalizerInterface>
     */
    protected function outputNormalizers(): array
    {
        return [];
    }
}
