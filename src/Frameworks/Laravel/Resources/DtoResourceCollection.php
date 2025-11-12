<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Frameworks\Laravel\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Laravel ResourceCollection for DTOs.
 *
 * This collection ensures that DTOs are properly serialized using their
 * toJsonArray() method, which respects #[DateTimeFormat] attributes and
 * formats DateTime objects as strings.
 *
 * Usage:
 * ```php
 * use event4u\DataHelpers\Frameworks\Laravel\Resources\DtoResourceCollection;
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
 * // In controller
 * $dtos = [
 *     new EventDto('Conference', new DateTime('2024-01-15 10:30:00')),
 *     new EventDto('Workshop', new DateTime('2024-01-16 14:00:00')),
 * ];
 *
 * return new DtoResourceCollection($dtos);
 * // {"data":[{"title":"Conference","startDate":"2024-01-15 10:30:00"},...]}
 *
 * // Or without wrapping
 * return DtoResourceCollection::make($dtos)->withoutWrapping();
 * // [{"title":"Conference","startDate":"2024-01-15 10:30:00"},...]
 * ```
 *
 * With pagination:
 * ```php
 * $paginator = Event::paginate(15);
 * $dtos = $paginator->map(fn($event) => EventDto::fromModel($event));
 *
 * return new DtoResourceCollection($dtos);
 * // {"data":[...],"links":{...},"meta":{...}}
 * ```
 */
class DtoResourceCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = DtoResource::class;
}
