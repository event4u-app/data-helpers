---
title: Performance & Caching
description: Performance optimization and caching strategies
---

Data Helpers is optimized for performance with built-in caching and efficient algorithms.

## Performance Benchmarks

DataMapper is significantly faster than traditional serializers:

- **3.7x faster** than Symfony Serializer for DTO mapping
- **2.3x faster** lazy cast speedup
- **914,000 instances/sec** DTO creation rate

See [Performance Benchmarks](/performance/benchmarks/) for detailed comparisons.

## Caching

### Path Caching

Dot-path parsing is automatically cached:

```php
// First call: Parse and cache
$value1 = $accessor->get('user.profile.name');

// Subsequent calls: Use cached path
$value2 = $accessor->get('user.profile.name'); // Faster!
```

### Template Caching

DataMapper templates are compiled and cached:

```php
$mapper = new DataMapper();

// First call: Compile and cache template
$result1 = $mapper->map($data, $template);

// Subsequent calls: Use cached template
$result2 = $mapper->map($data, $template); // Faster!
```

## Optimization Tips

### Use Explicit Paths

Explicit paths are faster than wildcards:

```php
// Faster
$name = $accessor->get('users.0.name');

// Slower (iterates all items)
$names = $accessor->get('users.*.name');
```

### Apply Filters Early

Filter data before mapping:

```php
$template = [
    'active_users' => [
        'WHERE' => ['{{ users.*.active }}' => true], // Filter first
        'ORDER BY' => ['{{ users.*.name }}' => 'ASC'],
        '*' => ['name' => '{{ users.*.name }}'],
    ],
];
```

### Use Lazy Properties

Lazy properties are only computed when accessed:

```php
class UserDTO extends SimpleDTO
{
    #[Lazy]
    public string $fullName;
    
    protected function computeFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}
```

### Batch Operations

Process multiple items at once:

```php
// Faster: Single wildcard operation
$mutator->set('orders.*.status', 'shipped');

// Slower: Loop with individual operations
foreach ($data['orders'] as $i => $order) {
    $mutator->set("orders.{$i}.status", 'shipped');
}
```

## Memory Usage

Data Helpers is memory-efficient:

- **No Reflection Overhead** - Template-based mapping avoids reflection
- **Lazy Loading** - Only load data when needed
- **Efficient Caching** - Minimal memory footprint

## See Also

- [Performance Benchmarks](/performance/benchmarks/)
- [Running Benchmarks](/performance/running-benchmarks/)
- [Optimization Guide](/performance/optimization/)
