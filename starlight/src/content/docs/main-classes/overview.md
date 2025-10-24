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
// ['name' => 'string', 'emails.*' => '\EmailDTO', ...]
```

[Learn more →](/main-classes/data-accessor/)

## DataMutator

Modify nested data structures safely.

```php
$mutator = new DataMutator($data);
$mutator->set('user.name', 'John');
$mutator->merge('user.settings', ['theme' => 'dark']);
$mutator->unset('user.password');
```

[Learn more →](/main-classes/data-mutator/)

## DataMapper

Transform data structures with templates and pipelines.

```php
$mapper = new DataMapper();
$result = $mapper->map($source, [
    'user_name' => '{{ profile.name }}',
    'user_email' => '{{ profile.email }}',
]);
```

[Learn more →](/main-classes/data-mapper/)

## DataFilter

Query and filter data with SQL-like API.

```php
$filter = new DataFilter($data);
$result = $filter
    ->where('category', 'Electronics')
    ->where('price', '>', 100)
    ->orderBy('price', 'DESC')
    ->limit(10)
    ->get();
```

[Learn more →](/main-classes/data-filter/)

## SimpleDTO

Immutable Data Transfer Objects with validation and casting.

```php
class UserDTO extends SimpleDTO
{
    public string $name;

    #[Email]
    public string $email;

    #[Min(18)]
    public int $age;
}

$user = UserDTO::from(['name' => 'John', 'email' => 'john@example.com', 'age' => 25]);
```

[Learn more →](/main-classes/simple-dto/)
