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

## Recommendations

- Prefer explicit indices for reproducible behavior in numeric arrays.
- Use structured mappings for complex multi-source transformations.
- For building new structures from multiple sources, consider `mapFromTemplate()`.

