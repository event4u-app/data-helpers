<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\SimpleDto;

describe('DataMapper Target DTO Instantiation', function(): void {
    it('creates DTO instance when target is a class name', function(): void {
        $dto = new class ('John', 'Doe') extends SimpleDto {
            public function __construct(
                public readonly string $firstName,
                public readonly string $lastName,
            ) {
            }
        };
        $dtoClass = $dto::class;

        $template = [
            'firstName' => '{{ first }}',
            'lastName' => '{{ last }}',
        ];

        $source = [
            'first' => 'Jane',
            'last' => 'Smith',
        ];

        $dataMapper = DataMapper::template($template);
        $dataMapper->source($source);
        $dataMapper->target($dtoClass);

        $result = $dataMapper->map()->getTarget();

        expect($result)->toBeInstanceOf($dtoClass);
        /** @var object{firstName: string, lastName: string} $result */
        expect($result->firstName)->toBe('Jane');
        expect($result->lastName)->toBe('Smith');
    });

    it('creates DTO instances when target is array with class names', function(): void {
        $projectDto = new class ('', '') extends SimpleDto {
            public function __construct(
                public readonly string $externalProjectId,
                public readonly string $externalProjectNumber,
            ) {
            }
        };
        $projectDtoClass = $projectDto::class;

        $template = [
            'project.externalProjectId' => '{{ LVDATA.LV.ID_LV }}',
            'project.externalProjectNumber' => '{{ LVDATA.LV.NR_LV }}',
        ];

        $source = [
            'LVDATA' => [
                'LV' => [
                    'ID_LV' => '2075436601850',
                    'NR_LV' => 'B25049',
                ],
            ],
        ];

        $dataMapper = DataMapper::template($template);
        $dataMapper->source($source);
        $dataMapper->target([
            'project' => $projectDtoClass,
        ]);
        $result = $dataMapper->map()->getTarget();

        expect($result)->toBeArray();
        expect($result)->toHaveKey('project');
        expect($result['project'])->toBeInstanceOf($projectDtoClass);
        /** @var object{externalProjectId: string, externalProjectNumber: string} $project */
        $project = $result['project'];
        expect($project->externalProjectId)->toBe('2075436601850');
        expect($project->externalProjectNumber)->toBe('B25049');
    });

    it('creates multiple DTO instances when target has multiple class names', function(): void {
        $userDto = new class ('', '') extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly string $email,
            ) {
            }
        };
        $userDtoClass = $userDto::class;

        $addressDto = new class ('', '') extends SimpleDto {
            public function __construct(
                public readonly string $street,
                public readonly string $city,
            ) {
            }
        };
        $addressDtoClass = $addressDto::class;

        $template = [
            'user.name' => '{{ data.user_name }}',
            'user.email' => '{{ data.user_email }}',
            'address.street' => '{{ data.street }}',
            'address.city' => '{{ data.city }}',
        ];

        $source = [
            'data' => [
                'user_name' => 'John Doe',
                'user_email' => 'john@example.com',
                'street' => 'Main St',
                'city' => 'Berlin',
            ],
        ];

        $dataMapper = DataMapper::template($template);
        $dataMapper->source($source);
        $dataMapper->target([
            'user' => $userDtoClass,
            'address' => $addressDtoClass,
        ]);
        $result = $dataMapper->map()->getTarget();

        expect($result)->toBeArray();
        expect($result)->toHaveKey('user');
        expect($result)->toHaveKey('address');
        expect($result['user'])->toBeInstanceOf($userDtoClass);
        expect($result['address'])->toBeInstanceOf($addressDtoClass);
        /** @var object{name: string, email: string} $user */
        $user = $result['user'];
        /** @var object{street: string, city: string} $address */
        $address = $result['address'];
        expect($user->name)->toBe('John Doe');
        expect($user->email)->toBe('john@example.com');
        expect($address->street)->toBe('Main St');
        expect($address->city)->toBe('Berlin');
    });

    it('keeps arrays when target value is not a class name', function(): void {
        $template = [
            'project.id' => '{{ data.id }}',
            'project.name' => '{{ data.name }}',
            'metadata.created' => '{{ data.created }}',
        ];

        $source = [
            'data' => [
                'id' => '123',
                'name' => 'Test Project',
                'created' => '2024-01-15',
            ],
        ];

        $dataMapper = DataMapper::template($template);
        $dataMapper->source($source);
        $dataMapper->target([
            'project' => [],
            'metadata' => [],
        ]);
        $result = $dataMapper->map()->getTarget();

        expect($result)->toBeArray();
        expect($result)->toHaveKey('project');
        expect($result)->toHaveKey('metadata');
        expect($result['project'])->toBeArray();
        expect($result['metadata'])->toBeArray();
        expect($result['project']['id'])->toBe('123');
        expect($result['project']['name'])->toBe('Test Project');
        expect($result['metadata']['created'])->toBe('2024-01-15');
    });

    it('creates DTO instance from flat template', function(): void {
        $dto = new class ('', '') extends SimpleDto {
            public function __construct(
                public readonly string $externalProjectId,
                public readonly string $externalProjectNumber,
            ) {
            }
        };
        $dtoClass = $dto::class;

        $template = [
            'externalProjectId' => '{{ LVDATA.LV.ID_LV }}',
            'externalProjectNumber' => '{{ LVDATA.LV.NR_LV }}',
        ];

        $source = [
            'LVDATA' => [
                'LV' => [
                    'ID_LV' => '2075436601850',
                    'NR_LV' => 'B25049',
                ],
            ],
        ];

        $dataMapper = DataMapper::template($template);
        $dataMapper->source($source);
        $dataMapper->target($dtoClass);

        $result = $dataMapper->map()->getTarget();

        expect($result)->toBeInstanceOf($dtoClass);
        /** @var object{externalProjectId: string, externalProjectNumber: string} $result */
        expect($result->externalProjectId)->toBe('2075436601850');
        expect($result->externalProjectNumber)->toBe('B25049');
    });

    it('creates stdClass instance when target is stdClass', function(): void {
        $template = [
            'name' => '{{ data.name }}',
            'email' => '{{ data.email }}',
        ];

        $source = [
            'data' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ];

        $dataMapper = DataMapper::template($template);
        $dataMapper->source($source);
        $dataMapper->target(stdClass::class);

        $result = $dataMapper->map()->getTarget();

        expect($result)->toBeInstanceOf(stdClass::class);
        /** @var object{name: string, email: string} $result */
        expect($result->name)->toBe('John Doe');
        expect($result->email)->toBe('john@example.com');
    });

    it('creates named class instance when target is a named class', function(): void {
        $class = new class {
            public string $firstName = '';
            public string $lastName = '';
        };
        $className = $class::class;

        $template = [
            'firstName' => '{{ first }}',
            'lastName' => '{{ last }}',
        ];

        $source = [
            'first' => 'Jane',
            'last' => 'Smith',
        ];

        $dataMapper = DataMapper::template($template);
        $dataMapper->source($source);
        $dataMapper->target($className);

        $result = $dataMapper->map()->getTarget();

        expect($result)->toBeInstanceOf($className);
        /** @var object{firstName: string, lastName: string} $result */
        expect($result->firstName)->toBe('Jane');
        expect($result->lastName)->toBe('Smith');
    });

    it('writes to existing object instance when target is an object', function(): void {
        $object = new class {
            public string $name = 'Original';
            public string $email = 'original@example.com';
        };

        $template = [
            'name' => '{{ data.name }}',
            'email' => '{{ data.email }}',
        ];

        $source = [
            'data' => [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ],
        ];

        $dataMapper = DataMapper::template($template);
        $dataMapper->source($source);
        $dataMapper->target($object);

        $result = $dataMapper->map()->getTarget();

        expect($result)->toBe($object);
        expect($object->name)->toBe('Updated Name');
        expect($object->email)->toBe('updated@example.com');
    });

    it('writes to existing DTO instance when target is a DTO object', function(): void {
        $dto = new class ('Original', 'original@example.com') extends SimpleDto {
            public function __construct(
                public string $name,
                public string $email,
            ) {
            }
        };

        $template = [
            'name' => '{{ data.name }}',
            'email' => '{{ data.email }}',
        ];

        $source = [
            'data' => [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ],
        ];

        $dataMapper = DataMapper::template($template);
        $dataMapper->source($source);
        $dataMapper->target($dto);

        $result = $dataMapper->map()->getTarget();

        expect($result)->toBe($dto);
        expect($dto->name)->toBe('Updated Name');
        expect($dto->email)->toBe('updated@example.com');
    });
});
