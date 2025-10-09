<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline;

use event4u\DataHelpers\DataMapper\Context\HookContext;

/**
 * Interface for reusable data transformers in the pipeline.
 *
 * Transformers can be used in DataMapper pipelines to apply
 * consistent transformations across multiple mappings.
 *
 * Example:
 *   class TrimStrings implements TransformerInterface {
 *       public function transform(mixed $value, HookContext $context): mixed {
 *           return is_string($value) ? trim($value) : $value;
 *       }
 *   }
 */
interface TransformerInterface
{
    /**
     * Transform a value during the mapping process.
     *
     * @param mixed $value The value to transform
     * @param HookContext $context The hook context (PairContext, WriteContext, etc.)
     * @return mixed The transformed value, or '__skip__' to skip writing
     */
    public function transform(mixed $value, HookContext $context): mixed;

    /**
     * Get the hook name this transformer should be attached to.
     *
     * Common hooks:
     * - 'preTransform': Before any transformation (after reading from source)
     * - 'postTransform': After transformation (before writing to target)
     * - 'beforeWrite': Just before writing to target
     *
     * @return string Hook name (e.g., 'preTransform', 'postTransform', 'beforeWrite')
     */
    public function getHook(): string;

    /**
     * Optional: Get filter prefix for conditional execution.
     *
     * Examples:
     * - 'src:user.email' - Only for source path user.email
     * - 'tgt:profile.' - Only for target paths starting with profile.
     * - 'mode:simple' - Only for simple mapping mode
     *
     * @return null|string Filter prefix, or null for no filtering
     */
    public function getFilter(): ?string;

    /**
     * Optional: Get template expression aliases for this transformer.
     *
     * When defined, this transformer can be used in template expressions
     * like {{ value | alias }} where 'alias' is one of the returned strings.
     *
     * Examples:
     * - ['trim'] - Can be used as {{ value | trim }}
     * - ['upper', 'uppercase'] - Can be used as {{ value | upper }} or {{ value | uppercase }}
     * - ['snake_case', 'snake'] - Multiple aliases for the same transformer
     *
     * @return array<int, string> Array of alias strings, or empty array if not usable in expressions
     */
    public function getAliases(): array;
}
