# Contributing to Data Helpers

Thank you for considering contributing to Data Helpers! This document provides guidelines and instructions for contributing.

## 🚀 Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- Git

### Setup

1. Fork the repository
2. Clone your fork:
   ```bash
   git clone git@github.com:YOUR_USERNAME/data-helpers.git
   cd data-helpers
   ```

3. Install dependencies:
   ```bash
   composer install
   ```

4. Run tests to ensure everything works:
   ```bash
   composer test
   ```

## 🧪 Testing

We use [Pest](https://pestphp.com/) for testing. All contributions must include tests.

### Running Tests

```bash
# Run all tests
composer test

# Run specific test file
composer test -- tests/Unit/DataMapper/DataMapperTest.php

# Run tests with filter
composer test -- --filter="maps nested key"

# Run with coverage (requires Xdebug)
composer test-coverage
```

### Writing Tests

- Place tests in the `tests/Unit/` directory
- Use descriptive test names
- Follow the existing test structure
- Aim for high code coverage

Example:
```php
test('maps nested data correctly', function(): void {
    $source = ['user' => ['name' => 'Alice']];
    $mapping = ['name' => '{{ user.name }}'];
    
    $result = DataMapper::map($source, [], $mapping);
    
    expect($result)->toBe(['name' => 'Alice']);
});
```

## 📝 Code Style

We follow PSR-12 coding standards and use PHP Easy Coding Standard (ECS).

### Running Code Style Checks

```bash
# Check code style
composer ecs

# Fix code style automatically
composer ecs-fix
```

### Static Analysis

We use PHPStan at Level 9 for static analysis:

```bash
# Run PHPStan
composer phpstan

# Run PHPStan with baseline
composer phpstan-baseline
```

## 🎯 Mapping Format Convention

**Important:** We use `target => source` format for all mappings:

```php
// ✅ CORRECT - target => source
$mapping = [
    'profile.name' => 'user.name',
    'profile.email' => 'user.email',
];

// Or nested format:
$mapping = [
    'profile' => [
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
    ],
];
```

This makes the target structure immediately visible and easier to understand.

## 📚 Documentation

- All public methods must have PHPDoc comments
- Include `@param`, `@return`, and `@throws` tags
- Add examples in docblocks for complex functionality
- Update README.md if adding new features
- Comments should be in English

Example:
```php
/**
 * Map data from source to target using dot-notation paths.
 *
 * @param mixed $source Source data (array, object, Collection, Model)
 * @param mixed $target Target data structure
 * @param array<string, mixed> $mapping Mapping definition (target => source)
 * @param bool $skipNull Skip null values (default: true)
 * @return mixed Mapped result
 */
public static function map(
    mixed $source,
    mixed $target,
    array $mapping,
    bool $skipNull = true
): mixed {
    // ...
}
```

## 🔄 Pull Request Process

1. **Create a feature branch:**
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes:**
   - Write code following our conventions
   - Add/update tests
   - Update documentation

3. **Ensure quality:**
   ```bash
   composer test        # All tests must pass
   composer ecs         # Code style must be clean
   composer phpstan     # No PHPStan errors
   ```

4. **Commit your changes:**
   ```bash
   git add .
   git commit -m "feat: add new feature"
   ```
   
   We follow [Conventional Commits](https://www.conventionalcommits.org/):
   - `feat:` - New feature
   - `fix:` - Bug fix
   - `docs:` - Documentation changes
   - `test:` - Test changes
   - `refactor:` - Code refactoring
   - `perf:` - Performance improvements
   - `chore:` - Maintenance tasks

5. **Push to your fork:**
   ```bash
   git push origin feature/your-feature-name
   ```

6. **Create a Pull Request:**
   - Provide a clear description
   - Reference any related issues
   - Ensure CI checks pass

## 🐛 Reporting Bugs

When reporting bugs, please include:

- PHP version
- Package version
- Minimal code example to reproduce
- Expected vs actual behavior
- Stack trace if applicable

## 💡 Suggesting Features

We welcome feature suggestions! Please:

- Check if the feature already exists
- Explain the use case
- Provide example code if possible
- Consider backward compatibility

## 📋 Development Guidelines

### Performance

- Avoid unnecessary loops
- Use early returns
- Cache expensive operations
- Profile performance-critical code

### Backward Compatibility

- Don't break existing APIs without major version bump
- Deprecate features before removing them
- Document breaking changes clearly

### Security

- Validate all inputs
- Avoid code injection risks
- Don't expose sensitive data in errors
- Follow OWASP guidelines

## 🏗️ Project Structure

```
data-helpers/
├── src/
│   ├── DataAccessor.php       # Read nested data
│   ├── DataMutator.php         # Write nested data
│   ├── DataMapper.php          # Transform data structures
│   └── DataMapper/
│       ├── AutoMapper.php      # Automatic field mapping
│       ├── Pipeline/           # Pipeline filters
│       └── Support/            # Internal helpers
├── tests/
│   └── Unit/                   # Unit tests
├── docs/                       # Documentation
└── examples/                   # Usage examples
```

## 🤝 Code Review

All submissions require review. We look for:

- ✅ Code quality and readability
- ✅ Test coverage
- ✅ Documentation completeness
- ✅ Performance considerations
- ✅ Backward compatibility

## 📞 Getting Help

- Open an issue for bugs or questions
- Check existing issues and documentation first
- Be respectful and constructive

## 📜 License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

Thank you for contributing to Data Helpers! 🎉

