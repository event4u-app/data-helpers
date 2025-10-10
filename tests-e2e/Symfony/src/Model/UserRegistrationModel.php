<?php

declare(strict_types=1);

namespace E2E\Symfony\Model;

use event4u\DataHelpers\MappedDataModel;

/**
 * Example MappedDataModel for testing Symfony integration.
 *
 * @property string $name
 * @property string $email
 * @property string|null $phone
 */
final class UserRegistrationModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'name' => '{{ request.user.full_name }}',
            'email' => '{{ request.user.email_address }}',
            'phone' => '{{ request.user.contact.phone }}',
        ];
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string'],
        ];
    }
}

