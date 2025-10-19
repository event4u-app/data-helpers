# Validation

Learn how to validate DTOs using automatic rule inferring and validation attributes.

---

## 🎯 What is Validation?

Validation ensures that data meets specific requirements before being processed. SimpleDTO provides:

- ✅ **Automatic rule inferring** from types and attributes
- ✅ **30+ validation attributes**
- ✅ **Framework integration** (Laravel, Symfony)
- ✅ **Custom validation rules**
- ✅ **Validation caching** (198x faster)

---

## 🚀 Quick Start

### Basic Validation

```php
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Between;

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $name,
        
        #[Required, Email]
        public readonly string $email,
        
        #[Required, Between(18, 120)]
        public readonly int $age,
    ) {}
}

// Validate and create
$dto = UserDTO::validateAndCreate([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);
```

### Handling Validation Errors

```php
use event4u\DataHelpers\SimpleDTO\Exceptions\ValidationException;

try {
    $dto = UserDTO::validateAndCreate([
        'name' => '',  // ❌ Required
        'email' => 'invalid',  // ❌ Invalid email
        'age' => 15,  // ❌ Too young
    ]);
} catch (ValidationException $e) {
    echo $e->getMessage();
    print_r($e->errors());
}
```

---

## 📋 Validation Attributes

### Required Validation

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Required;

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $name,
        
        #[Required]
        public readonly string $email,
        
        // Optional - no Required attribute
        public readonly ?string $phone = null,
    ) {}
}
```

### String Validation

```php
use event4u\DataHelpers\SimpleDTO\Attributes\StringType;
use event4u\DataHelpers\SimpleDTO\Attributes\Min;
use event4u\DataHelpers\SimpleDTO\Attributes\Max;
use event4u\DataHelpers\SimpleDTO\Attributes\Between;

class PostDTO extends SimpleDTO
{
    public function __construct(
        #[Required, StringType, Min(3)]
        public readonly string $title,
        
        #[Required, StringType, Between(10, 1000)]
        public readonly string $content,
        
        #[StringType, Max(100)]
        public readonly ?string $excerpt = null,
    ) {}
}
```

### Numeric Validation

```php
use event4u\DataHelpers\SimpleDTO\Attributes\IntegerType;
use event4u\DataHelpers\SimpleDTO\Attributes\Numeric;
use event4u\DataHelpers\SimpleDTO\Attributes\Min;
use event4u\DataHelpers\SimpleDTO\Attributes\Max;

class ProductDTO extends SimpleDTO
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public readonly int $quantity,
        
        #[Required, Numeric, Min(0)]
        public readonly float $price,
        
        #[IntegerType, Between(0, 100)]
        public readonly ?int $discount = null,
    ) {}
}
```

### Email Validation

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Email;

class ContactDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
        
        #[Email]
        public readonly ?string $alternativeEmail = null,
    ) {}
}
```

### URL Validation

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Url;

class WebsiteDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Url]
        public readonly string $website,
        
        #[Url]
        public readonly ?string $blog = null,
    ) {}
}
```

### Date Validation

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Date;
use event4u\DataHelpers\SimpleDTO\Attributes\After;
use event4u\DataHelpers\SimpleDTO\Attributes\Before;

class EventDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Date]
        public readonly string $startDate,
        
        #[Required, Date, After('startDate')]
        public readonly string $endDate,
        
        #[Date, Before('today')]
        public readonly ?string $registrationDeadline = null,
    ) {}
}
```

### Array Validation

```php
use event4u\DataHelpers\SimpleDTO\Attributes\ArrayType;
use event4u\DataHelpers\SimpleDTO\Attributes\In;

class FilterDTO extends SimpleDTO
{
    public function __construct(
        #[Required, ArrayType]
        public readonly array $tags,
        
        #[Required, In(['active', 'inactive', 'pending'])]
        public readonly string $status,
    ) {}
}
```

### Boolean Validation

```php
use event4u\DataHelpers\SimpleDTO\Attributes\BooleanType;

class SettingsDTO extends SimpleDTO
{
    public function __construct(
        #[Required, BooleanType]
        public readonly bool $emailNotifications,
        
        #[BooleanType]
        public readonly bool $smsNotifications = false,
    ) {}
}
```

---

## 🔐 Database Validation

### Unique Validation

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Unique;

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Email, Unique('users', 'email')]
        public readonly string $email,
        
        #[Required, Unique('users', 'username')]
        public readonly string $username,
    ) {}
}
```

### Exists Validation

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Exists;

class PostDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Exists('users', 'id')]
        public readonly int $userId,
        
        #[Required, Exists('categories', 'id')]
        public readonly int $categoryId,
    ) {}
}
```

---

## 🎨 Pattern Validation

