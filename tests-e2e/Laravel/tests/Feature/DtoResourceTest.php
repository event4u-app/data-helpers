<?php

declare(strict_types=1);

use event4u\DataHelpers\Frameworks\Laravel\Resources\DtoResource;
use event4u\DataHelpers\Frameworks\Laravel\Resources\DtoResourceCollection;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\DateTimeFormat;
use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\DateTimeFormat as LiteDateTimeFormat;
use Illuminate\Http\Request;

class LaravelEventDto extends SimpleDto
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

class LaravelLiteEventDto extends LiteDto
{
    public function __construct(
        public readonly string $title,

        #[LiteDateTimeFormat('Y-m-d H:i:s')]
        public readonly DateTime $startDate,
    ) {
    }
}

describe('Laravel DtoResource E2E', function (): void {
    it('DtoResource class exists', function (): void {
        expect(class_exists(DtoResource::class))->toBeTrue();
    });

    it('DtoResourceCollection class exists', function (): void {
        expect(class_exists(DtoResourceCollection::class))->toBeTrue();
    });

    it('can instantiate DtoResource with SimpleDto', function (): void {
        $dto = new LaravelEventDto(
            'Conference',
            new DateTime('2024-01-15 10:30:00'),
            new DateTime('2024-01-15')
        );

        $resource = new DtoResource($dto);

        expect($resource)->toBeInstanceOf(DtoResource::class);
    });

    it('can instantiate DtoResource with LiteDto', function (): void {
        $dto = new LaravelLiteEventDto(
            'Workshop',
            new DateTime('2024-01-16 14:00:00')
        );

        $resource = new DtoResource($dto);

        expect($resource)->toBeInstanceOf(DtoResource::class);
    });

    it('transforms SimpleDto to array with DateTimeFormat', function (): void {
        $dto = new LaravelEventDto(
            'Conference',
            new DateTime('2024-01-15 10:30:00'),
            new DateTime('2024-01-15')
        );

        $resource = new DtoResource($dto);
        $request = Request::create('/test', 'GET');

        $result = $resource->toArray($request);

        expect($result)->toBe([
            'title' => 'Conference',
            'startDate' => '2024-01-15 10:30:00',
            'germanDate' => '15.01.2024',
        ]);
    });

    it('transforms LiteDto to array with DateTimeFormat', function (): void {
        $dto = new LaravelLiteEventDto(
            'Workshop',
            new DateTime('2024-01-16 14:00:00')
        );

        $resource = new DtoResource($dto);
        $request = Request::create('/test', 'GET');

        $result = $resource->toArray($request);

        expect($result)->toBe([
            'title' => 'Workshop',
            'startDate' => '2024-01-16 14:00:00',
        ]);
    });

    it('converts SimpleDto to JSON response', function (): void {
        $dto = new LaravelEventDto(
            'Conference',
            new DateTime('2024-01-15 10:30:00'),
            new DateTime('2024-01-15')
        );

        $resource = new DtoResource($dto);
        $request = Request::create('/test', 'GET');

        $response = $resource->toResponse($request);

        expect($response->getStatusCode())->toBe(200);
        expect($response->headers->get('Content-Type'))->toContain('application/json');

        $content = $response->getContent();
        expect($content)->toBeJson();

        $decoded = json_decode($content, true);
        expect($decoded)->toHaveKey('data');
        expect($decoded['data'])->toBe([
            'title' => 'Conference',
            'startDate' => '2024-01-15 10:30:00',
            'germanDate' => '15.01.2024',
        ]);
    });

    it('converts LiteDto to JSON response', function (): void {
        $dto = new LaravelLiteEventDto(
            'Workshop',
            new DateTime('2024-01-16 14:00:00')
        );

        $resource = new DtoResource($dto);
        $request = Request::create('/test', 'GET');

        $response = $resource->toResponse($request);

        expect($response->getStatusCode())->toBe(200);

        $content = $response->getContent();
        expect($content)->toBeJson();

        $decoded = json_decode($content, true);
        expect($decoded)->toHaveKey('data');
        expect($decoded['data'])->toBe([
            'title' => 'Workshop',
            'startDate' => '2024-01-16 14:00:00',
        ]);
    });

    it('can instantiate DtoResourceCollection', function (): void {
        $dtos = [
            new LaravelEventDto(
                'Conference',
                new DateTime('2024-01-15 10:30:00'),
                new DateTime('2024-01-15')
            ),
            new LaravelEventDto(
                'Workshop',
                new DateTime('2024-01-16 14:00:00'),
                new DateTime('2024-01-16')
            ),
        ];

        $collection = new DtoResourceCollection($dtos);

        expect($collection)->toBeInstanceOf(DtoResourceCollection::class);
    });

    it('converts collection to JSON response', function (): void {
        $dtos = [
            new LaravelEventDto(
                'Conference',
                new DateTime('2024-01-15 10:30:00'),
                new DateTime('2024-01-15')
            ),
            new LaravelEventDto(
                'Workshop',
                new DateTime('2024-01-16 14:00:00'),
                new DateTime('2024-01-16')
            ),
        ];

        $collection = new DtoResourceCollection($dtos);
        $request = Request::create('/test', 'GET');

        $response = $collection->toResponse($request);

        expect($response->getStatusCode())->toBe(200);

        $content = $response->getContent();
        expect($content)->toBeJson();

        $decoded = json_decode($content, true);
        expect($decoded)->toHaveKey('data');
        expect($decoded['data'])->toBeArray();
        expect($decoded['data'])->toHaveCount(2);
        expect($decoded['data'][0])->toBe([
            'title' => 'Conference',
            'startDate' => '2024-01-15 10:30:00',
            'germanDate' => '15.01.2024',
        ]);
        expect($decoded['data'][1])->toBe([
            'title' => 'Workshop',
            'startDate' => '2024-01-16 14:00:00',
            'germanDate' => '16.01.2024',
        ]);
    });

    it('supports make() static method', function (): void {
        $dto = new LaravelEventDto(
            'Conference',
            new DateTime('2024-01-15 10:30:00'),
            new DateTime('2024-01-15')
        );

        $resource = DtoResource::make($dto);

        expect($resource)->toBeInstanceOf(DtoResource::class);
    });

    it('supports collection make() static method', function (): void {
        $dtos = [
            new LaravelEventDto(
                'Conference',
                new DateTime('2024-01-15 10:30:00'),
                new DateTime('2024-01-15')
            ),
        ];

        $collection = DtoResourceCollection::make($dtos);

        expect($collection)->toBeInstanceOf(DtoResourceCollection::class);
    });
});

