# GitHub Actions Workflows

## Overview

This project uses GitHub Actions for continuous integration with a comprehensive test matrix that ensures framework independence and compatibility.

## Workflows

### 1. Test Matrix (Isolated) - `test-matrix.yml`

**Purpose:** Comprehensive test matrix with isolated framework testing

**Runs on:**
- Push to `main` branch
- Pull requests to `main`
- Manual trigger (`workflow_dispatch`)

**Test Strategy:**

#### Plain PHP Tests (3 tests)
Tests the library without any frameworks installed:
- PHP 8.2 - Plain
- PHP 8.3 - Plain
- PHP 8.4 - Plain

#### Laravel Isolated Tests (6 tests)
Tests with ONLY Laravel installed (other frameworks removed):
- PHP 8.2 - Laravel 9
- PHP 8.2 - Laravel 10
- PHP 8.2 - Laravel 11
- PHP 8.3 - Laravel 10
- PHP 8.3 - Laravel 11
- PHP 8.4 - Laravel 11

#### Symfony Isolated Tests (6 tests)
Tests with ONLY Symfony installed (other frameworks removed):
- PHP 8.2 - Symfony 6
- PHP 8.2 - Symfony 7
- PHP 8.3 - Symfony 6
- PHP 8.3 - Symfony 7
- PHP 8.4 - Symfony 6
- PHP 8.4 - Symfony 7

#### Doctrine Isolated Tests (6 tests)
Tests with ONLY Doctrine installed (other frameworks removed):
- PHP 8.2 - Doctrine 2
- PHP 8.2 - Doctrine 3
- PHP 8.3 - Doctrine 2
- PHP 8.3 - Doctrine 3
- PHP 8.4 - Doctrine 2
- PHP 8.4 - Doctrine 3

#### E2E Tests (6 tests)
Integration tests with all frameworks installed together:
- PHP 8.2 - Laravel E2E
- PHP 8.2 - Symfony E2E
- PHP 8.3 - Laravel E2E
- PHP 8.3 - Symfony E2E
- PHP 8.4 - Laravel E2E
- PHP 8.4 - Symfony E2E

**Total: 27 test jobs**

**Summary Job:**
A final job that checks all test results and provides a summary.

---

### 2. Code Quality - `code-quality.yml`

**Purpose:** Automated code quality checks and fixes

**Runs on:**
- Push to `main` branch
- Pull requests to `main`
- Manual trigger

**Steps:**
1. Run ECS (Easy Coding Standard) with auto-fix
2. Run Rector with auto-fix
3. (Optional) Auto-commit fixes

**PHP Version:** 8.4

---

### 3. PHPStan - `phpstan.yml`

**Purpose:** Static analysis with PHPStan Level 9

**Runs on:**
- Push to `main` branch
- Pull requests to `main`
- Manual trigger

**Steps:**
1. Install dependencies
2. Run PHPStan analysis

**PHP Version:** 8.4

---

### 4. Build Documentation - `build-docs.yml`

**Purpose:** Build static documentation site and deploy to GitHub Pages

**Runs on:**
- Push to `main` branch
- Pull requests to `main`

**Jobs:**

#### Job 1: build-docs
1. Checkout code
2. Setup Node.js 18
3. Install npm dependencies
4. Build documentation with Astro/Starlight
5. Copy to `dist/` directory
6. Upload as artifact (30 days)

#### Job 2: deploy-pages (only on push to main)
1. Download artifact
2. Setup GitHub Pages
3. Upload Pages artifact
4. Deploy to GitHub Pages

**Output:**
- Static HTML documentation in `dist/`
- Artifact available for 30 days (temporary)
- **GitHub Pages deployment (permanent)**

**URLs:**
- GitHub Pages: `https://event4u-app.github.io/data-helpers/`
- Artifact: Available in Actions tab for 30 days

**Local Build:**
```bash
task docs:build:dist
```

**Setup Required:**
See [GITHUB_PAGES_SETUP.md](../GITHUB_PAGES_SETUP.md) for initial setup instructions.

---

## Why Isolated Testing?

### Problem
When all frameworks are installed together, tests might pass due to:
- Shared dependencies
- Cross-framework compatibility
- Hidden dependency conflicts

### Solution
**Isolated testing** ensures:
- ✅ Library works with each framework independently
- ✅ No hidden dependencies between frameworks
- ✅ Real-world usage scenarios (projects use one framework)
- ✅ Framework-specific issues are caught

### How It Works

For each isolated test:
1. **Backup** - Save `composer.json` and `composer.lock`
2. **Remove** - Remove ALL framework packages
3. **Install** - Install ONLY the target framework
4. **Test** - Run test suite
5. **Restore** - Restore original composer files

