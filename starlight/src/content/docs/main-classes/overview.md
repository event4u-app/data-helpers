---
title: Main Classes Overview
description: Overview of the five main Data Helpers classes
---

Data Helpers provides five main classes for working with data:

## DataAccessor

Read nested data with dot notation and wildcards. Analyze data structure with type information.

```php
$accessor = new DataAccessor($data);

// Read values
$email = $accessor->get('user.profile.email');
$emails = $accessor->get('users.*.email');

// Get structure with type information
$structure = $accessor->getStructure();
// ['name' => 'string', 'emails.*' => '\EmailDto', ...]
```

[Learn more →](/main-classes/data-accessor/)

## DataMutator

Modify nested data structures safely.

```php
$data = ['user' => ['name' => 'Jane']];
DataMutator::make($data)
    ->set('user.name', 'John')
    ->merge('user.settings', ['theme' => 'dark'])
    ->unset('user.password');
// $data is now modified in-place
```

[Learn more →](/main-classes/data-mutator/)

## DataMapper

Transform data structures with templates and pipelines.

```php
$source = ['profile' => ['name' => 'John', 'email' => 'john@example.com']];

$result = DataMapper::source($source)
    ->template([
        'user_name' => 'profile.name',
        'user_email' => 'profile.email',
    ])
    ->map();
```

[Learn more →](/main-classes/data-mapper/)

## DataFilter

Query and filter data with SQL-like API.

```php
$data = [
    ['category' => 'Electronics', 'price' => 150],
    ['category' => 'Electronics', 'price' => 50],
    ['category' => 'Books', 'price' => 200],
];

$result = DataFilter::query($data)
    ->where('category', 'Electronics')
    ->where('price', '>', 100)
    ->orderBy('price', 'DESC')
    ->limit(10)
    ->get();
```

[Learn more →](/main-classes/data-filter/)

## SimpleDto

Immutable Data Transfer Objects with validation and casting.

```php
class UserDto extends SimpleDto
{
    public string $name;

    #[Email]
    public string $email;

    #[Min(18)]
    public int $age;
}

$user = UserDto::from(['name' => 'John', 'email' => 'john@example.com', 'age' => 25]);
```

[Learn more →](/main-classes/simple-dto/)
