# Custom Validation

Learn how to create custom validation rules and attributes for SimpleDTO.

---

## 🎯 Overview

SimpleDTO allows you to create custom validation rules in multiple ways:

- ✅ **Custom Attributes** - Create reusable validation attributes
- ✅ **Custom Rules** - Implement ValidationRuleInterface
- ✅ **Inline Validation** - Use closures for one-off validation
- ✅ **Framework Integration** - Use Laravel/Symfony validators
- ✅ **Conditional Validation** - Validate based on conditions

---

## 🚀 Creating Custom Attributes

### Basic Custom Attribute

```php
use event4u\DataHelpers\SimpleDTO\Attributes\ValidationAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Username extends ValidationAttribute
{
    public function rules(): array
    {
        return [
            'regex:/^[a-zA-Z0-9_]+$/',
            'min:3',
            'max:20',
        ];
    }
    
    public function message(): string
    {
        return 'The :attribute must be a valid username (3-20 alphanumeric characters or underscores).';
    }
}

// Usage
class CreateUserDTO extends SimpleDTO
{
    public function __construct(
        #[Username]
        public readonly string $username,
    ) {}
}
```

### Parameterized Custom Attribute

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class StrongPassword extends ValidationAttribute
{
    public function __construct(
        private int $minLength = 8,
        private bool $requireUppercase = true,
        private bool $requireLowercase = true,
        private bool $requireNumbers = true,
        private bool $requireSpecialChars = false,
    ) {}
    
    public function rules(): array
    {
        $rules = ["min:{$this->minLength}"];
        
        $pattern = '^';
        if ($this->requireUppercase) {
            $pattern .= '(?=.*[A-Z])';
        }
        if ($this->requireLowercase) {
            $pattern .= '(?=.*[a-z])';
        }
        if ($this->requireNumbers) {
            $pattern .= '(?=.*\d)';
        }
        if ($this->requireSpecialChars) {
            $pattern .= '(?=.*[@$!%*?&])';
        }
        $pattern .= '.+$';
        
        $rules[] = "regex:/{$pattern}/";
        
        return $rules;
    }
    
    public function message(): string
    {
        $requirements = [];
        if ($this->requireUppercase) $requirements[] = 'uppercase letter';
        if ($this->requireLowercase) $requirements[] = 'lowercase letter';
        if ($this->requireNumbers) $requirements[] = 'number';
        if ($this->requireSpecialChars) $requirements[] = 'special character';
        
        return sprintf(
            'The :attribute must be at least %d characters and contain: %s.',
            $this->minLength,
            implode(', ', $requirements)
        );
    }
}

// Usage
class CreateUserDTO extends SimpleDTO
{
    public function __construct(
        #[StrongPassword(minLength: 12, requireSpecialChars: true)]
        public readonly string $password,
    ) {}
}
```

---

## 🔧 Custom Validation Rules

### Implementing ValidationRuleInterface

```php
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRuleInterface;

class PhoneNumberRule implements ValidationRuleInterface
{
    public function __construct(
        private string $country = 'US'
    ) {}
    
    public function passes(string $attribute, mixed $value): bool
    {
        return match($this->country) {
            'US' => preg_match('/^\+1\d{10}$/', $value),
            'DE' => preg_match('/^\+49\d{10,11}$/', $value),
            'UK' => preg_match('/^\+44\d{10}$/', $value),
            default => false,
        };
    }
    
    public function message(): string
    {
        return "The :attribute must be a valid {$this->country} phone number.";
    }
}

// Create attribute
#[Attribute(Attribute::TARGET_PROPERTY)]
class PhoneNumber extends ValidationAttribute
{
    public function __construct(
        private string $country = 'US'
    ) {}
    
    public function rules(): array
    {
        return [new PhoneNumberRule($this->country)];
    }
}

// Usage
class ContactDTO extends SimpleDTO
{
    public function __construct(
        #[PhoneNumber('DE')]
        public readonly string $phone,
    ) {}
}
```

---

## 🎯 Real-World Examples

### Example 1: Credit Card Validation

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class CreditCard extends ValidationAttribute
{
    public function __construct(
        private array $types = ['visa', 'mastercard', 'amex']
    ) {}
    
    public function rules(): array
    {
        return [
            function ($attribute, $value, $fail) {
                // Remove spaces and dashes
                $number = preg_replace('/[\s-]/', '', $value);
                
                // Check if numeric
                if (!ctype_digit($number)) {
                    $fail("The {$attribute} must contain only numbers.");
                    return;
                }
                
                // Luhn algorithm
                if (!$this->luhnCheck($number)) {
                    $fail("The {$attribute} is not a valid credit card number.");
                    return;
                }
                
                // Check card type
                if (!$this->isValidType($number)) {
                    $fail("The {$attribute} must be one of: " . implode(', ', $this->types));
                }
            }
        ];
    }
    
    private function luhnCheck(string $number): bool
    {
        $sum = 0;
        $numDigits = strlen($number);
        $parity = $numDigits % 2;
        
        for ($i = 0; $i < $numDigits; $i++) {
            $digit = (int) $number[$i];
            
            if ($i % 2 == $parity) {
                $digit *= 2;
            }
            
            if ($digit > 9) {
                $digit -= 9;
            }
            
            $sum += $digit;
        }
        
        return $sum % 10 == 0;
    }
    
    private function isValidType(string $number): bool
    {
        $patterns = [
            'visa' => '/^4/',
            'mastercard' => '/^5[1-5]/',
            'amex' => '/^3[47]/',
        ];
        
        foreach ($this->types as $type) {
            if (isset($patterns[$type]) && preg_match($patterns[$type], $number)) {
                return true;
            }
        }
        
        return false;
    }
}

// Usage
class PaymentDTO extends SimpleDTO
{
    public function __construct(
        #[CreditCard(['visa', 'mastercard'])]
        public readonly string $cardNumber,
    ) {}
}
```

