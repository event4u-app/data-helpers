<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Frameworks\Laravel\Resources;

use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\LiteDto\Support\LiteEngine;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Support\SimpleEngine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Laravel JsonResource for SimpleDto and LiteDto.
 *
 * This resource ensures that DTOs are properly serialized using their
 * toJsonArray() method, which respects #[DateTimeFormat] attributes and
 * formats DateTime objects as strings.
 *
 * Usage:
 * ```php
 * use event4u\DataHelpers\Frameworks\Laravel\Resources\DtoResource;
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
 * $dto = new EventDto('Conference', new DateTime('2024-01-15 10:30:00'));
 *
 * return new DtoResource($dto);
 * // {"data":{"title":"Conference","startDate":"2024-01-15 10:30:00"}}
 *
 * // Or without wrapping
 * return DtoResource::make($dto)->withoutWrapping();
 * // {"title":"Conference","startDate":"2024-01-15 10:30:00"}
 * ```
 *
 * Collection usage:
 * ```php
 * use event4u\DataHelpers\Frameworks\Laravel\Resources\DtoResourceCollection;
 *
 * $dtos = [
 *     new EventDto('Conference', new DateTime('2024-01-15 10:30:00')),
 *     new EventDto('Workshop', new DateTime('2024-01-16 14:00:00')),
 * ];
 *
 * return new DtoResourceCollection($dtos);
 * // {"data":[{"title":"Conference",...},{"title":"Workshop",...}]}
 * ```
 */
class DtoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Uses toJsonArray() which formats DateTime objects according to
     * #[DateTimeFormat] attributes.
     *
     * @return array<string, mixed>
     */
    public function toArray(\Illuminate\Http\Request $request): array
    {
        if ($this->resource instanceof SimpleDto) {
            return SimpleEngine::toJsonArray($this->resource);
        }

        if ($this->resource instanceof LiteDto) {
            return LiteEngine::toJsonArray($this->resource);
        }

        /** @var array<string, mixed> */
        return parent::toArray($request);
    }
}