### Regex Validation

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Regex;

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Regex('/^[a-zA-Z0-9_]+$/')]
        public readonly string $username,
        
        #[Regex('/^\+?[1-9]\d{1,14}$/')]
        public readonly ?string $phone = null,
    ) {}
}
```

### Alpha Validation

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Alpha;
use event4u\DataHelpers\SimpleDTO\Attributes\AlphaNum;

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Alpha]
        public readonly string $firstName,
        
        #[Required, AlphaNum]
        public readonly string $username,
    ) {}
}
```

---

## 🔒 Password Validation

### Password Validation

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Min;
use event4u\DataHelpers\SimpleDTO\Attributes\Confirmed;

class RegisterDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
        
        #[Required, Min(8), Confirmed]
        public readonly string $password,
        
        #[Required]
        public readonly string $passwordConfirmation,
    ) {}
}
```

---

## 📊 File Validation

### File Validation

```php
use event4u\DataHelpers\SimpleDTO\Attributes\File;
use event4u\DataHelpers\SimpleDTO\Attributes\Image;
use event4u\DataHelpers\SimpleDTO\Attributes\Mimes;
use event4u\DataHelpers\SimpleDTO\Attributes\MaxFileSize;

class UploadDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Image, MaxFileSize(2048)]
        public readonly $avatar,
        
        #[Required, File, Mimes(['pdf', 'doc', 'docx'])]
        public readonly $document,
    ) {}
}
```

---

## 🎯 Custom Validation

### Custom Validation Rules

```php
use event4u\DataHelpers\SimpleDTO\Attributes\CustomRule;

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required, CustomRule('strong_password')]
        public readonly string $password,
    ) {}
}

// Register custom rule
Validator::extend('strong_password', function ($attribute, $value, $parameters, $validator) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $value);
});
```

### Custom Validation Method

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
        
        #[Required]
        public readonly string $password,
    ) {}
    
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:8', function ($attribute, $value, $fail) {
                if (!preg_match('/[A-Z]/', $value)) {
                    $fail('Password must contain at least one uppercase letter.');
                }
            }],
        ];
    }
}
```

---

## 🚀 Automatic Rule Inferring

SimpleDTO automatically infers validation rules from types and attributes:

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,      // → 'string'
        public readonly int $age,          // → 'integer'
        public readonly ?string $phone,    // → 'nullable|string'
        
        #[Email]
        public readonly string $email,     // → 'string|email'
        
        #[Between(18, 120)]
        public readonly int $age,          // → 'integer|between:18,120'
    ) {}
}

// Get inferred rules
$rules = UserDTO::getValidationRules();
print_r($rules);
```

---

## ⚡ Validation Caching

Validation rules are cached for better performance:

```php
// First call: generates and caches rules
$dto = UserDTO::validateAndCreate($data);

// Subsequent calls: uses cached rules (198x faster!)
$dto = UserDTO::validateAndCreate($data);
```

### Clear Validation Cache

```php
// Clear cache for specific DTO
UserDTO::clearValidationCache();

// Clear all validation caches
SimpleDTO::clearAllValidationCaches();
```

---

## 🎯 Validation in Controllers

### Laravel Controller

```php
class UserController extends Controller
{
    public function store(Request $request)
    {
        try {
            $dto = UserDTO::validateAndCreate($request->all());
            $user = User::create($dto->toArray());
            return response()->json($user, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }
}
```

### Symfony Controller

```php
class UserController
{
    public function store(Request $request): JsonResponse
    {
        try {
            $dto = UserDTO::validateAndCreate($request->request->all());
            // Process DTO
            return new JsonResponse($dto->toArray(), 201);
        } catch (ValidationException $e) {
            return new JsonResponse([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }
}
```

---

## 💡 Best Practices

### 1. Always Validate User Input

```php
// ✅ Good - validate user input
$dto = UserDTO::validateAndCreate($request->all());

// ❌ Bad - no validation
$dto = UserDTO::fromArray($request->all());
```

### 2. Use Specific Validation Attributes

```php
// ✅ Good - specific validation
#[Required, Email, Unique('users', 'email')]
public readonly string $email;

// ❌ Bad - generic validation
#[Required]
public readonly string $email;
```

### 3. Handle Validation Errors Gracefully

```php
// ✅ Good - proper error handling
try {
    $dto = UserDTO::validateAndCreate($data);
} catch (ValidationException $e) {
    return response()->json(['errors' => $e->errors()], 422);
}
```

---

## 📚 Next Steps

1. [Property Mapping](08-property-mapping.md) - Map property names
2. [Custom Validation](21-custom-validation.md) - Create custom rules
3. [Validation Attributes](20-validation-attributes.md) - All attributes
4. [Security & Visibility](22-security-visibility.md) - Hidden properties

---

**Previous:** [Type Casting](06-type-casting.md)  
**Next:** [Property Mapping](08-property-mapping.md)

