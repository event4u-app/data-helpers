<?php

declare(strict_types=1);

use event4u\DataHelpers\Frameworks\Symfony\Serializer\DtoNormalizer;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\DateTimeFormat;
use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\DateTimeFormat as LiteDateTimeFormat;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class EventDto extends SimpleDto
{
    public function __construct(
        public readonly string $title,

        #[DateTimeFormat('Y-m-d H:i:s')]
        public readonly DateTime $startDate,

        #[DateTimeFormat('d.m.Y')]
        public readonly DateTime $germanDate,
    ) {
    }
}

class LiteEventDto extends LiteDto
{
    public function __construct(
        public readonly string $title,

        #[LiteDateTimeFormat('Y-m-d H:i:s')]
        public readonly DateTime $startDate,
    ) {
    }
}

describe('Symfony DtoNormalizer E2E', function (): void {
    it('DtoNormalizer class exists', function (): void {
        expect(class_exists(DtoNormalizer::class))->toBeTrue();
    });

    it('can instantiate DtoNormalizer', function (): void {
        $normalizer = new DtoNormalizer();

        expect($normalizer)->toBeInstanceOf(DtoNormalizer::class);
    });

    it('normalizes SimpleDto with DateTimeFormat', function (): void {
        $dto = new EventDto(
            'Conference',
            new DateTime('2024-01-15 10:30:00'),
            new DateTime('2024-01-15')
        );

        $normalizer = new DtoNormalizer();
        $result = $normalizer->normalize($dto);

        expect($result)->toBe([
            'title' => 'Conference',
            'startDate' => '2024-01-15 10:30:00',
            'germanDate' => '15.01.2024',
        ]);
    });

    it('normalizes LiteDto with DateTimeFormat', function (): void {
        $dto = new LiteEventDto(
            'Workshop',
            new DateTime('2024-01-16 14:00:00')
        );

        $normalizer = new DtoNormalizer();
        $result = $normalizer->normalize($dto);

        expect($result)->toBe([
            'title' => 'Workshop',
            'startDate' => '2024-01-16 14:00:00',
        ]);
    });

    it('serializes SimpleDto to JSON using Symfony Serializer', function (): void {
        $dto = new EventDto(
            'Conference',
            new DateTime('2024-01-15 10:30:00'),
            new DateTime('2024-01-15')
        );

        $normalizers = [new DtoNormalizer(), new ObjectNormalizer()];
        $encoders = [new JsonEncoder()];
        $serializer = new Serializer($normalizers, $encoders);

        $json = $serializer->serialize($dto, 'json');

        expect($json)->toBeJson();

        $decoded = json_decode($json, true);
        expect($decoded)->toBe([
            'title' => 'Conference',
            'startDate' => '2024-01-15 10:30:00',
            'germanDate' => '15.01.2024',
        ]);
    });

    it('serializes LiteDto to JSON using Symfony Serializer', function (): void {
        $dto = new LiteEventDto(
            'Workshop',
            new DateTime('2024-01-16 14:00:00')
        );

        $normalizers = [new DtoNormalizer(), new ObjectNormalizer()];
        $encoders = [new JsonEncoder()];
        $serializer = new Serializer($normalizers, $encoders);

        $json = $serializer->serialize($dto, 'json');

        expect($json)->toBeJson();

        $decoded = json_decode($json, true);
        expect($decoded)->toBe([
            'title' => 'Workshop',
            'startDate' => '2024-01-16 14:00:00',
        ]);
    });

    it('serializes array of DTOs to JSON', function (): void {
        $dtos = [
            new EventDto(
                'Conference',
                new DateTime('2024-01-15 10:30:00'),
                new DateTime('2024-01-15')
            ),
            new EventDto(
                'Workshop',
                new DateTime('2024-01-16 14:00:00'),
                new DateTime('2024-01-16')
            ),
        ];

        $normalizers = [new DtoNormalizer(), new ObjectNormalizer()];
        $encoders = [new JsonEncoder()];
        $serializer = new Serializer($normalizers, $encoders);

        $json = $serializer->serialize($dtos, 'json');

        expect($json)->toBeJson();

        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
        expect($decoded)->toHaveCount(2);
        expect($decoded[0])->toBe([
            'title' => 'Conference',
            'startDate' => '2024-01-15 10:30:00',
            'germanDate' => '15.01.2024',
        ]);
        expect($decoded[1])->toBe([
            'title' => 'Workshop',
            'startDate' => '2024-01-16 14:00:00',
            'germanDate' => '16.01.2024',
        ]);
    });

    it('DtoNormalizer supports SimpleDto', function (): void {
        $dto = new EventDto(
            'Test',
            new DateTime('2024-01-15 10:30:00'),
            new DateTime('2024-01-15')
        );

        $normalizer = new DtoNormalizer();

        expect($normalizer->supportsNormalization($dto))->toBeTrue();
    });

    it('DtoNormalizer supports LiteDto', function (): void {
        $dto = new LiteEventDto(
            'Test',
            new DateTime('2024-01-15 10:30:00')
        );

        $normalizer = new DtoNormalizer();

        expect($normalizer->supportsNormalization($dto))->toBeTrue();
    });

    it('DtoNormalizer does not support non-DTO objects', function (): void {
        $normalizer = new DtoNormalizer();

        expect($normalizer->supportsNormalization(new stdClass()))->toBeFalse();
        expect($normalizer->supportsNormalization('string'))->toBeFalse();
        expect($normalizer->supportsNormalization(123))->toBeFalse();
        expect($normalizer->supportsNormalization([]))->toBeFalse();
    });

    it('DtoNormalizer returns supported types', function (): void {
        $normalizer = new DtoNormalizer();
        $types = $normalizer->getSupportedTypes(null);

        expect($types)->toHaveKey(SimpleDto::class);
        expect($types[SimpleDto::class])->toBeTrue();
        expect($types)->toHaveKey(LiteDto::class);
        expect($types[LiteDto::class])->toBeTrue();
    });
});

