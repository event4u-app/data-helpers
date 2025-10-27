---
title: API Reference Overview
description: Complete API documentation for Data Helpers
---

Complete API documentation for Data Helpers.

## Introduction

Browse the complete API reference:

- **Main Classes** - Core functionality
- **Helpers** - Utility classes
- **Attributes** - PHP attributes
- **Casts** - Type casting classes

## Main Classes

### [DataAccessor](/api/data-accessor/)

Read data using dot-notation paths:

```php
$accessor = DataAccessor::make(['user' => ['profile' => ['name' => 'John']]]);
$value = $accessor->get('user.profile.name');
```

### [DataMutator](/api/data-mutator/)

Modify data using dot-notation paths:

<!-- skip-test: requires $data variable -->
```php
$mutator = DataMutator::make($data);
$mutator->set('user.profile.name', 'John Doe');
```

### [DataMapper](/api/data-mapper/)

Map data between structures:

<!-- skip-test: requires variables -->
```php
$result = DataMapper::source($src)
    ->target($tgt)
    ->template($template)
    ->map()
    ->getTarget();
```

### [DataFilter](/api/data-filter/)

Filter and transform data:

```php
$filter = DataFilter::make(['name' => 'John', 'email' => 'john@example.com', 'age' => 30]);
$filtered = $filter->only(['name', 'email']);
```

### [SimpleDto](/api/simple-dto/)

Type-safe data transfer objects:

```php
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}
```

## Helpers

### [EnvHelper](/api/helpers/#envhelper)

Framework-agnostic environment variables:

```php
$value = EnvHelper::get('APP_NAME', 'default');
```

### [MathHelper](/api/helpers/#mathhelper)

High-precision math operations:

```php
$result = MathHelper::add('0.1', '0.2', 2); // '0.30'
```

### [ConfigHelper](/api/helpers/#confighelper)

Universal configuration helper:

<!-- skip-test: ConfigHelper::get() is not static -->
```php
$value = ConfigHelper::get('app.name', 'default');
```

### [DotPathHelper](/api/helpers/#dotpathhelper)

Dot-path notation utilities:

```php
$segments = DotPathHelper::segments('user.profile.name');
// $segments = ['user', 'profile', 'name']
```

### [ObjectHelper](/api/helpers/#objecthelper)

Object manipulation utilities:

```php
$object = (object)['name' => 'John', 'age' => 30];
$clone = ObjectHelper::copy($object);
```

## Attributes

### [Validation Attributes](/api/attributes/#validation)

30+ validation attributes:

<!-- skip-test: incomplete code snippet -->
```php
#[Required, Email, Min(3), Max(50)]
public readonly string $email;
```

### [Conditional Attributes](/api/attributes/#conditional)

18 conditional attributes:

<!-- skip-test: incomplete code snippet -->
```php
#[WhenAuth, WhenCan('edit'), WhenRole('admin')]
public readonly ?string $adminNotes = null;
```

### [Cast Attributes](/api/attributes/#casting)

Type casting:

<!-- skip-test: incomplete code snippet -->
```php
#[Cast(DateTimeCast::class)]
public readonly Carbon $createdAt;
```

### [Mapping Attributes](/api/attributes/#mapping)

Property mapping:

<!-- skip-test: incomplete code snippet -->
```php
#[MapFrom('user.full_name')]
public readonly string $name;
```

## Casts

### [Primitive Casts](/api/casts/#primitive)

Basic type casts:

<!-- skip-test: list of class names -->
```php
StringCast, IntCast, FloatCast, BoolCast, ArrayCast
```

### [Date Casts](/api/casts/#date)

Date and time casts:

<!-- skip-test: list of class names -->
```php
DateTimeCast, DateCast, TimeCast, TimestampCast
```

### [Enum Casts](/api/casts/#enum)

Enum casts:

<!-- skip-test: list of class names -->
```php
EnumCast, BackedEnumCast
```

### [Collection Casts](/api/casts/#collection)

Collection casts:

<!-- skip-test: list of class names -->
```php
CollectionCast, DataCollectionCast
```

## See Also

- [Getting Started](/getting-started/quick-start/) - Quick start guide
- [Main Classes](/main-classes/overview/) - Main classes overview
- [SimpleDto](/simple-dto/introduction/) - Dto introduction
