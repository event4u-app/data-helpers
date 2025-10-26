---
title: Contributing Guide
description: Guidelines and instructions for contributing to Data Helpers
---

Thank you for considering contributing to Data Helpers! This guide will help you get started.

:::tip[New to Forking?]
If you're new to the fork and pull request workflow, check out our detailed [Fork & Pull Request Guide](/guides/fork-and-pull-request/) for step-by-step instructions on how to fork, work locally, and create pull requests.
:::

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- Git
- Docker & Docker Compose (recommended)
- Task (optional but recommended)

### Setup Development Environment

1. **Fork the repository** on GitHub

2. **Clone your fork:**
   ```bash
   git clone git@github.com:YOUR_USERNAME/data-helpers.git
   cd data-helpers
   ```

3. **Setup development environment:**
   ```bash
   task dev:setup
   ```

   Or manually:
   ```bash
   docker-compose up -d --build
   docker exec -it data-helpers-php84 composer install
   ```

4. **Verify installation:**
   ```bash
   task test:run
   ```

See [Development Setup](/guides/development-setup/) for detailed instructions.

## Development Workflow

### 1. Create a Feature Branch

```bash
git checkout -b feature/my-feature
```

Branch naming conventions:
- `feature/` - New features
- `fix/` - Bug fixes
- `docs/` - Documentation changes
- `refactor/` - Code refactoring
- `test/` - Test improvements

### 2. Make Your Changes

- Write clean, readable code
- Follow PSR-12 coding standards
- Add tests for new features
- Update documentation if needed

### 3. Run Quality Checks

```bash
# Fix code style
task quality:ecs:fix

# Run static analysis
task quality:phpstan

# Run tests
task test:run

# Or run all checks at once
task dev:pre-commit
```

### 4. Commit Your Changes

Use [Conventional Commits](https://www.conventionalcommits.org/):

```bash
git add .
git commit -m "feat: add new feature"
```

Commit types:
- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation changes
- `style:` - Code style changes (formatting)
- `refactor:` - Code refactoring
- `test:` - Test changes
- `chore:` - Build/tooling changes

### 5. Push and Create Pull Request

```bash
git push origin feature/my-feature
```

Then create a Pull Request on GitHub.

## Testing

We use [Pest](https://pestphp.com/) for testing. All contributions must include tests.

### Running Tests

```bash
# Run all tests
task test:run

# Run unit tests only
task test:unit

# Run E2E tests only
task test:e2e

# Run specific test file
task test:unit -- tests/Unit/DataMapper/DataMapperTest.php

# Run tests with filter
task test:unit -- --filter="maps nested key"

# Run with coverage (requires Xdebug)
task test:coverage
```

### Writing Tests

Place tests in the appropriate directory:
- `tests/Unit/` - Unit tests
- `tests-e2e/` - End-to-end tests

Use descriptive test names:

```php
test('maps nested data correctly', function (): void {
    $source = ['user' => ['name' => 'Alice']];
    $mapping = ['name' => '{{ user.name }}'];

    $result = DataMapper::source($source)
        ->template($mapping)
        ->map()
        ->getTarget();

    expect($result)->toBe(['name' => 'Alice']);
});
```

### Test Guidelines

- ✅ Test one thing per test
- ✅ Use descriptive test names
- ✅ Follow AAA pattern (Arrange, Act, Assert)
- ✅ Test edge cases and error conditions
- ✅ Aim for high code coverage
- ✅ Keep tests fast and isolated

## Code Style

We follow PSR-12 coding standards and use PHP Easy Coding Standard (ECS).

### Running Code Style Checks

```bash
# Check code style
task quality:ecs

# Fix code style automatically
task quality:ecs:fix
```

### Code Style Guidelines

- Use 4 spaces for indentation
- Use type hints for all parameters and return types
- Use strict types: `declare(strict_types=1);`
- Use readonly properties where possible
- Document complex logic with comments
- Keep methods short and focused

## Static Analysis

We use PHPStan at Level 9 for static analysis.

### Running PHPStan

```bash
# Run PHPStan
task quality:phpstan

# Generate baseline (if needed)
task quality:phpstan:baseline
```

### PHPStan Guidelines

- Fix all PHPStan errors before submitting PR
- Don't add to baseline unless absolutely necessary
- Use proper type hints to avoid PHPStan errors
- Use `@phpstan-ignore-next-line` sparingly

## Documentation

### Code Documentation

- Add PHPDoc blocks for all public methods
- Document parameters and return types
- Include usage examples for complex features
- Keep documentation up to date

Example:

```php
/**
 * Maps source data to target structure using template expressions.
 *
 * @param array<string, mixed> $source Source data
 * @param array<string, mixed> $source Source data
 * @param array<string, string> $mapping Mapping configuration
 * @return array<string, mixed> Mapped result
 *
 * @example
 * $result = DataMapper::source(['user' => ['name' => 'Alice']])
 *     ->template(['name' => '{{ user.name }}'])
 *     ->map()
 *     ->getTarget();
 */
public static function map(array $source, array $target, array $mapping): array
{
    // Implementation
}
```

### User Documentation

If your contribution adds new features:

1. Update relevant documentation pages in `documentation/src/content/docs/`
2. Add code examples
3. Update the changelog

## Pull Request Process

### Before Submitting

- ✅ All tests pass: `task test:run`
- ✅ Code style is correct: `task quality:ecs:fix`
- ✅ PHPStan passes: `task quality:phpstan`
- ✅ Documentation is updated
- ✅ Changelog is updated (if applicable)

### PR Guidelines

1. **Title:** Use conventional commit format
   - Example: `feat: add support for nested wildcards`

2. **Description:** Include:
   - What changes were made
   - Why the changes were needed
   - How to test the changes
   - Related issues (if any)

3. **Size:** Keep PRs focused and reasonably sized
   - Large PRs are harder to review
   - Consider splitting into multiple PRs

4. **Tests:** Include tests for all changes
   - New features must have tests
   - Bug fixes should include regression tests

### Review Process

1. Automated checks will run (tests, code style, PHPStan)
2. Maintainers will review your code
3. Address any feedback or requested changes
4. Once approved, your PR will be merged

## Reporting Issues

### Bug Reports

Include:
- PHP version
- Framework version (Laravel/Symfony)
- Steps to reproduce
- Expected behavior
- Actual behavior
- Code example (if possible)

### Feature Requests

Include:
- Use case description
- Proposed solution
- Alternative solutions considered
- Code examples (if applicable)

## Code of Conduct

- Be respectful and inclusive
- Welcome newcomers
- Focus on constructive feedback
- Assume good intentions

## Questions?

- Open a [GitHub Discussion](https://github.com/event4u-app/data-helpers/discussions)
- Check existing [Issues](https://github.com/event4u-app/data-helpers/issues)
- Read the [Documentation](/)

## Next Steps

- [Development Setup](/guides/development-setup/) - Setup your environment
- [Testing Guide](/testing/testing-dtos/) - Learn about testing
- [Architecture](/guides/architecture/) - Understand the codebase structure

Thank you for contributing! 🎉

