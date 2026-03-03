# Copilot Repository Instructions

This repository contains `event4u/data-helpers`, a framework-agnostic PHP library for data mapping,
DTOs and utilities. It has **zero required dependencies** and supports Laravel 9–11, Symfony 6–7,
and Doctrine 2–3 as optional integrations.

## ✅ Scope Control

- Do not introduce architectural changes unless explicitly requested.
- Do not replace existing patterns with alternative patterns.
- Do not suggest new libraries unless explicitly requested.
- Stay within the established project structure and conventions.
- This is a **library** — keep required dependencies at **zero**.

## ✅ General Coding Standards

- Follow **PSR-12** coding style and structure.
- Prefer short, expressive, and readable code.
- Use **meaningful, descriptive variable, function, class, and file names**.
- Apply proper PHPDoc blocks for classes, methods, and complex logic.
- Organize code into small, reusable functions or classes with single responsibility.
- Avoid magic numbers or hard-coded strings; use constants or config files.
- All new PHP files must declare `declare(strict_types=1);`.
- Use typed properties and return types.
- Constructor property promotion is preferred.

## ✅ PHP 8.2 Best Practices

All code must remain compatible with PHP ^8.2 (the minimum version in `composer.json`).

- Use **readonly properties** to enforce immutability where applicable.
- Use **readonly classes** when all properties are readonly (PHP 8.2+).
- Use **Enums** instead of string or integer constants.
- Utilize **First-class callable syntax** for callbacks.
- Leverage **Constructor Property Promotion**.
- Use **Union Types**, **Intersection Types**, and **true/false return types** for strict typing.
- Apply **Static Return Type** where needed.
- Use the **Nullsafe Operator (?->)** for optional chaining.
- Adopt **final classes** where extension is not intended.
- Use **Named Arguments** for improved clarity when calling functions with multiple parameters.

### When to Use `readonly` and `final`

**Use `readonly` classes when:**

- All properties in the class are readonly
- The class represents an immutable value object or DTO
- **Exception:** Do NOT use `readonly` for Pest test classes

**Use `final` classes when:**

- The class is not designed to be extended
- The class represents a specific implementation that should not be subclassed
- Example: Helpers, Converters, internal support classes
- **Exception:** Do NOT use `final` for Pest test classes
- **Exception:** Do NOT use `final` for base classes like `SimpleDto`, `LiteDto` that users extend

**Example:**

```php
// ✅ Value Object - readonly and final
final readonly class FilterCondition
{
    public function __construct(
        public string $field,
        public string $operator,
        public mixed $value,
    ) {}
}

// ✅ Helper - final
final class MathHelper
{
    public static function round(float $value, int $precision = 2): float
    {
        // ...
    }
}

// ✅ Base DTO - NOT final (users extend this)
abstract class SimpleDto
{
    // ...
}

// ✅ Pest test - no readonly, no final
test('accessor resolves nested paths', function () {
    // test code
});
```

## ✅ Project Structure & Conventions

