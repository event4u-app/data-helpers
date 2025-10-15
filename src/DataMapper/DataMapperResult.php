<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper;

use event4u\DataHelpers\DataFilter;
use event4u\DataHelpers\Exceptions\ConversionException;
use Illuminate\Support\Collection;
use SimpleXMLElement;
use Throwable;

/**
 * DataMapperResult - Result handling with query support.
 *
 * Provides methods to access and transform mapping results.
 *
 * Example:
 *   $result = $mapper->map();
 *   $result->getTarget();
 *   $result->toJson();
 *   $result->toArray();
 *   $result->query()->where('age', '>', 18)->toArray();
 */
final class DataMapperResult
{
    private mixed $result;

    private mixed $source;

    /** @var array<int|string, mixed> */
    private array $template;

    private ?DataMapperExceptionHandler $exceptionHandler = null;

    /**
     * Create a new DataMapperResult instance.
     *
     * @param mixed $result Mapping result
     * @param mixed $source Original source
     * @param array<int|string, mixed> $template Mapping template
     * @param DataMapperExceptionHandler|null $exceptionHandler Exception handler for this result
     */
    public function __construct(
        mixed $result,
        mixed $source,
        array $template,
        ?DataMapperExceptionHandler $exceptionHandler = null
    ) {
        $this->result = $result;
        $this->source = $source;
        $this->template = $template;
        $this->exceptionHandler = $exceptionHandler;
    }

    /**
     * Get the target result.
     */
    public function getTarget(): mixed
    {
        return $this->result;
    }

    /**
     * Get the original source.
     */
    public function getSource(): mixed
    {
        return $this->source;
    }

    /**
     * Get the template.
     *
     * @return array<int|string, mixed>
     */
    public function getTemplate(): array
    {
        return $this->template;
    }

    /**
     * Convert result to JSON.
     *
     * @throws DataMapperException If conversion fails
     */
    public function toJson(int $options = 0, int $depth = 512): string
    {
        $array = $this->toArray();
        $json = json_encode($array, $options, $depth);

        if (false === $json) {
            throw new ConversionException('Failed to convert result to JSON: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Convert result to array.
     *
     * @return array<int|string, mixed>
     * @throws DataMapperException If conversion fails
     */
    public function toArray(): array
    {
        // Already an array
        if (is_array($this->result)) {
            return $this->result;
        }

        // Collection
        if ($this->result instanceof Collection) {
            return $this->result->all();
        }

        // Object - convert to array
        if (is_object($this->result)) {
            return $this->objectToArray($this->result);
        }

        // JSON string
        if (is_string($this->result) && $this->isJson($this->result)) {
            $decoded = json_decode($this->result, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // XML string
        if (is_string($this->result) && $this->isXml($this->result)) {
            return $this->xmlToArray($this->result);
        }

        throw new ConversionException('Cannot convert result to array. Type: ' . get_debug_type($this->result));
    }

    /**
     * Convert result to Collection.
     *
     * @throws DataMapperException If conversion fails
     */
    public function toCollection(): Collection
    {
        if (!class_exists(Collection::class)) {
            throw new ConversionException('Illuminate\Support\Collection not available');
        }

        return new Collection($this->toArray());
    }

    /**
     * Start a query on the result.
     */
    public function query(): DataFilter
    {
        return DataFilter::query($this->result);
    }

    /**
     * Check if string is JSON.
     */
    private function isJson(string $string): bool
    {
        json_decode($string);

        return JSON_ERROR_NONE === json_last_error();
    }

    /**
     * Check if string is XML.
     */
    private function isXml(string $string): bool
    {
        $trimmed = trim($string);

        return str_starts_with($trimmed, '<') && str_ends_with($trimmed, '>');
    }

    /**
     * Convert XML string to array.
     *
     * @return array<int|string, mixed>
     */
    private function xmlToArray(string $xml): array
    {
        $element = new SimpleXMLElement($xml);

        return json_decode(json_encode($element), true) ?: [];
    }

    /**
     * Convert object to array recursively.
     *
     * @return array<int|string, mixed>
     */
    private function objectToArray(object $object): array
    {
        // Use get_object_vars for simple objects
        $array = get_object_vars($object);

        // Recursively convert nested objects
        foreach ($array as $key => $value) {
            if (is_object($value)) {
                $array[$key] = $this->objectToArray($value);
            } elseif (is_array($value)) {
                $array[$key] = array_map(
                    fn ($item) => is_object($item) ? $this->objectToArray($item) : $item,
                    $value
                );
            }
        }

        return $array;
    }

    /**
     * Check if any exceptions have been collected during mapping.
     */
    public function hasExceptions(): bool
    {
        return null !== $this->exceptionHandler && $this->exceptionHandler->hasExceptions();
    }

    /**
     * Get all collected exceptions.
     *
     * @return array<int, Throwable>
     */
    public function getExceptions(): array
    {
        if (null === $this->exceptionHandler) {
            return [];
        }

        return $this->exceptionHandler->getExceptions();
    }

    /**
     * Get the last collected exception or null if none.
     */
    public function getLastException(): ?Throwable
    {
        if (null === $this->exceptionHandler) {
            return null;
        }

        return $this->exceptionHandler->getLastException();
    }

    /**
     * Get the number of collected exceptions.
     */
    public function getExceptionCount(): int
    {
        if (null === $this->exceptionHandler) {
            return 0;
        }

        return $this->exceptionHandler->getExceptionCount();
    }

    /**
     * Throw the last collected exception if any.
     *
     * @throws Throwable
     */
    public function throwLastException(): void
    {
        if (null !== $this->exceptionHandler) {
            $this->exceptionHandler->throwLastException();
        }
    }

    /**
     * Throw all collected exceptions.
     *
     * If only one exception was collected, it is thrown directly.
     * If multiple exceptions were collected, they are wrapped in a CollectedExceptionsException.
     *
     * @throws Throwable
     */
    public function throwCollectedExceptions(): void
    {
        if (null !== $this->exceptionHandler) {
            $this->exceptionHandler->throwCollectedExceptions();
        }
    }
}
