# Quality Tools

This document describes the quality tooling used in the data-helpers library: PHPStan, Rector, and ECS.

See also: [`agents/testing.md`](testing.md) for the test framework,
[`.github/copilot-instructions.md`](../.github/copilot-instructions.md) for coding standards.

## Overview

All tools run directly via vendor binaries inside Docker containers. Configuration lives in the
project root. Commands are executed via Taskfile (`task` command).

| Tool    | Purpose                   | Config File    |
|---------|---------------------------|----------------|
| PHPStan | Static analysis (Level 9) | `phpstan.neon` |
| Rector  | Automated refactoring     | `rector.php`   |
| ECS     | Code style (PSR-12 based) | `ecs.php`      |

## Task Commands

### `task quality:phpstan` — Static Analysis

Runs PHPStan at Level 9 against the library source, tests, benchmarks, and scripts.

### `task quality:ecs` — Code Style Check

Checks code style using ECS (dry-run).

### `task quality:ecs:fix` — Code Style Fix

Applies ECS code style fixes automatically.

### `task quality:rector` — Automated Refactoring (dry-run)

Runs Rector in dry-run mode to show what would change.

### `task quality:rector:fix` — Apply Rector Changes

Applies Rector refactoring fixes.

### `task quality:refactor` — ECS + Rector (dry-run)

Runs both ECS and Rector in dry-run mode.

### `task quality:refactor:fix` — ECS + Rector (auto-fix)

Applies both ECS and Rector fixes.

### `task quality:check` — Full Quality Pipeline

Runs all checks: ECS + Rector + PHPStan + Tests.

### `task quality:fix` — Full Fix Pipeline

Runs all fixes, then checks: ECS fix + Rector fix + PHPStan + Tests.

## PHPStan Configuration Details

- **Level 9** (strictest)
- `checkMissingCallableSignature: true` — requires exact closure type hints
- `treatPhpDocTypesAsCertain: false` — PHPDoc types are not treated as guaranteed
- `reportUnmatchedIgnoredErrors: false` — no warnings for unmatched ignore entries

### Extensions

PHPStan-PHPUnit, PHPStan-Prophecy, PHPStan-Mockery, phpat, disallowed-calls (dangerous,
execution, insecure), ergebnis-rules, phpstan-enum.

### Paths Analyzed

`benchmarks/`, `config/`, `scripts/`, `src/`, `tests/`, `tests-e2e/*/tests/Feature/`, `types/`

### Excluded Paths

- `tests-e2e/*/vendor/*`
- `*.stub.php`
- Specific Laravel/Symfony command files (optional framework dependencies)

### Disallowed Functions

`var_dump()`, `print_r()`, `dd()`, `die()` — all forbidden.

### Global Ignores

- Cast errors (`Cannot cast mixed to …`)
- Unused method warnings
- Pest-specific patterns (`$this` access, undefined properties in tests)
- Framework class resolution (Laravel/Symfony classes not available in library context)
- E2E test class resolution issues

## ECS Configuration Highlights

- Based on **PSR-12** with PHP-CS-Fixer and Symplify rule sets
- **Line length:** 120 characters (breaks long lines automatically)
- **Yoda style:** enabled (`true === $value` instead of `$value === true`)
- **Trailing commas** in multiline structures
- **Cast spaces:** none (`(int)$value`, not `(int) $value`)
- **Increment style:** post (`$i++`, not `++$i`)
- **Import ordering:** alphabetical (classes, functions, constants)
- **No extra blank lines** between cases, returns, curly braces, etc.

### Paths Analyzed

`benchmarks/`, `examples/`, `scripts/`, `src/`, `tests/`, `types/`

### Notable Skip Rules

- `StaticLambdaFixer` — can break closures that use `$this`
- `ReturnAssignmentFixer` — can break PHPStan fixes
- `FinalInternalClassFixer` / `PhpUnitStrictFixer` — can break tests
- `GeneralPhpdocAnnotationRemoveFixer` — would remove `@throws` tags
- `DeclareStrictTypesFixer` — managed manually per file
- `StrictComparisonFixer` — managed via PHPStan instead

### Skipped Paths

- `src/Support/` — uses FQN intentionally, must not be modified by ECS
- `tests-e2e/*/vendor/` — third-party code

## Rector Configuration Highlights

- **PHP 8.2** migration rules active (`LevelSetList::UP_TO_PHP_82`)
- Rule sets: dead code, code quality, coding style, type declarations, privatization,
  naming, instanceof, early return
- Function renames: `split` → `explode`, `join` → `implode`, `sizeof` → `count`, etc.
- Constant replacements: `php_sapi_name()` → `PHP_SAPI`, `pi()` → `M_PI`

### Paths Analyzed

Same as ECS: `benchmarks/`, `examples/`, `scripts/`, `src/`, `tests/`, `types/`

### Skipped Paths

- `src/Support/` — must not be modified by Rector
- `tests-e2e/*/vendor/` — third-party code

### Notable Skip Rules

- Naming rectors (variable/property/param renaming) — too aggressive
- `RemoveUnusedPrivateMethodRector` — can break internal patterns
- `RemoveNullPropertyInitializationRector` — causes "used before initialized" errors
- `DisallowedEmptyRuleFixerRector` — produces overly complex conditions
- Type declaration rectors — can conflict with PHPStan
- `SimplifyIfElseToTernaryRector` — reduces readability
- `JoinStringConcatRector` — reduces readability

## Recommended Workflow

After making changes, run this sequence:

```bash
task quality:phpstan          # 1. Check for type errors
task quality:refactor:fix     # 2. Auto-fix code style + refactoring
task quality:phpstan          # 3. Verify fixes didn't introduce issues
task test:run                 # 4. Run all tests
```

Or use the combined commands:

```bash
task quality:check            # All checks (ECS + Rector + PHPStan + Tests)
task quality:fix              # All fixes + checks
```