Example for Laravel 11:
```bash
# Remove all frameworks
composer remove --dev illuminate/* symfony/* doctrine/* --no-update

# Install only Laravel 11
composer require --dev "illuminate/support:^11.0" "illuminate/database:^11.0" "illuminate/http:^11.0"

# Run tests
task test:unit

# Restore original files
```

---

## Test Matrix Visualization

```
┌─────────────────────────────────────────────────────────────┐
│                    Test Matrix (27 jobs)                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Plain PHP (3)                                              │
│  ├── PHP 8.2 - Plain                                        │
│  ├── PHP 8.3 - Plain                                        │
│  └── PHP 8.4 - Plain                                        │
│                                                             │
│  Laravel Isolated (6)                                       │
│  ├── PHP 8.2 - Laravel 9                                    │
│  ├── PHP 8.2 - Laravel 10                                   │
│  ├── PHP 8.2 - Laravel 11                                   │
│  ├── PHP 8.3 - Laravel 10                                   │
│  ├── PHP 8.3 - Laravel 11                                   │
│  └── PHP 8.4 - Laravel 11                                   │
│                                                             │
│  Symfony Isolated (6)                                       │
│  ├── PHP 8.2 - Symfony 6                                    │
│  ├── PHP 8.2 - Symfony 7                                    │
│  ├── PHP 8.3 - Symfony 6                                    │
│  ├── PHP 8.3 - Symfony 7                                    │
│  ├── PHP 8.4 - Symfony 6                                    │
│  └── PHP 8.4 - Symfony 7                                    │
│                                                             │
│  Doctrine Isolated (6)                                      │
│  ├── PHP 8.2 - Doctrine 2                                   │
│  ├── PHP 8.2 - Doctrine 3                                   │
│  ├── PHP 8.3 - Doctrine 2                                   │
│  ├── PHP 8.3 - Doctrine 3                                   │
│  ├── PHP 8.4 - Doctrine 2                                   │
│  └── PHP 8.4 - Doctrine 3                                   │
│                                                             │
│  E2E Tests (6)                                              │
│  ├── PHP 8.2 - Laravel E2E                                  │
│  ├── PHP 8.2 - Symfony E2E                                  │
│  ├── PHP 8.3 - Laravel E2E                                  │
│  ├── PHP 8.3 - Symfony E2E                                  │
│  ├── PHP 8.4 - Laravel E2E                                  │
│  └── PHP 8.4 - Symfony E2E                                  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Workflow Status

You can check the status of all workflows in the GitHub Actions tab:

```
https://github.com/event4u-app/data-helpers/actions
```

---

## Running Tests Locally

To run the same tests locally:

```bash
# Complete test suite (like CI)
task test:run

# Test matrix only
task test:matrix

# Specific framework
task test:matrix:laravel

# Specific version
task test:laravel11

# E2E tests
task test:e2e
```

See [docs/TEST-MATRIX.md](../../docs/TEST-MATRIX.md) for more details.

---

## Troubleshooting

### Failed Tests

1. Check the workflow run in GitHub Actions
2. Identify which specific test failed
3. Run that test locally:
   ```bash
   task test:laravel11
   ```
4. Fix the issue
5. Push the fix

### Workflow Not Triggering

Check that your changes match the `paths` filter in the workflow:
- `**.php` - PHP files
- `composer.json` - Composer config
- `composer.lock` - Composer lock
- `phpunit.xml` - PHPUnit config
- `scripts/test-isolated.sh` - Test script
- `.github/workflows/test-matrix.yml` - Workflow file

### Manual Trigger

You can manually trigger any workflow:
1. Go to Actions tab
2. Select the workflow
3. Click "Run workflow"
4. Select branch
5. Click "Run workflow"

---

## Performance

- **Plain PHP tests**: ~1-2 minutes per PHP version
- **Isolated framework tests**: ~2-3 minutes per test
- **E2E tests**: ~3-4 minutes per test
- **Total matrix**: ~30-40 minutes (parallel execution)

---

## Best Practices

1. **Always run tests locally before pushing**
   ```bash
   task test:matrix
   ```

2. **Check quality before pushing**
   ```bash
   task quality:check
   ```

3. **Use pre-commit hooks**
   ```bash
   task dev:pre-commit
   ```

4. **Monitor CI status**
   - Check GitHub Actions after pushing
   - Fix failures immediately
   - Don't merge PRs with failing tests

---

## Summary

The CI setup ensures:
- ✅ **Framework independence** - Each framework tested in isolation
- ✅ **Comprehensive coverage** - 27 test jobs across all combinations
- ✅ **Real-world scenarios** - Tests mimic actual usage
- ✅ **Fast feedback** - Parallel execution
- ✅ **Quality assurance** - Automated code quality checks

