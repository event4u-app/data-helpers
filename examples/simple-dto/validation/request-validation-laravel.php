<?php

declare(strict_types=1);

/**
 * Phase 15.2: Laravel Request Validation Integration
 *
 * This example demonstrates Laravel-specific features:
 * - DtoFormRequest (similar to Laravel's FormRequest)
 * - Controller injection with automatic validation
 * - Integration with Laravel's Validator
 *
 * Note: This example shows the API, but requires a Laravel application to run.
 */

require __DIR__ . '/../../bootstrap.php';
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Between;
use event4u\DataHelpers\SimpleDto\Attributes\Email;
use event4u\DataHelpers\SimpleDto\Attributes\Min;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Attributes\ValidateRequest;

echo "=== Phase 15.2: Laravel Request Validation Integration ===\n\n";

// Example 1: Dto with ValidateRequest Attribute
echo "1. Dto with ValidateRequest Attribute\n";
echo str_repeat('-', 60) . "\n";

#[ValidateRequest(throw: true)]
class CreateUserDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(3)]
        public readonly string $name,

        #[Between(18, 120)]
        public readonly int $age,
    ) {}
}

echo "✅  CreateUserDto defined with ValidateRequest attribute\n";
echo "    - Automatic validation in controllers\n";
echo "    - Throws ValidationException on failure\n";
echo "\n";

// Example 2: Controller Method with Dto Injection
echo "2. Controller Method with Dto Injection\n";
echo str_repeat('-', 60) . "\n";

echo "```php\n";
echo "class UserController extends Controller\n";
echo "{\n";
echo "    public function store(CreateUserDto \$dto)\n";
echo "    {\n";
echo "        // \$dto is automatically validated!\n";
echo "        \$user = User::create(\$dto->toArray());\n";
echo "        return response()->json(\$user);\n";
echo "    }\n";
echo "}\n";
echo "```\n";
echo "\n";
echo "✅  Dto is automatically:\n";
echo "    - Created from request data\n";
echo "    - Validated using defined rules\n";
echo "    - Injected into controller method\n";
echo "\n";

// Example 3: DtoFormRequest (Laravel FormRequest Style)
echo "3. DtoFormRequest (Laravel FormRequest Style)\n";
echo str_repeat('-', 60) . "\n";

echo "```php\n";
echo "class StoreUserRequest extends DtoFormRequest\n";
echo "{\n";
echo "    protected string \$dtoClass = CreateUserDto::class;\n";
echo "\n";
echo "    public function authorize(): bool\n";
echo "    {\n";
echo "        return \$this->user()->can('create-users');\n";
echo "    }\n";
echo "}\n";
echo "\n";
echo "// In controller\n";
echo "public function store(StoreUserRequest \$request)\n";
echo "{\n";
echo "    \$dto = \$request->toDto();\n";
echo "    \$user = User::create(\$dto->toArray());\n";
echo "    return response()->json(\$user);\n";
echo "}\n";
echo "```\n";
echo "\n";
echo "✅  DtoFormRequest provides:\n";
echo "    - Authorization logic (authorize method)\n";
echo "    - Automatic validation from Dto rules\n";
echo "    - toDto() method for easy conversion\n";
echo "\n";

// Example 4: Update Dto with Partial Validation
echo "4. Update Dto with Partial Validation\n";
echo str_repeat('-', 60) . "\n";

#[ValidateRequest(throw: true, except: ['email'])]
class UpdateUserDto extends SimpleDto
{
    public function __construct(
        #[Email]
        public readonly ?string $email = null,

        #[Min(3)]
        public readonly ?string $name = null,

        #[Between(18, 120)]
        public readonly ?int $age = null,
    ) {}
}

echo "✅  UpdateUserDto defined for PATCH requests\n";
echo "    - All fields are optional\n";
echo "    - Email validation is excluded\n";
echo "    - Only provided fields are validated\n";
echo "\n";

echo "```php\n";
echo "public function update(int \$id, UpdateUserDto \$dto)\n";
echo "{\n";
echo "    \$user = User::findOrFail(\$id);\n";
echo "    \$user->update(\$dto->partial());\n";
echo "    return response()->json(\$user);\n";
echo "}\n";
echo "```\n";
echo "\n";

