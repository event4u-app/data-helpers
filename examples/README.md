# Examples

Run individual examples with:

```bash
php examples/01-data-accessor.php
php examples/02-data-mutator.php
php examples/03-data-mapper-simple.php
php examples/04-data-mapper-with-hooks.php
php examples/05-laravel.php
php examples/06-symfony-doctrine.php
```

## Example Files

### Basic Examples (Framework-agnostic)
- **01-data-accessor.php** - Reading nested data with wildcards (arrays)
- **02-data-mutator.php** - Writing, merging, and unsetting values (arrays)
- **03-data-mapper-simple.php** - Simple mapping between structures (arrays)
- **04-data-mapper-with-hooks.php** - Advanced mapping with hooks (arrays)

### Framework-specific Examples
- **05-laravel.php** - Laravel Collections, Eloquent Models, Arrayable
- **06-symfony-doctrine.php** - Symfony/Doctrine Collections and Entities

## Running Examples

All examples work out of the box. Framework-specific examples (05, 06) will use polyfills if the framework is not installed, or the real framework classes if available.

To see the difference:

```bash
# Without Laravel (uses polyfills)
php examples/05-laravel.php

# With Laravel (uses real classes)
composer require illuminate/support illuminate/database
php examples/05-laravel.php
```
