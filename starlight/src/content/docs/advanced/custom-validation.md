---
title: Custom Validation
description: Create custom validation rules and attributes
---

Create custom validation rules and attributes.

## Introduction

Custom validation allows you to create reusable validation logic:

- ✅ **Custom Attributes** - Reusable validation attributes
- ✅ **Custom Rules** - Implement ValidationRuleInterface
- ✅ **Inline Validation** - Use closures
- ✅ **Framework Integration** - Use Laravel/Symfony validators

## Creating Custom Attributes

### Basic Custom Attribute

```php
use event4u\DataHelpers\SimpleDto\Attributes\ValidationAttribute;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class StrongPassword extends ValidationAttribute
{
    public function rules(): array
    {
        return [
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
        ];
    }

    public function message(): string
    {
        return 'Password must be at least 8 characters with uppercase, lowercase, number, and special character';
    }
}
```

### Using the Attribute

```php
class UserDto extends SimpleDto
{
    public function __construct(
        #[Required, StrongPassword]
        public readonly string $password,
    ) {}
}
```

## Advanced Examples

### Attribute with Parameters

<!-- skip-test: Class definition example -->
```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class MinWords extends ValidationAttribute
{
    public function __construct(
        private int $min,
    ) {}

    public function rules(): array
    {
        return [
            function ($attribute, $value, $fail) {
                $wordCount = str_word_count($value);

                if ($wordCount < $this->min) {
                    $fail("The {$attribute} must contain at least {$this->min} words.");
                }
            },
        ];
    }
}

// Usage
#[MinWords(10)]
public readonly string $description;
```

### Multiple Rules

<!-- skip-test: Class definition example -->
```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class Username extends ValidationAttribute
{
    public function rules(): array
    {
        return [
            'required',
            'string',
            'min:3',
            'max:20',
            'regex:/^[a-zA-Z0-9_]+$/',
            'unique:users,username',
        ];
    }

    public function messages(): array
    {
        return [
            'regex' => 'Username can only contain letters, numbers, and underscores',
            'unique' => 'This username is already taken',
        ];
    }
}
```

### Conditional Validation

<!-- skip-test: Class definition example -->
```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class RequiredIf extends ValidationAttribute
{
    public function __construct(
        private string $field,
        private mixed $value,
    ) {}

    public function rules(): array
    {
        return [
            "required_if:{$this->field},{$this->value}",
        ];
    }
}

// Usage
class OrderDto extends SimpleDto
{
    public function __construct(
        public readonly string $paymentMethod,

        #[RequiredIf('paymentMethod', 'credit_card')]
        public readonly ?string $cardNumber = null,
    ) {}
}
```

## Custom Validation Rules

### Implementing ValidationRuleInterface

<!-- skip-test: Class definition example -->
```php
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

class UniqueEmailRule implements ValidationRule
{
    public function passes(string $attribute, mixed $value): bool
    {
        return !User::where('email', $value)->exists();
    }

    public function message(): string
    {
        return 'This email is already registered';
    }
}
```

### Using Custom Rules

<!-- skip-test: Class definition example -->
```php
class UserDto extends SimpleDto
{
    public function __construct(
        #[CustomRule(UniqueEmailRule::class)]
        public readonly string $email,
    ) {}
}
```

## Inline Validation

### Using Closures

<!-- skip-test: Class definition example -->
```php
class ProductDto extends SimpleDto
{
    public function __construct(
        #[CustomRule(function ($attribute, $value, $fail) {
            if ($value < 0) {
                $fail('Price cannot be negative');
            }
        })]
        public readonly float $price,
    ) {}
}
```

## Real-World Examples

### Credit Card Validation

<!-- skip-test: Class definition example -->
```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class CreditCard extends ValidationAttribute
{
    public function rules(): array
    {
        return [
            function ($attribute, $value, $fail) {
                // Luhn algorithm
                $value = preg_replace('/\D/', '', $value);
                $sum = 0;
                $alt = false;

                for ($i = strlen($value) - 1; $i >= 0; $i--) {
                    $n = (int) $value[$i];

                    if ($alt) {
                        $n *= 2;
                        if ($n > 9) {
                            $n -= 9;
                        }
                    }

                    $sum += $n;
                    $alt = !$alt;
                }

                if ($sum % 10 !== 0) {
                    $fail('Invalid credit card number');
                }
            },
        ];
    }
}
```

### IBAN Validation

<!-- skip-test: Class definition example -->
```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class IBAN extends ValidationAttribute
{
    public function rules(): array
    {
        return [
            'regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/',
            function ($attribute, $value, $fail) {
                // IBAN checksum validation
                $iban = str_replace(' ', '', strtoupper($value));
                $checksum = substr($iban, 0, 4);
                $account = substr($iban, 4);

                $numeric = $account . $checksum;
                $numeric = str_replace(
                    range('A', 'Z'),
                    range(10, 35),
                    $numeric
                );

                if (bcmod($numeric, '97') !== '1') {
                    $fail('Invalid IBAN');
                }
            },
        ];
    }
}
```

### Date Range Validation

<!-- skip-test: Class definition example -->
```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class DateRange extends ValidationAttribute
{
    public function __construct(
        private ?string $after = null,
        private ?string $before = null,
    ) {}

    public function rules(): array
    {
        $rules = ['date'];

        if ($this->after) {
            $rules[] = "after:{$this->after}";
        }

        if ($this->before) {
            $rules[] = "before:{$this->before}";
        }

        return $rules;
    }
}

// Usage
#[DateRange(after: 'today', before: '+1 year')]
public readonly Carbon $eventDate;
```

## Framework Integration

### Laravel Validation

<!-- skip-test: Class definition example -->
```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class LaravelRule extends ValidationAttribute
{
    public function __construct(
        private string $rule,
    ) {}

    public function rules(): array
    {
        return [$this->rule];
    }
}

// Usage
#[LaravelRule('exists:users,id')]
public readonly int $userId;
```

### Symfony Validation

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Attribute(Attribute::TARGET_PROPERTY)]
class SymfonyConstraint extends ValidationAttribute
{
    public function __construct(
        private Assert\Constraint $constraint,
    ) {}

    public function getConstraint(): Assert\Constraint
    {
        return $this->constraint;
    }
}

// Usage
#[SymfonyConstraint(new Assert\Url())]
public readonly string $website;
```

## Best Practices

### Clear Error Messages

```php
// ✅ Good - clear message
public function message(): string
{
    return 'Password must be at least 8 characters with uppercase, lowercase, number, and special character';
}

// ❌ Bad - vague message
public function message(): string
{
    return 'Invalid password';
}
```

### Reusable Attributes

```php
// ✅ Good - reusable
#[Attribute(Attribute::TARGET_PROPERTY)]
class MinWords extends ValidationAttribute

// ❌ Bad - specific
#[Attribute(Attribute::TARGET_PROPERTY)]
class ProductDescriptionMinWords extends ValidationAttribute
```

## See Also

- [Validation](/simple-dto/validation/) - Built-in validation
- [Custom Casts](/advanced/custom-casts/) - Custom type casts
- [Custom Attributes](/advanced/custom-attributes/) - Custom attributes

