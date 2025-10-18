<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Lazy as LazyAttribute;
use event4u\DataHelpers\Support\Lazy;

// Test DTOs
class TestLazyDTO1 extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly Lazy|string $content,
    ) {}
}

class TestLazyDTO2 extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        #[LazyAttribute]
        public readonly Lazy|string $content,
    ) {}
}

class TestLazyDTO3 extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly Lazy|string|null $content,
    ) {}
}

describe('Lazy Union Types', function () {
    it('wraps lazy properties with union type syntax', function () {
        $dto = TestLazyDTO1::fromArray(['title' => 'Test', 'content' => 'Content...']);
        
        expect($dto->title)->toBe('Test')
            ->and($dto->content)->toBeInstanceOf(Lazy::class)
            ->and($dto->content->isLoaded())->toBeTrue()
            ->and($dto->content->get())->toBe('Content...');
    });

    it('wraps lazy properties with attribute syntax', function () {
        $dto = TestLazyDTO2::fromArray(['title' => 'Test', 'content' => 'Content...']);
        
        expect($dto->title)->toBe('Test')
            ->and($dto->content)->toBeInstanceOf(Lazy::class)
            ->and($dto->content->isLoaded())->toBeTrue()
            ->and($dto->content->get())->toBe('Content...');
    });

    it('excludes lazy properties from toArray by default', function () {
        $dto = TestLazyDTO1::fromArray(['title' => 'Test', 'content' => 'Content...']);
        $array = $dto->toArray();
        
        expect($array)->toBe(['title' => 'Test'])
            ->and(array_key_exists('content', $array))->toBeFalse();
    });

    it('includes lazy properties when explicitly requested', function () {
        $dto = TestLazyDTO1::fromArray(['title' => 'Test', 'content' => 'Content...']);
        $array = $dto->include(['content'])->toArray();
        
        expect($array)->toBe([
            'title' => 'Test',
            'content' => 'Content...',
        ]);
    });

    it('includes all lazy properties with includeAll', function () {
        $dto = TestLazyDTO1::fromArray(['title' => 'Test', 'content' => 'Content...']);
        $array = $dto->includeAll()->toArray();
        
        expect($array)->toBe([
            'title' => 'Test',
            'content' => 'Content...',
        ]);
    });

    it('handles nullable lazy properties', function () {
        $dto = TestLazyDTO3::fromArray(['title' => 'Test', 'content' => null]);
        
        expect($dto->content)->toBeInstanceOf(Lazy::class)
            ->and($dto->content->get())->toBeNull();
    });

    it('excludes lazy properties from JSON by default', function () {
        $dto = TestLazyDTO1::fromArray(['title' => 'Test', 'content' => 'Content...']);
        $json = json_encode($dto);
        
        expect($json)->toBe('{"title":"Test"}');
    });

    it('includes lazy properties in JSON when requested', function () {
        $dto = TestLazyDTO1::fromArray(['title' => 'Test', 'content' => 'Content...']);
        $json = json_encode($dto->includeAll());
        
        expect($json)->toBe('{"title":"Test","content":"Content..."}');
    });
});

describe('Lazy Wrapper', function () {
    it('creates lazy with value', function () {
        $lazy = Lazy::value('test');
        
        expect($lazy->isLoaded())->toBeTrue()
            ->and($lazy->get())->toBe('test');
    });

    it('creates lazy with loader', function () {
        $lazy = Lazy::of(fn() => 'computed');
        
        expect($lazy->isLoaded())->toBeFalse();
        
        $value = $lazy->get();
        
        expect($value)->toBe('computed')
            ->and($lazy->isLoaded())->toBeTrue();
    });

    it('caches loaded value', function () {
        $counter = 0;
        $lazy = Lazy::of(function () use (&$counter) {
            $counter++;
            return 'value';
        });
        
        $lazy->get();
        $lazy->get();
        $lazy->get();
        
        expect($counter)->toBe(1);
    });

    it('maps lazy value', function () {
        $lazy = Lazy::value('hello');
        $mapped = $lazy->map(fn($v) => strtoupper($v));
        
        expect($mapped->get())->toBe('HELLO');
    });

    it('executes callback if loaded', function () {
        $lazy = Lazy::value('test');
        $executed = false;
        
        $lazy->ifLoaded(function ($value) use (&$executed) {
            $executed = true;
        });
        
        expect($executed)->toBeTrue();
    });

    it('does not execute callback if not loaded', function () {
        $lazy = Lazy::of(fn() => 'test');
        $executed = false;
        
        $lazy->ifLoaded(function ($value) use (&$executed) {
            $executed = true;
        });
        
        expect($executed)->toBeFalse();
    });

    it('executes callback if not loaded', function () {
        $lazy = Lazy::of(fn() => 'test');
        $executed = false;
        
        $lazy->ifNotLoaded(function () use (&$executed) {
            $executed = true;
        });
        
        expect($executed)->toBeTrue();
    });

    it('loads value explicitly', function () {
        $lazy = Lazy::of(fn() => 'test');
        
        expect($lazy->isLoaded())->toBeFalse();
        
        $lazy->load();
        
        expect($lazy->isLoaded())->toBeTrue()
            ->and($lazy->get())->toBe('test');
    });
});

