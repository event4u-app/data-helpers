<?php

declare(strict_types=1);

namespace Tests\Integration\Laravel;

// Skip this file entirely if Laravel is not installed
if (!class_exists('Illuminate\Foundation\Http\FormRequest')) {
    return;
}

use event4u\DataHelpers\Frameworks\Laravel\DTOFormRequest;
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Min;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use Illuminate\Container\Container;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidationFactory;

// Test DTO
class UserFormDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Min(2)]
        public readonly string $name,
        #[Required]
        #[Email]
        public readonly string $email,
        public readonly ?int $age = null,
    ) {}
}

// Test FormRequest
class UserFormRequest extends DTOFormRequest
{
    protected string $dtoClass = UserFormDTO::class;

    public function authorize(): bool
    {
        return true;
    }
}

describe('Laravel DTOFormRequest', function(): void {
    beforeEach(function(): void {
        // Skip if Laravel is not available
        if (!class_exists('Illuminate\Foundation\Http\FormRequest')) {
            $this->markTestSkipped('Laravel is not available');
        }

        // Create validation factory
        $translator = new Translator(new ArrayLoader(), 'en');
        $this->validationFactory = new ValidationFactory($translator);

        // Create container
        $this->container = new Container();
        $this->container->instance('validator', $this->validationFactory);
        Container::setInstance($this->container);
    });

    afterEach(function(): void {
        Container::setInstance(null);
    });

    test('it creates dto from valid data', function(): void {
        /** @phpstan-ignore-next-line unknown */
        $request = UserFormRequest::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        // Manually set validator
        $validator = $this->validationFactory->make(
            $request->all(),
            $request->rules()
        );
        $request->setValidator($validator);

        $dto = $request->toDTO();

        expect($dto)->toBeInstanceOf(UserFormDTO::class)
            ->and($dto->name)->toBe('John Doe')
            ->and($dto->email)->toBe('john@example.com')
            ->and($dto->age)->toBe(30);
    });

    test('it generates rules from dto', function(): void {
        $request = new UserFormRequest();

        $rules = $request->rules();

        expect($rules)->toBeArray()
            ->and($rules)->toHaveKey('name')
            ->and($rules)->toHaveKey('email')
            ->and($rules['name'])->toContain('required')
            ->and($rules['email'])->toContain('required')
            ->and($rules['email'])->toContain('email');
    });

    test('it handles optional fields', function(): void {
        /** @phpstan-ignore-next-line unknown */
        $request = UserFormRequest::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Manually set validator
        $validator = $this->validationFactory->make(
            $request->all(),
            $request->rules()
        );
        $request->setValidator($validator);

        $dto = $request->toDTO();

        expect($dto)->toBeInstanceOf(UserFormDTO::class)
            ->and($dto->age)->toBeNull();
    });

    test('it validates data automatically', function(): void {
        /** @phpstan-ignore-next-line unknown */
        $request = UserFormRequest::create('/test', 'POST', [
            'name' => 'J',  // Too short
            'email' => 'invalid-email',
        ]);

        // Set validator
        $request->setContainer($this->container);

        $validator = $this->validationFactory->make(
            $request->all(),
            $request->rules()
        );
        $request->setValidator($validator);

        expect($validator->passes())->toBeFalse();
    });

    test('it provides custom messages', function(): void {
        $request = new class extends DTOFormRequest {
            protected string $dtoClass = UserFormDTO::class;

            public function authorize(): bool
            {
                return true;
            }

            public function messages(): array
            {
                return [
                    'name.required' => 'Please provide your name',
                    'email.email' => 'Please provide a valid email address',
                ];
            }
        };

        $messages = $request->messages();

        expect($messages)->toHaveKey('name.required')
            ->and($messages)->toHaveKey('email.email')
            ->and($messages['name.required'])->toBe('Please provide your name');
    });

    test('it provides custom attributes', function(): void {
        $request = new class extends DTOFormRequest {
            protected string $dtoClass = UserFormDTO::class;

            public function authorize(): bool
            {
                return true;
            }

            public function attributes(): array
            {
                return [
                    'name' => 'full name',
                    'email' => 'email address',
                ];
            }
        };

        $attributes = $request->attributes();

        expect($attributes)->toHaveKey('name')
            ->and($attributes)->toHaveKey('email')
            ->and($attributes['name'])->toBe('full name');
    });

    test('it handles json request', function(): void {
        /** @phpstan-ignore-next-line unknown */
        $request = UserFormRequest::create(
            '/test',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
            ])
        );

        // Manually set validator
        $validator = $this->validationFactory->make(
            $request->all(),
            $request->rules()
        );
        $request->setValidator($validator);

        $dto = $request->toDTO();

        expect($dto)->toBeInstanceOf(UserFormDTO::class)
            ->and($dto->name)->toBe('Jane Doe')
            ->and($dto->email)->toBe('jane@example.com');
    });

    test('it can be extended with additional rules', function(): void {
        $request = new class extends DTOFormRequest {
            protected string $dtoClass = UserFormDTO::class;

            public function authorize(): bool
            {
                return true;
            }

            public function rules(): array
            {
                return array_merge(parent::rules(), [
                    'age' => ['integer', 'min:18', 'max:120'],
                ]);
            }
        };

        $rules = $request->rules();

        expect($rules)->toHaveKey('age')
            ->and($rules['age'])->toContain('integer')
            ->and($rules['age'])->toContain('min:18');
    });

    test('it handles authorization', function(): void {
        $request = new class extends DTOFormRequest {
            protected string $dtoClass = UserFormDTO::class;

            public function authorize(): bool
            {
                return false;
            }
        };

        expect($request->authorize())->toBeFalse();
    });

    test('it converts dto back to array', function(): void {
        /** @phpstan-ignore-next-line unknown */
        $request = UserFormRequest::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        // Manually set validator
        $validator = $this->validationFactory->make(
            $request->all(),
            $request->rules()
        );
        $request->setValidator($validator);

        $dto = $request->toDTO();
        $array = $dto->toArray();

        expect($array)->toBeArray()
            ->and($array)->toHaveKey('name')
            ->and($array)->toHaveKey('email')
            ->and($array)->toHaveKey('age')
            ->and($array['name'])->toBe('John Doe');
    });
})->group('laravel');;

