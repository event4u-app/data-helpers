# Data Helpers

Framework-agnostic PHP library for data mapping, DTOs and utilities. Part of the **event4u** ecosystem.

> **Also read:** [`.github/copilot-instructions.md`](.github/copilot-instructions.md) for the full set of
> coding standards, PHP 8.2 best practices, and conventions that apply to this repository.
> The instructions below complement and reference that file.

## Agent Task Instructions

Task-specific instructions for agents are stored as `.md` files in the `./agents/` directory.
See [`agents/roadmaps/template.md`](agents/roadmaps/template.md) for the format and rules.

### Agent Folder Structure

- **Structural docs** (e.g., `testing.md`, `quality-tools.md`) live **directly** in the `agents/` folder.
- **Roadmaps and change instructions** (e.g., refactorings, feature plans) live in `agents/roadmaps/`.
- If the current structure doesn't match this convention, the agent should reorganize it accordingly.

### Agent Behavior Rules

- **Keep docs up to date:** If a change affects something documented in an agent file, the agent must
  update that file to reflect the new state.
- **All `.md` files must be in English.** If an existing file is in German or another language,
  translate it to English when you touch it.
- **Suggest roadmap files for larger changes:** When working on significant changes without an existing
  roadmap file, ask the user whether to create one in `agents/roadmaps/`.

---

## Tech Stack

- **Type:** PHP Library / Composer Package (`event4u/data-helpers`)
- **PHP:** ^8.2 (tested on 8.2, 8.3, 8.4)
- **Dependencies:** Zero required dependencies (framework integrations are optional)
- **Framework Support:** Laravel 9–11, Symfony 6–7, Doctrine 2–3 (all optional)
- **Testing:** Pest (PHPUnit 11 under the hood)
- **Static Analysis:** PHPStan Level 9
- **Code Style:** ECS (Easy Coding Standard) — PSR-12 based
- **Refactoring:** Rector
- **Task Runner:** Taskfile (`task` command)
- **Containers:** Docker with PHP 8.2, 8.3, 8.4 containers
- **Documentation:** Starlight (Astro-based, in `starlight/`)
- **Benchmarks:** PHPBench (in `benchmarks/`)
- **Editor Config:** `.editorconfig` is used — respect it

---

## Development Setup

The project runs in Docker with three PHP containers (one per PHP version).

```bash
# Start Docker containers
task docker:up

# Setup development environment (install, build, etc.)
task dev:setup

# Run tests
task test:run

# Run quality checks
task quality:check
```

### Docker Containers

| Container            | PHP Version | Purpose            |
|----------------------|-------------|--------------------|
| `data-helpers-php82` | 8.2         | Compatibility      |
| `data-helpers-php83` | 8.3         | Compatibility      |
| `data-helpers-php84` | 8.4         | Default / Primary  |

Commands run via `docker exec <container>`. Most task commands default to PHP 8.4.
Use `PHP=8.2` or `PHP=8.3` to override, e.g. `task test:run PHP=8.2`.

---

## Project Structure

