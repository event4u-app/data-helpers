<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\Map;

it('works with Map attribute for bidirectional mapping', function(): void {
    $dto = new class ('John Doe', 'john@example.com') extends LiteDto {
        public function __construct(
            #[Map('user_name')]
            public readonly string $name,
            #[Map('email_address')]
            public readonly string $email,
        ) {}
    };

    // Test input mapping (MapFrom behavior)
    $result = $dto::from([
        'user_name' => 'Jane Smith',
        'email_address' => 'jane@example.com',
    ]);

    expect($result->name)->toBe('Jane Smith')
        ->and($result->email)->toBe('jane@example.com');

    // Test output mapping (MapTo behavior)
    $array = $result->toArray();

    expect($array)->toBe([
        'user_name' => 'Jane Smith',
        'email_address' => 'jane@example.com',
    ]);
});

it('Map attribute has priority over MapFrom and MapTo', function(): void {
    $dto = new class ('', '') extends LiteDto {
        public function __construct(
            #[Map('mapped_name')]
            public readonly string $name,
            public readonly string $email,
        ) {}
    };

    $result = $dto::from([
        'mapped_name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    expect($result->name)->toBe('Test User')
        ->and($result->email)->toBe('test@example.com');

    $array = $result->toArray();

    expect($array)->toBe([
        'mapped_name' => 'Test User',
        'email' => 'test@example.com',
    ]);
});
