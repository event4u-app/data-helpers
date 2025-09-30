# Dot Path Syntax

All helpers (Accessor, Mutator, Mapper) use the same dot-path syntax and wildcard semantics.

## Segments

- Segments are separated by `.` (dot): `user.profile.name`
- Numeric segments index arrays/collections: `users.0.email`
- Empty segments are not allowed.
- Validation: invalid syntax throws `InvalidArgumentException` (leading dot `.a`, trailing dot `a.`, double dots `a..b`). An empty path `""` is allowed and yields no segments.

## Wildcards

- `*` matches any single segment at that position.
- Deep wildcards: multiple `*` can appear in one path: `users.*.profile.*.city`
- `containsWildcard()` validates the path first and throws on invalid syntax.

### Accessor returns for wildcards

- Accessor returns an associative array keyed by the resolved dot-path for each match, e.g.:

```php
'accessor->get("users.*.email")' => [
  'users.0.email' => 'a@example.com',
  'users.1.email' => null,
  'users.2.email' => 'b@example.com',
]
```

This is consumed by Mapper/Mutator to expand corresponding `*` in target paths.

### Mapper wildcard expansion

- When mapping from `users.*.email` to `emails.*`:
  - Each matched value is placed into `emails.{i}`.
  - With `skipNull=true`, nulls are skipped.
  - Index handling:
    - Default (`reindexWildcard=false`): preserve numeric gaps (e.g. keep `0` and `2`).
    - With `reindexWildcard=true`: compact indices to a sequential array `[0..n-1]`.

## Root level numeric indices

- Paths like `0.name` are valid and target the root-level array index `0`.

## Missing keys

- Accessor: returns `null` if a path does not exist.
- Mapper: with `skipNull=true`, values that resolve to `null` are skipped.

## Escaping & special characters

- Paths with dots in actual keys are not supported via escaping; prefer structured mappings or pass through objects where appropriate.

## Examples

### Simple path

```
'user.profile.name' // Accesses $data['user']['profile']['name']
```

### Numeric index

```
'users.0.email' // Accesses $data['users'][0]['email']
```

### Single wildcard

```
'users.*.email' // Matches all emails in users array
// Returns: ['users.0.email' => 'a@x', 'users.1.email' => 'b@x']
```

### Deep wildcards

```
'orders.*.items.*.sku' // Matches all SKUs in all items across all orders
// Returns: ['orders.0.items.0.sku' => 'A', 'orders.0.items.1.sku' => 'B', 'orders.1.items.0.sku' => 'C']
```

### Root-level index

```
'0.name' // Accesses $data[0]['name'] when $data is a numeric array
```

## Edge cases

### Empty path

An empty path `""` is allowed and yields no segments. Accessor returns the entire data structure.

```php
$accessor = new DataAccessor(['a' => 1]);
$result = $accessor->get(''); // ['a' => 1]
```

### Invalid paths

Paths with leading dots (`.a`), trailing dots (`a.`), or double dots (`a..b`) throw `InvalidArgumentException`.

### Paths with literal dots

Paths with dots in actual keys (e.g., `user.email.address` where `email.address` is a single key) are not supported via escaping. Use structured mappings or nested objects instead.

## Best practices

- **Use explicit indices**: For reproducible behavior in numeric arrays, prefer explicit indices (`users.0.email`) over wildcards when you know the index.
- **Wildcards for bulk operations**: Use wildcards (`users.*.email`) when you need to extract or update multiple items at once.
- **Deep wildcards**: Use deep wildcards (`orders.*.items.*.sku`) for complex nested structures, but be aware of performance implications on large datasets.
- **Validate paths**: Always validate user-provided paths to avoid `InvalidArgumentException` at runtime.
- **Template-based mapping**: For building new structures from multiple sources, use `mapFromTemplate()` instead of manual path construction.

## Recommendations

- Prefer explicit indices for reproducible behavior in numeric arrays.
- Use structured mappings for complex multi-source transformations.
- For building new structures from multiple sources, consider `mapFromTemplate()`.

