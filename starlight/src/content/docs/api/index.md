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
$accessor = DataAccessor::make($data);
$value = $accessor->get('user.profile.name');
```

### [DataMutator](/api/data-mutator/)

Modify data using dot-notation paths:

```php
$mutator = DataMutator::make($data);
$mutator->set('user.profile.name', 'John Doe');
```

### [DataMapper](/api/data-mapper/)

Map data between structures:

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
$filter = DataFilter::make($data);
$filtered = $filter->only(['name', 'email']);
```

### [SimpleDTO](/api/simple-dto/)

Type-safe data transfer objects:

```php
class UserDTO extends SimpleDTO
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

```php
$value = ConfigHelper::get('app.name', 'default');
```

### [DotPathHelper](/api/helpers/#dotpathhelper)

Dot-path notation utilities:

```php
$value = DotPathHelper::get($data, 'user.profile.name');
```

### [ObjectHelper](/api/helpers/#objecthelper)

Object manipulation utilities:

```php
$clone = ObjectHelper::deepClone($object);
```

## Attributes

### [Validation Attributes](/api/attributes/#validation)

30+ validation attributes:

```php
#[Required, Email, Min(3), Max(50)]
public readonly string $email;
```

### [Conditional Attributes](/api/attributes/#conditional)

18 conditional attributes:

```php
#[WhenAuth, WhenCan('edit'), WhenRole('admin')]
public readonly ?string $adminNotes = null;
```

### [Cast Attributes](/api/attributes/#casting)

Type casting:

```php
#[Cast(DateTimeCast::class)]
public readonly Carbon $createdAt;
```

### [Mapping Attributes](/api/attributes/#mapping)

Property mapping:

```php
#[MapFrom('user.full_name')]
public readonly string $name;
```

## Casts

### [Primitive Casts](/api/casts/#primitive)

Basic type casts:

```php
StringCast, IntCast, FloatCast, BoolCast, ArrayCast
```

### [Date Casts](/api/casts/#date)

Date and time casts:

```php
DateTimeCast, DateCast, TimeCast, TimestampCast
```

### [Enum Casts](/api/casts/#enum)

Enum casts:

```php
EnumCast, BackedEnumCast
```

### [Collection Casts](/api/casts/#collection)

Collection casts:

```php
CollectionCast, DataCollectionCast
```

## See Also

- [Getting Started](/getting-started/quick-start/) - Quick start guide
- [Main Classes](/main-classes/overview/) - Main classes overview
- [SimpleDTO](/simple-dto/introduction/) - DTO introduction