- All library code lives under `src/` with namespace `event4u\DataHelpers\`
- Tests are in `tests/` using Pest syntax
- E2E framework tests live in `tests-e2e/` (Laravel, Symfony)
- Documentation is in `starlight/` (Astro/Starlight)
- Benchmarks are in `benchmarks/` (PHPBench)

### Source Code Organization

- `src/Config/` — Configuration loading
- `src/Console/` — CLI commands (cache warming)
- `src/Converters/` — Format converters (JSON, XML, YAML, CSV)
- `src/Enums/` — Enum classes (CacheDriver, Mode, etc.)
- `src/Exceptions/` — Custom exception classes
- `src/Frameworks/` — Framework integrations (Laravel, Symfony)
- `src/Helpers/` — Utility helpers (Math, Env, Config, DotPath, Object)
- `src/LiteDto/` — Ultra-fast minimalistic DTO internals
- `src/SimpleDto/` — Full-featured immutable DTO internals
- `src/Support/` — Internal support classes (**DO NOT modify with ECS/Rector**)
- `src/Traits/` — Shared traits
- `src/Validation/` — Validation engine

### Naming Conventions

- **Classes:** PascalCase (`DataMapper`, `SimpleDto`)

## ✅ Software Quality & Maintainability

- Follow **SOLID Principles**:
    - Single Responsibility Principle (SRP)
    - Open/Closed Principle (OCP)
    - Liskov Substitution Principle (LSP)
    - Interface Segregation Principle (ISP)
    - Dependency Inversion Principle (DIP)
- Follow **DRY** (Don't Repeat Yourself) and **KISS** (Keep It Simple, Stupid) principles.
- Apply **YAGNI** (You Aren't Gonna Need It) to avoid overengineering.
- Document complex logic with PHPDoc and inline comments.

## ✅ Quality Tools

- **PHPStan Level 9** — strictest static analysis level
- **ECS** (Easy Coding Standard) — PSR-12 based code style
- **Rector** — automated refactoring
- All tools run inside Docker containers via Taskfile commands
- The `src/Support/` directory is **skipped** by both ECS and Rector intentionally

### Key ECS Rules

- **Line length:** 120 characters
- **Yoda style comparisons:** enabled (`true === $value`)
- **Trailing commas** in multiline structures
- **Disallowed functions:** `var_dump()`, `print_r()`, `dd()`, `die()`

## ✅ Additional Copilot Behavior Preferences

- Generate **strictly typed** PHP code using **modern features available in PHP 8.2 only**
    - Avoid features from newer PHP versions unless polyfilled.
- Prioritize **readable, clean, maintainable** code over cleverness.
- Suggest tests alongside new features where applicable.
- Default to **immutability**, **dependency injection**, and **encapsulation** best practices.
- Avoid starting responses with "Sure!", "You're right!" or similar phrases; be direct and concise.
- Use **Title Case** for titles and headings to match existing documentation.

## ✅ Legacy / Existing Code Handling

- This repository contains existing code that may not fully comply with the rules defined above.
- Do NOT refactor, rewrite, or modernize existing code solely to make it comply with these rules.
- Existing code should only be modified if it is:
    - directly related to the current change,
    - required to fix a bug,
    - required for security reasons,
    - required to prevent breaking behavior,
    - or explicitly requested in the task or pull request.
- When touching existing code:
    - Keep changes minimal and scoped.
    - Do not introduce large refactorings unless strictly necessary.
    - Do not apply stylistic or architectural changes unrelated to the current task.
- New or newly modified code MUST follow all rules defined in this document.

## ✅ Code Review Scope

- When reviewing code changes, **only review the actually modified lines** and their **direct dependencies**
- Do NOT review or suggest changes to unmodified code in the same file
- **Direct dependencies** include:
    - Functions or methods that are called by the modified code
    - Functions or methods that call the modified code
    - Classes or interfaces that are directly used or implemented by the modified code
    - Properties or constants that are directly accessed by the modified code
- **Do NOT review:**
    - Unmodified code in the same file that is not directly related to the change
    - Code style issues in unmodified lines
    - Architectural patterns in unmodified code
    - Other methods or functions in the same class that are not called by or calling the modified code

## ✅ Language Rules

- All **code comments** must be written in **English**
- All **parameter names**, **variable names**, **method names**, and **class names** must be in **English**
- For GitHub comments (PR reviews, issue discussions), provide bilingual comments:
    - Write the main comment in **English** first
    - Add a German translation below, prefixed with "🇩🇪 " or separated by a horizontal line

**Example for GitHub comments:**

> This change improves performance by reducing array iterations.
>
> ---
>
> 🇩🇪 Diese Änderung verbessert die Performance durch Reduzierung der Array-Iterationen.

## ✅ Package Management

- **Always use Composer** for installing, updating, or removing dependencies
- **Never manually edit** `composer.json` for dependency changes
- Use appropriate commands:
    - `composer require package/name` to add dependencies
    - `composer require --dev package/name` to add dev dependencies
    - `composer remove package/name` to remove dependencies
- **Remember:** This is a library — `require` section should have **zero** dependencies.
  Use `require-dev` for development tools and `suggest` for optional framework integrations.

## ✅ PHPStan Baseline

- **Do NOT add entries to PHPStan baseline files** — always fix the actual error
- If fixing is truly impossible, use an inline `@phpstan-ignore` comment with a clear reason
- Baseline files are for pre-existing technical debt only

## ✅ Known Issues

- When `is_string($var)` is used, Copilot often suggests adding `null !== $var`.
  This is incorrect. The `is_string()` function already ensures that the variable is of type string.
  Adding an additional null check is redundant and should not be done.
- Pest test files without `namespace` treat all PHP built-in classes as global — do NOT add `use`
  statements for classes like `DateTimeImmutable`, `Exception`, `stdClass`.
