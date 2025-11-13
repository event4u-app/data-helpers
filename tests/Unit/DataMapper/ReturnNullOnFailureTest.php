<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\MapperExceptions;
use event4u\DataHelpers\SimpleDto;

describe('DataMapper returnNullOnFailure', function(): void {
    beforeEach(function(): void {
        MapperExceptions::reset();
    });

    afterEach(function(): void {
        MapperExceptions::reset();
    });

    it('returns null for entire target when returnNullOnFailure is true with invalid DateTime', function(): void {
        $projectDto = new class ('', null) extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly ?DateTime $date = null,
            ) {
            }
        };
        $projectDtoClass = $projectDto::class;

        $userDto = new class ('') extends SimpleDto {
            public function __construct(
                public readonly string $username,
            ) {
            }
        };
        $userDtoClass = $userDto::class;

        $template = [
            'project.name' => '{{ data.projectName }}',
            'project.date' => '{{ data.projectDate }}',
            'user.username' => '{{ data.userName }}',
        ];

        $source = [
            'data' => [
                'projectName' => 'Test Project',
                'projectDate' => 'invalid-date-xyz-123',  // Invalid date to trigger exception
                'userName' => 'john_doe',
            ],
        ];

        // Test with returnNullOnFailure(true) - default
        $dataMapper = DataMapper::template($template);
        $dataMapper->source($source);
        $dataMapper->target([
            'project' => $projectDtoClass,
            'user' => $userDtoClass,
        ]);
        $dataMapper->returnNullOnFailure(true);

        $result = $dataMapper->map()->getTarget();

        // Entire target should be null because project mapping failed
        expect($result)->toBeNull();
        expect(MapperExceptions::hasExceptions())->toBeTrue();
        expect(MapperExceptions::getExceptionCount())->toBeGreaterThan(0);
    });

    it('returns null for entire target when returnNullOnFailure is true with invalid int', function(): void {
        $productDto = new class ('', 0) extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly int $price,
            ) {
            }
        };
        $productDtoClass = $productDto::class;

        $userDto = new class ('') extends SimpleDto {
            public function __construct(
                public readonly string $username,
            ) {
            }
        };
        $userDtoClass = $userDto::class;

        $template = [
            'product.name' => '{{ data.productName }}',
            'product.price' => '{{ data.productPrice }}',
            'user.username' => '{{ data.userName }}',
        ];

        $source = [
            'data' => [
                'productName' => 'Widget',
                'productPrice' => 'not-a-number',  // Invalid int to trigger exception
                'userName' => 'jane_doe',
            ],
        ];

        // Test with returnNullOnFailure(true)
        $dataMapper = DataMapper::template($template);
        $dataMapper->source($source);
        $dataMapper->target([
            'product' => $productDtoClass,
            'user' => $userDtoClass,
        ]);
        $dataMapper->returnNullOnFailure(true);

        $result = $dataMapper->map()->getTarget();

        // Entire target should be null because product mapping failed
        expect($result)->toBeNull();
        expect(MapperExceptions::hasExceptions())->toBeTrue();
        expect(MapperExceptions::getExceptionCount())->toBeGreaterThan(0);
    });

    it('returns null for entire target when returnNullOnFailure is true with invalid array', function(): void {
        $orderDto = new class ('', []) extends SimpleDto {
            /** @param array<int, mixed> $items */
            public function __construct(
                public readonly string $orderId,
                public readonly array $items,
            ) {
            }
        };
        $orderDtoClass = $orderDto::class;

        $userDto = new class ('') extends SimpleDto {
            public function __construct(
                public readonly string $username,
            ) {
            }
        };
        $userDtoClass = $userDto::class;

        $template = [
            'order.orderId' => '{{ data.orderId }}',
            'order.items' => '{{ data.items }}',
            'user.username' => '{{ data.userName }}',
        ];

        $source = [
            'data' => [
                'orderId' => 'ORD-123',
                'items' => 'not-an-array',  // Invalid array to trigger exception
                'userName' => 'bob_smith',
            ],
        ];

        // Test with returnNullOnFailure(true)
        $dataMapper = DataMapper::template($template);
        $dataMapper->source($source);
        $dataMapper->target([
            'order' => $orderDtoClass,
            'user' => $userDtoClass,
        ]);
        $dataMapper->returnNullOnFailure(true);

        $result = $dataMapper->map()->getTarget();

        // Entire target should be null because order mapping failed
        expect($result)->toBeNull();
        expect(MapperExceptions::hasExceptions())->toBeTrue();
        expect(MapperExceptions::getExceptionCount())->toBeGreaterThan(0);
    });

    it('returns partial results when returnNullOnFailure is false with invalid DateTime', function(): void {
        $projectDto = new class ('', null) extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly ?DateTime $date = null,
            ) {
            }
        };
        $projectDtoClass = $projectDto::class;

        $userDto = new class ('') extends SimpleDto {
            public function __construct(
                public readonly string $username,
            ) {
            }
        };
        $userDtoClass = $userDto::class;

        $template = [
            'project.name' => '{{ data.projectName }}',
            'project.date' => '{{ data.projectDate }}',
            'user.username' => '{{ data.userName }}',
        ];

        $source = [
            'data' => [
                'projectName' => 'Test Project',
                'projectDate' => 'invalid-date-xyz-123',  // Invalid date to trigger exception
                'userName' => 'john_doe',
            ],
        ];

        // Test with returnNullOnFailure(false)
        $dataMapper = DataMapper::template($template);
        $dataMapper->source($source);
        $dataMapper->target([
            'project' => $projectDtoClass,
            'user' => $userDtoClass,
        ]);
        $dataMapper->returnNullOnFailure(false);

        $result = $dataMapper->map()->getTarget();

        // Should get partial results
        expect($result)->toBeArray();
        expect($result)->toHaveKey('project');
        expect($result)->toHaveKey('user');

        // Project should be null because DateTime casting failed
        expect($result['project'])->toBeNull();

        // User should be successfully mapped
        expect($result['user'])->toBeInstanceOf($userDtoClass);
        /** @var object{username: string} $user */
        $user = $result['user'];
        expect($user->username)->toBe('john_doe');

        // Exceptions should still be collected
        expect(MapperExceptions::hasExceptions())->toBeTrue();
        expect(MapperExceptions::getExceptionCount())->toBeGreaterThan(0);
    });

    it('returns partial results when returnNullOnFailure is false with invalid int', function(): void {
        $productDto = new class ('', 0) extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly int $price,
            ) {
            }
        };
        $productDtoClass = $productDto::class;

        $userDto = new class ('') extends SimpleDto {
            public function __construct(
                public readonly string $username,
            ) {
            }
        };
        $userDtoClass = $userDto::class;

        $template = [
            'product.name' => '{{ data.productName }}',
            'product.price' => '{{ data.productPrice }}',
            'user.username' => '{{ data.userName }}',
        ];

        $source = [
            'data' => [
                'productName' => 'Widget',
                'productPrice' => 'not-a-number',  // Invalid int to trigger exception
                'userName' => 'jane_doe',
            ],
        ];

        // Test with returnNullOnFailure(false)
        $dataMapper = DataMapper::template($template);
        $dataMapper->source($source);
        $dataMapper->target([
            'product' => $productDtoClass,
            'user' => $userDtoClass,
        ]);
        $dataMapper->returnNullOnFailure(false);

        $result = $dataMapper->map()->getTarget();

        // Should get partial results
        expect($result)->toBeArray();
        expect($result)->toHaveKey('product');
        expect($result)->toHaveKey('user');

        // Product should be null because int casting failed
        expect($result['product'])->toBeNull();

        // User should be successfully mapped
        expect($result['user'])->toBeInstanceOf($userDtoClass);
        /** @var object{username: string} $user */
        $user = $result['user'];
        expect($user->username)->toBe('jane_doe');

        // Exceptions should still be collected
        expect(MapperExceptions::hasExceptions())->toBeTrue();
        expect(MapperExceptions::getExceptionCount())->toBeGreaterThan(0);
    });

    it('returns complete result when no failures occur with returnNullOnFailure true', function(): void {
        $userDto = new class ('') extends SimpleDto {
            public function __construct(
                public readonly string $username,
            ) {
            }
        };
        $userDtoClass = $userDto::class;

        $productDto = new class ('', 0) extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly int $price,
            ) {
            }
        };
        $productDtoClass = $productDto::class;

        $template = [
            'user.username' => '{{ data.userName }}',
            'product.name' => '{{ data.productName }}',
            'product.price' => '{{ data.productPrice }}',
        ];

        $source = [
            'data' => [
                'userName' => 'jane_doe',
                'productName' => 'Widget',
                'productPrice' => '99',
            ],
        ];

        // Test with returnNullOnFailure(true) - should return complete result when no failures
        $dataMapper = DataMapper::template($template);
        $dataMapper->source($source);
        $dataMapper->target([
            'user' => $userDtoClass,
            'product' => $productDtoClass,
        ]);
        $dataMapper->returnNullOnFailure(true);

        $result = $dataMapper->map()->getTarget();

        // Should get complete result
        expect($result)->toBeArray();
        expect($result)->toHaveKey('user');
        expect($result)->toHaveKey('product');

        // User should be successfully mapped
        expect($result['user'])->toBeInstanceOf($userDtoClass);
        /** @var object{username: string} $user */
        $user = $result['user'];
        expect($user->username)->toBe('jane_doe');

        // Product should be successfully mapped
        expect($result['product'])->toBeInstanceOf($productDtoClass);
        /** @var object{name: string, price: int} $product */
        $product = $result['product'];
        expect($product->name)->toBe('Widget');
        expect($product->price)->toBe(99);

        // No exceptions should be collected
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('returns complete result when no failures occur with returnNullOnFailure false', function(): void {
        $userDto = new class ('') extends SimpleDto {
            public function __construct(
                public readonly string $username,
            ) {
            }
        };
        $userDtoClass = $userDto::class;

        $productDto = new class ('', 0) extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly int $price,
            ) {
            }
        };
        $productDtoClass = $productDto::class;

        $template = [
            'user.username' => '{{ data.userName }}',
            'product.name' => '{{ data.productName }}',
            'product.price' => '{{ data.productPrice }}',
        ];

        $source = [
            'data' => [
                'userName' => 'jane_doe',
                'productName' => 'Widget',
                'productPrice' => '99',
            ],
        ];

        // Test with returnNullOnFailure(false) - should return complete result when no failures
        $dataMapper = DataMapper::template($template);
        $dataMapper->source($source);
        $dataMapper->target([
            'user' => $userDtoClass,
            'product' => $productDtoClass,
        ]);
        $dataMapper->returnNullOnFailure(false);

        $result = $dataMapper->map()->getTarget();

        // Should get complete result
        expect($result)->toBeArray();
        expect($result)->toHaveKey('user');
        expect($result)->toHaveKey('product');

        // User should be successfully mapped
        expect($result['user'])->toBeInstanceOf($userDtoClass);
        /** @var object{username: string} $user */
        $user = $result['user'];
        expect($user->username)->toBe('jane_doe');

        // Product should be successfully mapped
        expect($result['product'])->toBeInstanceOf($productDtoClass);
        /** @var object{name: string, price: int} $product */
        $product = $result['product'];
        expect($product->name)->toBe('Widget');
        expect($product->price)->toBe(99);

        // No exceptions should be collected
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });
});