```
├── src/                    ← Library source code (namespace: event4u\DataHelpers)
│   ├── Config/             ← Configuration loading
│   ├── Console/            ← CLI commands (cache warming)
│   ├── Converters/         ← Format converters (JSON, XML, YAML, CSV)
│   ├── DataAccessor.php    ← Read & transform nested data
│   ├── DataCollection.php  ← Type-safe collections
│   ├── DataFilter.php      ← SQL-like data filtering
│   ├── DataMapper.php      ← Template-based data mapping
│   ├── DataMutator.php     ← Modify nested data structures
│   ├── Enums/              ← Enums (CacheDriver, Mode, etc.)
│   ├── Exceptions/         ← Custom exception classes
│   ├── Frameworks/         ← Framework integrations (Laravel, Symfony)
│   ├── Helpers/            ← Utility helpers (Math, Env, Config, DotPath, Object)
│   ├── LiteDto/            ← Ultra-fast minimalistic DTOs
│   ├── LiteDto.php         ← LiteDto base class
│   ├── Logging/            ← Logging infrastructure
│   ├── SimpleDto/          ← Full-featured immutable DTOs
│   ├── SimpleDto.php       ← SimpleDto base class
│   ├── Support/            ← Internal support classes (DO NOT modify with ECS/Rector)
│   ├── Traits/             ← Shared traits
│   └── Validation/         ← Validation engine
├── tests/                  ← Test suite (Pest)
│   ├── Unit/               ← Unit tests (no framework dependencies)
│   ├── Integration/        ← Integration tests
│   ├── Documentation/      ← Documentation example tests
│   ├── Utils/              ← Test utilities (DTOs, Models, helpers)
│   └── Pest.php            ← Pest configuration
├── tests-e2e/              ← End-to-end framework tests
│   ├── Laravel/            ← Laravel E2E test app
│   └── Symfony/            ← Symfony E2E test app
├── examples/               ← Runnable code examples

---

## Testing

### Test Framework: Pest

All tests are written in **Pest** syntax. See [`agents/testing.md`](agents/testing.md) for full details.

### Test Suites (defined in `phpunit.xml`)

| Suite         | Location               | Purpose                                  |
|---------------|------------------------|------------------------------------------|
| Unit          | `tests/Unit/`          | Isolated class tests, no framework deps  |
| Integration   | `tests/Integration/`   | Tests with framework integration         |
| Documentation | `tests/Documentation/` | Validates code examples from docs/README |

### Running Tests

```bash
task test:run             # Run tests (default PHP 8.4 container)
task test:unit            # Unit tests only
task test:documentation   # Documentation tests only
task test:full            # Full suite including E2E
task test:e2e             # E2E tests only
task test:run PHP=8.2     # Tests with specific PHP version
```

### Framework-Specific Tests (Isolated Containers)

```bash
task test:laravel10       # Laravel 10 (isolated)
task test:laravel11       # Laravel 11 (isolated)
task test:symfony6        # Symfony 6 (isolated)
task test:symfony7        # Symfony 7 (isolated)
task test:doctrine2       # Doctrine 2 (isolated)
task test:doctrine3       # Doctrine 3 (isolated)
task test:matrix          # Complete test matrix (all combinations)
```

### Test Guidelines

- Do NOT use `readonly` or `final` on Pest test classes
- Write clear, human-readable test names
- Test DTOs and utilities belong in `tests/Utils/`
- Pest test files without `namespace` — do NOT add `use` for global PHP classes

---

## Quality Tools

PHPStan, ECS, and Rector run directly via vendor binaries inside Docker containers.
See [`agents/quality-tools.md`](agents/quality-tools.md) for full details.

### Quality Commands

```bash
task quality:phpstan      # PHPStan analysis (Level 9)
task quality:ecs          # ECS code style check
task quality:ecs:fix      # ECS auto-fix
task quality:rector       # Rector dry-run
task quality:rector:fix   # Rector auto-fix
task quality:check        # All checks (ECS + Rector + PHPStan + Tests)
task quality:fix          # All fixes + checks
task quality:refactor     # ECS + Rector dry-run
task quality:refactor:fix # ECS + Rector auto-fix
```

### Recommended Quality Workflow

```bash
task quality:phpstan          # 1. Check for type errors
task quality:refactor:fix     # 2. Auto-fix code style + refactoring
task quality:phpstan          # 3. Verify fixes didn't introduce issues
task test:run                 # 4. Run all tests
```

---

## Code Standards

> Full details in [`.github/copilot-instructions.md`](.github/copilot-instructions.md).

### PHP 8.2 Requirements

- All new PHP files must declare `declare(strict_types=1);`
- Use typed properties and return types
- Constructor property promotion preferred
- Use `readonly` properties/classes for immutable objects (DTOs, Value Objects)
- Use `final` classes where extension is not intended
- Use Enums instead of string/integer constants
- Use Named Arguments for clarity with multiple parameters
- Use Nullsafe Operator (`?->`) for optional chaining

### Key Configuration

- **PHPStan Level 9** — strictest level
- **ECS line length:** 120 characters
- **Yoda style comparisons:** enabled (`true === $value`)
- **Trailing commas** in multiline structures
- **Disallowed functions:** `var_dump()`, `print_r()`, `dd()`, `die()`

### Naming Conventions

- **Namespace:** `event4u\DataHelpers\`
- **Classes:** PascalCase (`DataMapper`, `SimpleDto`)
- **Helpers:** PascalCase + `Helper` suffix (`MathHelper`, `EnvHelper`)
- **Traits:** PascalCase + `Trait` suffix (`SimpleDtoEloquentTrait`)
- **Enums:** PascalCase (`CacheDriver`, `Mode`)
- **Tests:** PascalCase + `Test` suffix (`DataAccessorTest`)

### Language Rules

- All **code comments** must be written in **English**
- All **parameter names**, **variable names**, **method names**, and **class names** must be in **English**

---

## Documentation

The project uses **Starlight** (Astro-based) for documentation, located in `starlight/`.

```bash
task docs:dev             # Start local documentation server
task docs:build           # Build documentation
```

Documentation source files are in `starlight/src/content/docs/` as `.md` and `.mdx` files.

### Documentation Tests

Code examples in the README and documentation are automatically tested via the `Documentation`
test suite (`tests/Documentation/`). When changing examples, ensure tests still pass.

---

## Scope Control & Legacy Code

- Do NOT introduce architectural changes unless explicitly requested
- Do NOT replace existing patterns with alternatives
- Do NOT suggest new libraries unless explicitly requested
- Stay within established project structure and conventions
- Do NOT refactor existing code solely to comply with current rules
- New or newly modified code MUST follow all rules in this document

---

## Package Management

- **Always use Composer** for dependency management — never manually edit `composer.json`
- `composer require package/name` to add dependencies
- `composer require --dev package/name` for dev dependencies
- `composer remove package/name` to remove dependencies
- This is a **library** — keep required dependencies at **zero** (use `require-dev` or `suggest`)

---

## Useful Task Commands

| Command                    | Description                            |
|----------------------------|----------------------------------------|
| `task docker:up`           | Start Docker containers                |
| `task docker:down`         | Stop Docker containers                 |
| `task dev:setup`           | Setup development environment          |
| `task dev:pre-commit`      | Pre-commit checks                      |
| `task test:run`            | Run tests                              |
| `task test:unit`           | Run unit tests only                    |
| `task test:documentation`  | Run documentation tests                |
| `task test:e2e`            | Run E2E tests                          |
| `task test:matrix`         | Run complete test matrix               |
| `task quality:check`       | Run all quality checks                 |
| `task quality:fix`         | Run all quality fixes + checks         |
| `task quality:phpstan`     | Run PHPStan                            |
| `task quality:ecs`         | Run ECS                                |
| `task quality:ecs:fix`     | Fix code style with ECS                |
| `task quality:rector`      | Run Rector dry-run                     |
| `task quality:rector:fix`  | Apply Rector changes                   |
| `task bench:run`           | Run benchmarks                         |
| `task docs:dev`            | Start docs dev server                  |
| `task docs:build`          | Build documentation                    |
| `task fork:update`         | Sync fork with upstream                |

---

## Quality Checklist

When making changes, **always** follow this checklist before considering the work done:

1. **Run PHPStan after every change** — `task quality:phpstan`
2. **Run ECS/Rector** — `task quality:refactor:fix`
3. **Run PHPStan again** — verify fixes didn't introduce issues
4. **Run tests** — `task test:run`
5. **Check documentation tests** if examples changed — `task test:documentation`
6. **Clean up dead code** when replacing functionality
7. **Respect the `src/Support/` skip** — ECS and Rector skip this directory intentionally

---

## Known Issues

- `is_string($var)` already ensures the variable is a string — do NOT add redundant `null !== $var` checks
- Pest test files without `namespace` treat all PHP built-in classes as global — do NOT add `use`
  statements for classes like `DateTimeImmutable`, `Exception`, `stdClass`

---

## Additional Documentation

| Document                             | Topic                               |
|--------------------------------------|-------------------------------------|
| `.github/copilot-instructions.md`    | Full coding standards & conventions |
| `agents/quality-tools.md`            | Quality tooling details             |
| `agents/testing.md`                  | Testing architecture & conventions  |
| `agents/project-structure.md`        | Project structure & architecture    |
| `agents/roadmaps/template.md`        | Template for agent task files       |
| `starlight/src/content/docs/`        | Full library documentation          |
| `README.md`                          | Library overview and usage examples |
| `CONTRIBUTING.md`                    | Contributing guidelines             |
