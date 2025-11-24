<?php

declare(strict_types=1);

use event4u\DataHelpers\Exceptions\InvalidAttributeUsageException;
use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\DateTimeFormat;
use event4u\DataHelpers\LiteDto\Attributes\Hidden;
use event4u\DataHelpers\LiteDto\Attributes\Map;
use event4u\DataHelpers\LiteDto\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\DataCollectionOf;
use event4u\DataHelpers\SimpleDto\Attributes\Email;
use event4u\DataHelpers\SimpleDto\Attributes\HiddenFromArray;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Attributes\Trim;
use event4u\DataHelpers\SimpleDto\Attributes\Visible;

describe('Invalid Attribute Usage', function(): void {
    it('throws exception when SimpleDto uses LiteDto MapFrom attribute', function(): void {
        $dto = new class('') extends SimpleDto {
            public function __construct(
                #[MapFrom('user_name')]
                public readonly string $name,
            ) {}
        };

        expect(fn(): object => $dto::from(['user_name' => 'John']))
            ->toThrow(InvalidAttributeUsageException::class);
    });

    it('throws exception when SimpleDto uses LiteDto Map attribute', function(): void {
        $dto = new class('') extends SimpleDto {
            public function __construct(
                #[Map('user_name')]
                public readonly string $name,
            ) {}
        };

        expect(fn(): object => $dto::from(['user_name' => 'John']))
            ->toThrow(InvalidAttributeUsageException::class);
    });

    it('throws exception when SimpleDto uses LiteDto Hidden attribute', function(): void {
        $dto = new class('', '') extends SimpleDto {
            public function __construct(
                public readonly string $name,
                #[Hidden]
                public readonly string $password,
            ) {}
        };

        expect(fn(): object => $dto::from(['name' => 'John', 'password' => 'secret']))
            ->toThrow(InvalidAttributeUsageException::class);
    });

    it('throws exception when SimpleDto uses LiteDto DateTimeFormat attribute', function(): void {
        $dto = new class(new DateTimeImmutable()) extends SimpleDto {
            public function __construct(
                #[DateTimeFormat('Y-m-d')]
                public readonly DateTimeImmutable $date,
            ) {}
        };

        expect(fn(): object => $dto::from(['date' => '2024-01-15']))
            ->toThrow(InvalidAttributeUsageException::class);
    });

    it('throws exception when LiteDto uses SimpleDto MapFrom attribute', function(): void {
        $dto = new class('') extends LiteDto {
            public function __construct(
                #[\event4u\DataHelpers\SimpleDto\Attributes\MapFrom('user_name')]
                public readonly string $name,
            ) {}
        };

        expect(fn(): object => $dto::from(['user_name' => 'John']))
            ->toThrow(InvalidAttributeUsageException::class);
    });

    it('throws exception when LiteDto uses SimpleDto Visible attribute', function(): void {
        $dto = new class('') extends LiteDto {
            public function __construct(
                #[Visible]
                public readonly string $name,
            ) {}
        };

        expect(fn(): object => $dto::from(['name' => 'John']))
            ->toThrow(InvalidAttributeUsageException::class);
    });

    it('throws exception when LiteDto uses SimpleDto HiddenFromArray attribute', function(): void {
        $dto = new class('', '') extends LiteDto {
            public function __construct(
                public readonly string $name,
                #[HiddenFromArray]
                public readonly string $password,
            ) {}
        };

        expect(fn(): object => $dto::from(['name' => 'John', 'password' => 'secret']))
            ->toThrow(InvalidAttributeUsageException::class);
    });

    it('throws exception when LiteDto uses SimpleDto Required validation attribute', function(): void {
        $dto = new class('') extends LiteDto {
            public function __construct(
                #[Required]
                public readonly string $name,
            ) {}
        };

        expect(fn(): object => $dto::from(['name' => 'John']))
            ->toThrow(InvalidAttributeUsageException::class);
    });

    it('throws exception when LiteDto uses SimpleDto Email validation attribute', function(): void {
        $dto = new class('') extends LiteDto {
            public function __construct(
                #[Email]
                public readonly string $email,
            ) {}
        };

        expect(fn(): object => $dto::from(['email' => 'john@example.com']))
            ->toThrow(InvalidAttributeUsageException::class);
    });

    it('throws exception when LiteDto uses SimpleDto Trim transformation attribute', function(): void {
        $dto = new class('') extends LiteDto {
            public function __construct(
                #[Trim]
                public readonly string $name,
            ) {}
        };

        expect(fn(): object => $dto::from(['name' => '  John  ']))
            ->toThrow(InvalidAttributeUsageException::class);
    });

    it('throws exception when LiteDto uses SimpleDto DataCollectionOf attribute', function(): void {
        /** @var array<int, stdClass> $items */
        $items = [];
        $dto = new class($items) extends LiteDto {
            /** @param array<int, stdClass> $items */
            public function __construct(
                #[DataCollectionOf(stdClass::class)]
                public readonly array $items,
            ) {}
        };

        expect(fn(): object => $dto::from(['items' => []]))
            ->toThrow(InvalidAttributeUsageException::class);
    });

    it('allows SimpleDto to use SimpleDto attributes', function(): void {
        $dto = new class('') extends SimpleDto {
            public function __construct(
                #[\event4u\DataHelpers\SimpleDto\Attributes\MapFrom('user_name')]
                public readonly string $name,
            ) {}
        };

        $result = $dto::from(['user_name' => 'John']);
        expect($result->name)->toBe('John');
    });

    it('allows LiteDto to use LiteDto attributes', function(): void {
        $dto = new class('') extends LiteDto {
            public function __construct(
                #[MapFrom('user_name')]
                public readonly string $name,
            ) {}
        };

        $result = $dto::from(['user_name' => 'John']);
        expect($result->name)->toBe('John');
    });

    it('provides helpful error message for SimpleDto using LiteDto attribute', function(): void {
        $dto = new class('') extends SimpleDto {
            public function __construct(
                #[MapFrom('user_name')]
                public readonly string $name,
            ) {}
        };

        try {
            $dto::from(['user_name' => 'John']);
            $this->fail('Expected InvalidAttributeUsageException to be thrown');
        } catch (InvalidAttributeUsageException $invalidAttributeUsageException) {
            expect($invalidAttributeUsageException->getMessage())->toContain('SimpleDto');
            expect($invalidAttributeUsageException->getMessage())->toContain('LiteDto');
            expect($invalidAttributeUsageException->getMessage())->toContain('MapFrom');
            expect($invalidAttributeUsageException->getMessage())->toContain('SimpleDto\\Attributes\\MapFrom');
        }
    });

    it('provides helpful error message for LiteDto using SimpleDto attribute', function(): void {
        $dto = new class('') extends LiteDto {
            public function __construct(
                #[Required]
                public readonly string $name,
            ) {}
        };

        try {
            $dto::from(['name' => 'John']);
            $this->fail('Expected InvalidAttributeUsageException to be thrown');
        } catch (InvalidAttributeUsageException $invalidAttributeUsageException) {
            expect($invalidAttributeUsageException->getMessage())->toContain('LiteDto');
            expect($invalidAttributeUsageException->getMessage())->toContain('SimpleDto');
            expect($invalidAttributeUsageException->getMessage())->toContain('Required');
        }
    });
});
