<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Validation;

use event4u\DataHelpers\Exceptions\ValidationException;

/**
 * Format validation errors as HTML for forms.
 *
 * Provides various HTML formats for displaying validation errors:
 * - Bootstrap 5 alerts
 * - Tailwind CSS alerts
 * - Simple HTML lists
 * - Inline field errors
 *
 * Example:
 * ```php
 * try {
 *     $dto = UserDto::validateAndCreate($data);
 * } catch (ValidationException $e) {
 *     echo HtmlErrorFormatter::bootstrap($e);
 * }
 * ```
 */
class HtmlErrorFormatter
{
    /**
     * Format errors as Bootstrap 5 alert.
     *
     * @param string $alertClass Bootstrap alert class (default: alert-danger)
     */
    public static function bootstrap(ValidationException $exception, string $alertClass = 'alert-danger'): string
    {
        $errors = $exception->errors();

        if ([] === $errors) {
            return '';
        }

        $html = '<div class="alert ' . htmlspecialchars($alertClass) . ' alert-dismissible fade show" role="alert">';
        $html .= '<strong>Validation Error!</strong> Please correct the following errors:';
        $html .= '<ul class="mb-0 mt-2">';

        foreach ($errors as $messages) {
            foreach ($messages as $message) {
                $html .= '<li>' . htmlspecialchars($message) . '</li>';
            }
        }

        $html .= '</ul>';
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';

        return $html . '</div>';
    }

    /**
     * Format errors as Tailwind CSS alert.
     *
     * @param string $bgColor Background color class (default: bg-red-100)
     * @param string $textColor Text color class (default: text-red-700)
     * @param string $borderColor Border color class (default: border-red-400)
     */
    public static function tailwind(
        ValidationException $exception,
        string $bgColor = 'bg-red-100',
        string $textColor = 'text-red-700',
        string $borderColor = 'border-red-400'
    ): string {
        $errors = $exception->errors();

        if ([] === $errors) {
            return '';
        }

        $html = '<div class="' . htmlspecialchars($bgColor) . ' border ' . htmlspecialchars(
            $borderColor
        ) . ' ' . htmlspecialchars(
            $textColor
        ) . ' px-4 py-3 rounded relative" role="alert">';
        $html .= '<strong class="font-bold">Validation Error!</strong>';
        $html .= '<span class="block sm:inline"> Please correct the following errors:</span>';
        $html .= '<ul class="mt-2 list-disc list-inside">';

        foreach ($errors as $messages) {
            foreach ($messages as $message) {
                $html .= '<li>' . htmlspecialchars($message) . '</li>';
            }
        }

        $html .= '</ul>';

        return $html . '</div>';
    }

    /**
     * Format errors as simple HTML list.
     *
     * @param string $class CSS class for the container div
     */
    public static function simple(ValidationException $exception, string $class = 'validation-errors'): string
    {
        $errors = $exception->errors();

        if ([] === $errors) {
            return '';
        }

        $html = '<div class="' . htmlspecialchars($class) . '">';
        $html .= '<p><strong>Validation Error:</strong></p>';
        $html .= '<ul>';

        foreach ($errors as $messages) {
            foreach ($messages as $message) {
                $html .= '<li>' . htmlspecialchars($message) . '</li>';
            }
        }

        $html .= '</ul>';

        return $html . '</div>';
    }

    /**
     * Get errors for a specific field as HTML.
     *
     * Useful for inline field validation.
     *
     * @param string $field Field name
     * @param string $class CSS class for error messages
     */
    public static function field(
        ValidationException $exception,
        string $field,
        string $class = 'invalid-feedback'
    ): string
    {
        $errors = $exception->errorsFor($field);

        if ([] === $errors) {
            return '';
        }

        $html = '';
        foreach ($errors as $error) {
            $html .= '<div class="' . htmlspecialchars($class) . '">' . htmlspecialchars($error) . '</div>';
        }

        return $html;
    }

    /**
     * Get first error for a specific field as HTML.
     *
     * @param string $field Field name
     * @param string $class CSS class for error message
     */
    public static function firstField(
        ValidationException $exception,
        string $field,
        string $class = 'invalid-feedback'
    ): string
    {
        $error = $exception->firstError($field);

        if (null === $error) {
            return '';
        }

        return '<div class="' . htmlspecialchars($class) . '">' . htmlspecialchars($error) . '</div>';
    }

    /**
     * Format errors as Bootstrap 5 field errors (for inline validation).
     *
     * Returns array of field => HTML error message.
     *
     * @return array<string, string>
     */
    public static function bootstrapFields(ValidationException $exception): array
    {
        $result = [];

        foreach ($exception->errors() as $field => $messages) {
            $html = '';
            foreach ($messages as $message) {
                $html .= '<div class="invalid-feedback d-block">' . htmlspecialchars($message) . '</div>';
            }
            $result[$field] = $html;
        }

        return $result;
    }

    /**
     * Format errors as Tailwind CSS field errors (for inline validation).
     *
     * Returns array of field => HTML error message.
     *
     * @param string $class CSS class for error messages
     * @return array<string, string>
     */
    public static function tailwindFields(
        ValidationException $exception,
        string $class = 'text-red-600 text-sm mt-1'
    ): array
    {
        $result = [];

        foreach ($exception->errors() as $field => $messages) {
            $html = '';
            foreach ($messages as $message) {
                $html .= '<p class="' . htmlspecialchars($class) . '">' . htmlspecialchars($message) . '</p>';
            }
            $result[$field] = $html;
        }

        return $result;
    }

    /**
     * Format errors as JSON (for AJAX forms).
     *
     * @param int $options JSON encode options
     */
    public static function json(ValidationException $exception, int $options = 0): string
    {
        return $exception->toJson($options);
    }

    /**
     * Check if a field has errors.
     *
     * Useful for adding CSS classes to form fields.
     *
     * @param string $field Field name
     */
    public static function hasError(ValidationException $exception, string $field): bool
    {
        return $exception->hasError($field);
    }

    /**
     * Get CSS class for field based on validation state.
     *
     * @param string $field Field name
     * @param string $errorClass CSS class for invalid fields (default: is-invalid)
     * @param string $validClass CSS class for valid fields (default: empty)
     */
    public static function fieldClass(
        ValidationException $exception,
        string $field,
        string $errorClass = 'is-invalid',
        string $validClass = ''
    ): string {
        return $exception->hasError($field) ? $errorClass : $validClass;
    }
}
