# PHPStan Type Stubs

This directory contains PHPStan stub files that help PHPStan understand the types returned by the Data Helpers library.

## What are stub files?

Stub files are PHP files that contain type assertions using `PHPStan\Testing\assertType()`. They help PHPStan:
- Understand complex return types
- Verify that methods return the expected types
- Provide better IDE autocomplete
- Catch type errors earlier

## Files

- **DataAccessor.php** - Type stubs for `DataAccessor` class
- **DataMutator.php** - Type stubs for `DataMutator` class  
- **DataMapper.php** - Type stubs for `DataMapper` class

## How it works

These files are scanned by PHPStan (configured in `phpstan.neon`) and used to verify that the actual implementation matches the expected types.

Example from `DataAccessor.php`:
```php
$accessor = new DataAccessor($data);
assertType('string|null', $accessor->getString('user.name'));
assertType('int|null', $accessor->getInt('user.age'));
```

This tells PHPStan that:
- `getString()` returns `string|null`
- `getInt()` returns `int|null`

## Running the type checks

```bash
composer phpstan
```

PHPStan will analyze these stub files along with the rest of the codebase to ensure type safety.

## Inspired by

This approach is inspired by [spatie/laravel-data](https://github.com/spatie/laravel-data/tree/main/types) which uses the same pattern for type checking.
