<?php

declare(strict_types=1);

use event4u\DataHelpers\Helpers\ObjectHelper;

/**
 * Tests for ObjectHelper.
 *
 * @internal
 */
describe('ObjectHelper', function(): void {
    describe('copy() - Shallow copy', function(): void {
        it('creates a shallow copy of an object', function(): void {
            $original = new class {
                public string $name = 'Alice';
                public int $age = 30;
            };

            $copy = ObjectHelper::copy($original, false);

            expect($copy)->not->toBe($original);
            /** @var object{name: string, age: int} $copy */
            expect($copy->name)->toBe('Alice');
            expect($copy->age)->toBe(30);
        });

        it('shallow copy shares nested objects', function(): void {
            $nested = new stdClass();
            $nested->value = 'original';

            $original = new class($nested) {
                public function __construct(public object $nested) {}
            };

            $copy = ObjectHelper::copy($original, false);

            // Shallow copy: nested object is shared
            /** @var object{nested: object{value: string}} $copy */
            /** @var object{nested: object{value: string}} $original */
            /** @phpstan-ignore-next-line unknown */
            expect($copy->nested)->toBe($original->nested);

            // Modifying nested object affects both
            /** @var object{value: string} $nestedCopy */
            $nestedCopy = $copy->nested;
            /** @phpstan-ignore-next-line unknown */
            $nestedCopy->value = 'modified';
            /** @var object{value: string} $nestedOriginal */
            $nestedOriginal = $original->nested;
            expect($nestedOriginal->value)->toBe('modified');
        });
    });

    describe('copy() - Deep copy (recursive)', function(): void {
        it('creates a deep copy of an object with nested objects', function(): void {
            $nested = new stdClass();
            $nested->value = 'original';

            $original = new class($nested) {
                public function __construct(public object $nested) {}
            };

            $copy = ObjectHelper::copy($original, true);

            // Deep copy: nested object is copied
            /** @var object{nested: object{value: string}} $copy */
            /** @var object{nested: object{value: string}} $original */
            /** @phpstan-ignore-next-line unknown */
            expect($copy->nested)->not->toBe($original->nested);
            expect($copy->nested->value)->toBe('original');

            // Modifying nested object does NOT affect original
            /** @phpstan-ignore-next-line unknown */
            $copy->nested->value = 'modified';
            expect($original->nested->value)->toBe('original');
            expect($copy->nested->value)->toBe('modified');
        });

        it('creates a deep copy of an object with nested arrays', function(): void {
            $original = new class {
                /** @var array<string, array<string, mixed>> */
                public array $items = [
                    'user' => ['name' => 'Alice', 'age' => 30],
                    'settings' => ['theme' => 'dark'],
                ];
            };

            $copy = ObjectHelper::copy($original, true);

            // Deep copy: arrays have same content
            /** @var object{items: array<string, array<string, mixed>>} $copy */
            /** @var object{items: array<string, array<string, mixed>>} $original */
            /** @phpstan-ignore-next-line unknown */
            expect($copy->items)->toBe([
                'user' => ['name' => 'Alice', 'age' => 30],
                'settings' => ['theme' => 'dark'],
            ]);

            // Modifying array does NOT affect original (arrays are copied by value in PHP)
            /** @phpstan-ignore-next-line unknown */
            $copy->items['user']['name'] = 'Bob';
            expect($original->items['user']['name'])->toBe('Alice');
            expect($copy->items['user']['name'])->toBe('Bob');
        });

        it('creates a deep copy of an object with arrays containing objects', function(): void {
            $user1 = new stdClass();
            $user1->name = 'Alice';

            $user2 = new stdClass();
            $user2->name = 'Bob';

            $original = new class($user1, $user2) {
                public function __construct(
                    public object $user1,
                    public object $user2,
                ) {
                    /** @phpstan-ignore-next-line unknown */
                    $this->users = [$user1, $user2];
                }

                /** @var array<int, object{name: string}> */
                public array $users;
            };

            $copy = ObjectHelper::copy($original, true);

            // Deep copy: objects in arrays are copied
            /** @var object{users: array<int, object{name: string}>} $copy */
            /** @var object{users: array<int, object{name: string}>} $original */
            /** @phpstan-ignore-next-line unknown */
            expect($copy->users[0])->not->toBe($original->users[0]);
            expect($copy->users[1])->not->toBe($original->users[1]);
            expect($copy->users[0]->name)->toBe('Alice');
            expect($copy->users[1]->name)->toBe('Bob');

            // Modifying object in array does NOT affect original
            /** @phpstan-ignore-next-line unknown */
            $copy->users[0]->name = 'Charlie';
            expect($original->users[0]->name)->toBe('Alice');
            expect($copy->users[0]->name)->toBe('Charlie');
        });

        it('handles deeply nested structures', function(): void {
            $level3 = new stdClass();
            $level3->value = 'deep';

            $level2 = new stdClass();
            $level2->nested = $level3;

            $level1 = new stdClass();
            $level1->nested = $level2;

            $original = new class($level1) {
                public function __construct(public object $nested) {}
            };

            $copy = ObjectHelper::copy($original, true, 10);

            // All levels are copied (within maxLevel)
            /** @var object{nested: object{nested: object{nested: object{value: string}}}} $copy */
            /** @var object{nested: object{nested: object{nested: object{value: string}}}} $original */
            /** @phpstan-ignore-next-line unknown */
            expect($copy->nested)->not->toBe($original->nested);
            expect($copy->nested->nested)->not->toBe($original->nested->nested);
            expect($copy->nested->nested->nested)->not->toBe($original->nested->nested->nested);
            expect($copy->nested->nested->nested->value)->toBe('deep');

            // Modifying deep nested object does NOT affect original
            /** @phpstan-ignore-next-line unknown */
            $copy->nested->nested->nested->value = 'modified';
            expect($original->nested->nested->nested->value)->toBe('deep');
            expect($copy->nested->nested->nested->value)->toBe('modified');
        });

        it('respects maxLevel parameter', function(): void {
            $level3 = new stdClass();
            $level3->value = 'deep';

            $level2 = new stdClass();
            $level2->nested = $level3;

            $level1 = new stdClass();
            $level1->nested = $level2;

            $original = new class($level1) {
                public function __construct(public object $nested) {}
            };

            // Copy with maxLevel = 1 (only first level is copied)
            $copy = ObjectHelper::copy($original, true, 1);

            // Level 1 is copied
            /** @var object{nested: object{nested: object{nested: object{value: string}}}} $copy */
            /** @var object{nested: object{nested: object{nested: object{value: string}}}} $original */
            /** @phpstan-ignore-next-line unknown */
            expect($copy->nested)->not->toBe($original->nested);

            // Level 2 and 3 are shared (maxLevel reached)
            expect($copy->nested->nested)->toBe($original->nested->nested);
            expect($copy->nested->nested->nested)->toBe($original->nested->nested->nested);

            // Modifying level 2 affects both (shared reference)
            /** @phpstan-ignore-next-line unknown */
            $copy->nested->nested->value = 'modified';
            /** @phpstan-ignore-next-line unknown */
            expect($original->nested->nested->value)->toBe('modified');
        });
    });

    describe('copy() - Private and protected properties', function(): void {
        it('copies private properties', function(): void {
            $original = new class {
                private string $secret = 'hidden';

                public function getSecret(): string
                {
                    return $this->secret;
                }

                public function setSecret(string $value): void
                {
                    $this->secret = $value;
                }
            };

            $copy = ObjectHelper::copy($original, true);

            /** @var object{getSecret: callable(): string, setSecret: callable(string): void} $copy */
            /** @var object{getSecret: callable(): string} $original */
            /** @phpstan-ignore-next-line unknown */
            /** @phpstan-ignore-next-line unknown */
            expect($copy->getSecret())->toBe('hidden');

            // Modifying copy does NOT affect original
            /** @phpstan-ignore-next-line unknown */
            $copy->setSecret('modified');
            /** @phpstan-ignore-next-line unknown */
            expect($original->getSecret())->toBe('hidden');
            /** @phpstan-ignore-next-line unknown */
            expect($copy->getSecret())->toBe('modified');
        });

        it('copies protected properties', function(): void {
            $original = new class {
                protected string $protected = 'protected value';

                public function getProtected(): string
                {
                    return $this->protected;
                }

                public function setProtected(string $value): void
                {
                    $this->protected = $value;
                }
            };

            $copy = ObjectHelper::copy($original, true);

            /** @var object{getProtected: callable(): string, setProtected: callable(string): void} $copy */
            /** @var object{getProtected: callable(): string} $original */
            /** @phpstan-ignore-next-line unknown */
            /** @phpstan-ignore-next-line unknown */
            expect($copy->getProtected())->toBe('protected value');

            // Modifying copy does NOT affect original
            /** @phpstan-ignore-next-line unknown */
            $copy->setProtected('modified');
            /** @phpstan-ignore-next-line unknown */
            expect($original->getProtected())->toBe('protected value');
            /** @phpstan-ignore-next-line unknown */
            expect($copy->getProtected())->toBe('modified');
        });

        it('copies nested private objects', function(): void {
            $nested = new class {
                private string $value = 'nested private';

                public function getValue(): string
                {
                    return $this->value;
                }

                public function setValue(string $value): void
                {
                    $this->value = $value;
                }
            };

            $original = new class($nested) {
                public function __construct(private readonly object $nested) {}

                /** @return object{getValue: callable(): string, setValue: callable(string): void} */
                public function getNested(): object
                {
                    /** @phpstan-ignore-next-line unknown */
                    return $this->nested;
                }
            };

            $copy = ObjectHelper::copy($original, true);

            // Nested object is copied
            /** @var object{getNested: callable(): object{getValue: callable(): string, setValue: callable(string): void}} $copy */
            /** @var object{getNested: callable(): object{getValue: callable(): string}} $original */
            /** @phpstan-ignore-next-line unknown */
            /** @phpstan-ignore-next-line unknown */
            /** @phpstan-ignore-next-line unknown */
            expect($copy->getNested())->not->toBe($original->getNested());
            /** @phpstan-ignore-next-line unknown */
            expect($copy->getNested()->getValue())->toBe('nested private');

            // Modifying nested object does NOT affect original
            /** @phpstan-ignore-next-line unknown */
            $copy->getNested()->setValue('modified');
            /** @phpstan-ignore-next-line unknown */
            expect($original->getNested()->getValue())->toBe('nested private');
            /** @phpstan-ignore-next-line unknown */
            expect($copy->getNested()->getValue())->toBe('modified');
        });
    });

    describe('copy() - Edge cases', function(): void {
        it('handles uninitialized properties', function(): void {
            $original = new class {
                public string $initialized = 'value';
                public string $uninitialized;
            };

            $copy = ObjectHelper::copy($original, true);

            /** @var object{initialized: string} $copy */
            expect($copy->initialized)->toBe('value');
            expect(property_exists($copy, 'uninitialized'))->toBeTrue();
        });

        it('handles empty objects', function(): void {
            $original = new stdClass();

            $copy = ObjectHelper::copy($original, true);

            expect($copy)->not->toBe($original);
            expect($copy)->toBeInstanceOf(stdClass::class);
        });

        it('handles objects with only primitives', function(): void {
            $original = new class {
                public string $name = 'Alice';
                public int $age = 30;
                public float $height = 1.75;
                public bool $active = true;
                public ?string $nullable = null;
            };

            $copy = ObjectHelper::copy($original, true);

            /** @var object{name: string, age: int, height: float, active: bool, nullable: ?string} $copy */
            expect($copy)->not->toBe($original);
            expect($copy->name)->toBe('Alice');
            expect($copy->age)->toBe(30);
            expect($copy->height)->toBe(1.75);
            expect($copy->active)->toBeTrue();
            expect($copy->nullable)->toBeNull();
        });
    });
});