### Example 2: Business Hours Validation

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class BusinessHours extends ValidationAttribute
{
    public function __construct(
        private string $startTime = '09:00',
        private string $endTime = '17:00',
        private array $workDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']
    ) {}
    
    public function rules(): array
    {
        return [
            function ($attribute, $value, $fail) {
                $dateTime = new \DateTime($value);
                $dayOfWeek = $dateTime->format('l');
                $time = $dateTime->format('H:i');
                
                // Check if it's a work day
                if (!in_array($dayOfWeek, $this->workDays)) {
                    $fail("The {$attribute} must be on a work day (" . implode(', ', $this->workDays) . ").");
                    return;
                }
                
                // Check if it's within business hours
                if ($time < $this->startTime || $time > $this->endTime) {
                    $fail("The {$attribute} must be between {$this->startTime} and {$this->endTime}.");
                }
            }
        ];
    }
}

// Usage
class AppointmentDTO extends SimpleDTO
{
    public function __construct(
        #[BusinessHours(startTime: '08:00', endTime: '18:00')]
        public readonly string $scheduledAt,
    ) {}
}
```

### Example 3: Conditional Validation

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class RequiredIf extends ValidationAttribute
{
    public function __construct(
        private string $field,
        private mixed $value
    ) {}
    
    public function rules(): array
    {
        return ["required_if:{$this->field},{$this->value}"];
    }
    
    public function message(): string
    {
        return "The :attribute is required when {$this->field} is {$this->value}.";
    }
}

// Usage
class ShippingDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $shippingMethod,
        
        #[RequiredIf('shippingMethod', 'express')]
        public readonly ?string $expressDeliveryDate = null,
    ) {}
}
```

### Example 4: Database Validation

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class ExistsWithCondition extends ValidationAttribute
{
    public function __construct(
        private string $table,
        private string $column = 'id',
        private array $where = []
    ) {}
    
    public function rules(): array
    {
        return [
            function ($attribute, $value, $fail) {
                $query = DB::table($this->table)
                    ->where($this->column, $value);
                
                foreach ($this->where as $field => $fieldValue) {
                    $query->where($field, $fieldValue);
                }
                
                if (!$query->exists()) {
                    $fail("The selected {$attribute} is invalid.");
                }
            }
        ];
    }
}

// Usage
class OrderDTO extends SimpleDTO
{
    public function __construct(
        #[ExistsWithCondition('products', 'id', ['active' => true])]
        public readonly int $productId,
    ) {}
}
```

---

## 🔄 Framework Integration

### Laravel Custom Rule

```php
use Illuminate\Contracts\Validation\Rule;

class UppercaseRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        return strtoupper($value) === $value;
    }
    
    public function message(): string
    {
        return 'The :attribute must be uppercase.';
    }
}

// Create attribute
#[Attribute(Attribute::TARGET_PROPERTY)]
class Uppercase extends ValidationAttribute
{
    public function rules(): array
    {
        return [new UppercaseRule()];
    }
}
```

### Symfony Custom Constraint

```php
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UsernameConstraint extends Constraint
{
    public string $message = 'The username "{{ value }}" is not valid.';
}

class UsernameValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}

// Create attribute
#[Attribute(Attribute::TARGET_PROPERTY)]
class Username extends ValidationAttribute
{
    public function rules(): array
    {
        return [new UsernameConstraint()];
    }
}
```

---

## 🎨 Inline Validation

### Using Closures

```php
class CreateUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $username,
    ) {}
    
    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (str_contains($value, 'admin')) {
                        $fail('The username cannot contain "admin".');
                    }
                },
            ],
        ];
    }
}
```

---

## 💡 Best Practices

### 1. Create Reusable Attributes

```php
// ✅ Good - reusable attribute
#[Attribute(Attribute::TARGET_PROPERTY)]
class Slug extends ValidationAttribute { /* ... */ }

// ❌ Bad - inline validation everywhere
```

### 2. Use Descriptive Names

```php
// ✅ Good - descriptive name
#[StrongPassword]
public readonly string $password

// ❌ Bad - generic name
#[Custom]
public readonly string $password
```

### 3. Provide Clear Error Messages

```php
// ✅ Good - clear message
public function message(): string
{
    return 'The :attribute must be a valid username (3-20 alphanumeric characters).';
}

// ❌ Bad - vague message
public function message(): string
{
    return 'Invalid value.';
}
```

### 4. Make Attributes Configurable

```php
// ✅ Good - configurable
#[StrongPassword(minLength: 12, requireSpecialChars: true)]

// ❌ Bad - hardcoded
#[StrongPassword]
```

---

## 📚 Next Steps

1. [Validation Attributes](20-validation-attributes.md) - All built-in attributes
2. [Validation System](07-validation.md) - How validation works
3. [Security & Visibility](22-security-visibility.md) - Hidden properties
4. [Best Practices](29-best-practices.md) - Tips and recommendations

---

**Previous:** [Validation Attributes](20-validation-attributes.md)  
**Next:** [Security & Visibility](22-security-visibility.md)