// Example 5: API Resource Controller
echo "5. API Resource Controller\n";
echo str_repeat('-', 60) . "\n";

echo "```php\n";
echo "class UserController extends Controller\n";
echo "{\n";
echo "    public function index()\n";
echo "    {\n";
echo "        return UserDto::collection(User::all());\n";
echo "    }\n";
echo "\n";
echo "    public function store(CreateUserDto \$dto)\n";
echo "    {\n";
echo "        \$user = User::create(\$dto->toArray());\n";
echo "        return new UserResource(\$user);\n";
echo "    }\n";
echo "\n";
echo "    public function show(int \$id)\n";
echo "    {\n";
echo "        \$user = User::findOrFail(\$id);\n";
echo "        return UserDto::from(\$user);\n";
echo "    }\n";
echo "\n";
echo "    public function update(int \$id, UpdateUserDto \$dto)\n";
echo "    {\n";
echo "        \$user = User::findOrFail(\$id);\n";
echo "        \$user->update(\$dto->partial());\n";
echo "        return UserDto::from(\$user);\n";
echo "    }\n";
echo "\n";
echo "    public function destroy(int \$id)\n";
echo "    {\n";
echo "        User::findOrFail(\$id)->delete();\n";
echo "        return response()->noContent();\n";
echo "    }\n";
echo "}\n";
echo "```\n";
echo "\n";
echo "✅  Complete CRUD API with automatic validation\n";
echo "\n";

// Example 6: Custom Validation Messages
echo "6. Custom Validation Messages\n";
echo str_repeat('-', 60) . "\n";

class RegisterUserDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(8)]
        public readonly string $password,

        #[Required]
        public readonly string $password_confirmation,
    ) {}

    protected function messages(): array
    {
        return [
            'email.required' => 'We need your email address',
            'email.email' => 'Please provide a valid email',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
        ];
    }

    protected function attributes(): array
    {
        return [
            'email' => 'email address',
            'password' => 'password',
        ];
    }
}

echo "✅  RegisterUserDto with custom messages\n";
echo "    - User-friendly error messages\n";
echo "    - Custom attribute names\n";
echo "    - Localization support\n";
echo "\n";

// Example 7: Service Provider Registration
echo "7. Service Provider Registration\n";
echo str_repeat('-', 60) . "\n";

echo "Add to config/app.php:\n";
echo "```php\n";
echo "'providers' => [\n";
echo "    // ...\n";
echo "    event4u\\DataHelpers\\Laravel\\DtoServiceProvider::class,\n";
echo "],\n";
echo "```\n";
echo "\n";
echo "✅  Enables automatic Dto injection in controllers\n";
echo "\n";

// Example 8: Error Handling
echo "8. Error Handling\n";
echo str_repeat('-', 60) . "\n";

echo "```php\n";
echo "// In app/Exceptions/Handler.php\n";
echo "use event4u\\DataHelpers\\Validation\\ValidationException;\n";
echo "\n";
echo "public function register()\n";
echo "{\n";
echo "    \$this->renderable(function (ValidationException \$e, \$request) {\n";
echo "        if (\$request->expectsJson()) {\n";
echo "            return response()->json([\n";
echo "                'message' => \$e->getMessage(),\n";
echo "                'errors' => \$e->errors(),\n";
echo "            ], 422);\n";
echo "        }\n";
echo "\n";
echo "        return back()->withErrors(\$e->errors())->withInput();\n";
echo "    });\n";
echo "}\n";
echo "```\n";
echo "\n";
echo "✅  Automatic error handling for:\n";
echo "    - JSON API responses\n";
echo "    - Form validation errors\n";
echo "    - Redirect with errors\n";
echo "\n";

echo "=== Laravel Integration Complete! ===\n";
echo "\n";
echo "Key Features:\n";
echo "  ✅  Automatic controller injection\n";
echo "  ✅  DtoFormRequest (like Laravel FormRequest)\n";
echo "  ✅  Authorization support\n";
echo "  ✅  Custom validation messages\n";
echo "  ✅  Partial updates (PATCH)\n";
echo "  ✅  JSON API support\n";
echo "  ✅  Error handling\n";
