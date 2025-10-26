<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use Tests\Utils\Doctrine\Entities\User;
use Tests\Utils\Doctrine\Enums\Status;
use Tests\Utils\XMLs\Enums\Salutation;
use Tests\Utils\XMLs\Models\ContactPerson;

describe('DataMapper Enum Support', function(): void {
    it('maps string to BackedEnum in Eloquent model', function(): void {
        $data = [
            'salutation' => 'Mr',
            'surname' => 'Doe, John',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
        ];

        $mapping = [
            'salutation' => '{{ salutation }}',
            'surname' => '{{ surname }}',
            'email' => '{{ email }}',
            'phone' => '{{ phone }}',
        ];

        $contactPerson = new ContactPerson();
        $result = DataMapper::source($data)
            ->target($contactPerson)
            ->template($mapping)
            ->map()
            ->getTarget();

        expect($result)->toBeInstanceOf(ContactPerson::class);
        /** @var ContactPerson $result */
        expect($result->getSalutation())->toBeInstanceOf(Salutation::class);
        expect($result->getSalutation())->toBe(Salutation::MR);
        expect($result->getSalutation()?->value)->toBe('Mr');
    });

    it('maps different salutation values to correct enum cases', function(): void {
        $testCases = [
            ['input' => 'Mr', 'expected' => Salutation::MR],
            ['input' => 'Mrs', 'expected' => Salutation::MRS],
            ['input' => 'Miss', 'expected' => Salutation::MISS],
            ['input' => 'Diverse', 'expected' => Salutation::DIVERSE],
        ];

        foreach ($testCases as $testCase) {
            $data = ['salutation' => $testCase['input']];
            $mapping = ['salutation' => '{{ salutation }}'];

            $contactPerson = new ContactPerson();
            $result = DataMapper::source($data)
                ->target($contactPerson)
                ->template($mapping)
                ->map()
                ->getTarget();

            /** @var ContactPerson $result */
            expect($result->getSalutation())->toBe($testCase['expected']);
        }
    });

    it('handles case-insensitive enum mapping with tryFromAny', function(): void {
        $testCases = [
            'mr' => Salutation::MR,
            'MR' => Salutation::MR,
            'mr.' => Salutation::MR,
            'herr' => Salutation::MR,
            'mrs' => Salutation::MRS,
            'frau' => Salutation::MRS,
            'miss' => Salutation::MISS,
            'diverse' => Salutation::DIVERSE,
            'divers' => Salutation::DIVERSE,
        ];

        foreach ($testCases as $input => $expected) {
            $data = ['salutation' => $input];
            $mapping = ['salutation' => '{{ salutation }}'];

            $contactPerson = new ContactPerson();
            $result = DataMapper::source($data)
                ->target($contactPerson)
                ->template($mapping)
                ->map()
                ->getTarget();

            /** @var ContactPerson $result */
            expect($result->getSalutation())->toBe($expected)
                ->and($result->getSalutation()?->value)->toBe($expected->value);
        }
    });

    it('handles null salutation', function(): void {
        $data = ['salutation' => null, 'surname' => 'Doe'];
        $mapping = ['salutation' => '{{ salutation }}', 'surname' => '{{ surname }}'];

        $contactPerson = new ContactPerson();
        $result = DataMapper::source($data)
            ->target($contactPerson)
            ->template($mapping)
            ->map()
            ->getTarget();

        /** @var ContactPerson $result */
        expect($result->getSalutation())->toBeNull();
        expect($result->getSurname())->toBe('Doe');
    });

    it('preserves enum when already an enum instance', function(): void {
        $data = ['salutation' => Salutation::MRS];
        $mapping = ['salutation' => '{{ salutation }}'];

        $contactPerson = new ContactPerson();
        $result = DataMapper::source($data)
            ->target($contactPerson)
            ->template($mapping)
            ->map()
            ->getTarget();

        /** @var ContactPerson $result */
        expect($result->getSalutation())->toBe(Salutation::MRS);
    });

    it('maps enum from XML file', function(): void {
        $xmlFile = __DIR__ . '/../../utils/XMLs/version1.xml';

        $mapping = [
            'salutation' => '{{ contact_persons.contact_person.salutation }}',
            'surname' => '{{ contact_persons.contact_person.surname }}',
            'email' => '{{ contact_persons.contact_person.email }}',
        ];

        $contactPerson = new ContactPerson();
        $result = DataMapper::sourceFile($xmlFile)->target($contactPerson)->template($mapping)->map()->getTarget();

        expect($result)->toBeInstanceOf(ContactPerson::class);
        /** @var ContactPerson $result */
        expect($result->getSalutation())->toBeInstanceOf(Salutation::class);
        expect($result->getSalutation())->toBe(Salutation::MR);
        expect($result->getSurname())->toBe('Schmidt, Peter');
    });

    it('converts enum to string in toArray()', function(): void {
        $contactPerson = new ContactPerson();
        $contactPerson->setSalutation(Salutation::MR);
        $contactPerson->setSurname('Doe, John');
        $contactPerson->setEmail('john@example.com');

        $array = $contactPerson->toArray();

        expect($array['salutation'])->toBe('Mr');
        expect($array['surname'])->toBe('Doe, John');
    });
})->group('laravel');

describe('DataMapper Enum Support - Doctrine', function(): void {
    it('maps string to BackedEnum in Doctrine entity', function(): void {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
        ];

        $mapping = [
            'name' => '{{ name }}',
            'email' => '{{ email }}',
            'status' => '{{ status }}',
        ];

        $user = new User();
        $result = DataMapper::source($data)
            ->target($user)
            ->template($mapping)
            ->map()
            ->getTarget();

        expect($result)->toBeInstanceOf(User::class);
        /** @var User $result */
        expect($result->getStatus())->toBeInstanceOf(Status::class);
        expect($result->getStatus())->toBe(Status::ACTIVE);
        expect($result->getStatus()?->value)->toBe('active');
    });

    it('handles case-insensitive enum mapping in Doctrine entity', function(): void {
        $testCases = [
            'active' => Status::ACTIVE,
            'ACTIVE' => Status::ACTIVE,
            'aktiv' => Status::ACTIVE,
            'inactive' => Status::INACTIVE,
            'inaktiv' => Status::INACTIVE,
            'pending' => Status::PENDING,
            'ausstehend' => Status::PENDING,
        ];

        foreach ($testCases as $input => $expected) {
            $data = ['status' => $input];
            $mapping = ['status' => '{{ status }}'];

            $user = new User();
            $result = DataMapper::source($data)
                ->target($user)
                ->template($mapping)
                ->map()
                ->getTarget();

            /** @var User $result */
            expect($result->getStatus())->toBe($expected)
                ->and($result->getStatus()?->value)->toBe($expected->value);
        }
    });

    it('handles null status in Doctrine entity', function(): void {
        $data = ['name' => 'John Doe', 'status' => null];
        $mapping = ['name' => '{{ name }}', 'status' => '{{ status }}'];

        $user = new User();
        $result = DataMapper::source($data)
            ->target($user)
            ->template($mapping)
            ->map()
            ->getTarget();

        /** @var User $result */
        expect($result->getStatus())->toBeNull();
        expect($result->getName())->toBe('John Doe');
    });
})->group('doctrine');
