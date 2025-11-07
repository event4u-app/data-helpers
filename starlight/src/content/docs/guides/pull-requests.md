---
title: Pull Requests
description: Contribute changes back to the upstream repository
---

Learn how to create pull requests to contribute your changes back to the Data Helpers repository.

## When to Create a Pull Request

Create a pull request when you want to:

- ✅ Add a new feature
- ✅ Fix a bug
- ✅ Improve documentation
- ✅ Refactor code
- ✅ Add or improve tests

## Prerequisites

Before creating a pull request:

1. ✅ You have a fork of the repository
2. ✅ Your fork is up-to-date with upstream
3. ✅ You have set up your development environment
4. ✅ You understand the project's coding standards

See [Fork Setup](/data-helpers/guides/fork-setup/) and [Fork Sync](/data-helpers/guides/fork-sync/) for details.

## Step-by-Step Guide

### Step 1: Setup Development Environment

```bash
# Install Composer dependencies
composer install

# Install Task (if not already installed)
# See: https://taskfile.dev/installation/

# Verify setup
task --list
```

### Step 2: Create a Feature Branch

Always create a new branch for your changes. This keeps your `main` branch clean and makes it easier to work on multiple features simultaneously.

```bash
# Update your fork first
task fork:update

# Create and switch to a new branch
git checkout -b feature/my-awesome-feature
```

**Branch Naming Conventions:**
- `feature/` - New features
- `fix/` - Bug fixes
- `docs/` - Documentation changes
- `refactor/` - Code refactoring
- `test/` - Test additions or changes

### Step 3: Make Your Changes

Edit files using your favorite editor:

```bash
# Example: Edit a file
vim src/DataAccessor.php

# Example: Add a new test
vim tests/Unit/DataAccessorTest.php
```

### Step 4: Test Your Changes

**Run all tests:**
```bash
# Using Task
task test:run

# Or directly with Pest
vendor/bin/pest
```

**Run specific tests:**
```bash
# Test specific file
vendor/bin/pest tests/Unit/DataAccessorTest.php

# Test specific method
vendor/bin/pest --filter testGetMethod
```

**Check code style:**
```bash
# Check code style
task quality:ecs:check

# Fix code style automatically
task quality:ecs:fix
```

**Run static analysis:**
```bash
task quality:phpstan
```

### Step 5: Commit Your Changes

**Stage your changes:**
```bash
# Stage all changes
git add .

# Or stage specific files
git add src/DataAccessor.php tests/Unit/DataAccessorTest.php
```

**Commit with a descriptive message:**
```bash
git commit -m "feat: add support for wildcard iteration"
```

**Commit Message Format:**
- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation changes
- `refactor:` - Code refactoring
- `test:` - Test changes
- `chore:` - Maintenance tasks

**Examples:**
```bash
git commit -m "feat: add DataFilter::whereIn() method"
git commit -m "fix: resolve null pointer in DataAccessor::get()"
git commit -m "docs: update installation instructions"
git commit -m "test: add tests for wildcard iteration"
```

### Step 6: Push to Your Fork

```bash
# Push your branch to your fork
git push origin feature/my-awesome-feature
```

**First time pushing a new branch:**
```bash
# Git will suggest setting upstream
git push --set-upstream origin feature/my-awesome-feature

# Or use the shorthand
git push -u origin feature/my-awesome-feature
```

### Step 7: Create the Pull Request

1. **Go to your fork on GitHub:**
   ```
   https://github.com/YOUR-USERNAME/data-helpers
   ```

2. **GitHub will show a banner** suggesting to create a pull request

3. **Click "Compare & pull request"**

4. **Fill in the PR details:**
   - **Title:** Clear, descriptive title
   - **Description:** Use the template below
   - **Base:** `event4u-app/data-helpers` `main`
   - **Compare:** `YOUR-USERNAME/data-helpers` `feature/my-awesome-feature`

5. **Click "Create pull request"**

### PR Description Template

```markdown
## Description
Brief description of what this PR does.

## Changes
- Added feature X
- Fixed bug Y
- Updated documentation Z

## Testing
- [ ] All tests pass
- [ ] Added new tests for new features
- [ ] Manually tested the changes

## Related Issues
Fixes #123
```

## Responding to Review Feedback

### Make Requested Changes

```bash
# Make the requested changes
vim src/DataAccessor.php

# Test your changes
task test:run

# Commit the changes
git add .
git commit -m "fix: address review feedback"

# Push to update the PR
git push origin feature/my-awesome-feature
```

**The PR updates automatically** when you push to the same branch.

### Amend Last Commit (Optional)

If you want to keep a clean commit history:

```bash
# Make changes
vim src/DataAccessor.php

# Stage changes
git add .

# Amend the last commit
git commit --amend --no-edit

# Force push (rewrites history)
git push origin feature/my-awesome-feature --force
```

:::caution[Force Push Warning]
Only use `--force` on your own feature branches, never on shared branches.
:::

## After Your PR is Merged

### Clean Up Your Branch

```bash
# Switch back to main
git checkout main

# Update your fork
task fork:update

# Delete the feature branch locally
git branch -d feature/my-awesome-feature

# Delete the feature branch on your fork
git push origin --delete feature/my-awesome-feature
```

### Start a New Feature

```bash
# Make sure main is up-to-date
task fork:update

# Create a new feature branch
git checkout -b feature/next-awesome-feature
```

## Common Workflows

### Starting a New Feature

**Using Task commands:**
```bash
# 1. Update fork
task fork:update

# 2. Create feature branch
git checkout -b feature/my-feature

# 3. Make changes and test
task test:run

# 4. Commit and push
git add .
git commit -m "feat: add new feature"
git push -u origin feature/my-feature

# 5. Create PR on GitHub
```

### Updating an Existing PR

```bash
# 1. Make changes
vim src/SomeFile.php

# 2. Test
task test:run

# 3. Commit and push
git add .
git commit -m "fix: address review comments"
git push origin feature/my-feature
```

### Syncing PR with Upstream

If upstream has changed while your PR is open:

```bash
# 1. Fetch upstream
git fetch upstream

# 2. Merge upstream/main into your branch
git merge upstream/main

# 3. Resolve conflicts if any
# ... edit files ...
git add .
git commit -m "Merge upstream changes"

# 4. Push (PR updates automatically)
git push origin feature/my-feature
```

## Best Practices

### Before Creating a PR

- ✅ Sync your fork with upstream
- ✅ Create a feature branch (don't use `main`)
- ✅ Write clear, descriptive commit messages
- ✅ Run all tests and ensure they pass
- ✅ Fix code style issues
- ✅ Update documentation if needed

### PR Content

- ✅ Keep PRs focused (one feature/fix per PR)
- ✅ Write a clear description
- ✅ Reference related issues
- ✅ Include tests for new features
- ✅ Update documentation
- ✅ Keep commits clean and logical

### During Review

- ✅ Respond to feedback promptly
- ✅ Be open to suggestions
- ✅ Ask questions if unclear
- ✅ Keep the PR updated with upstream
- ✅ Mark conversations as resolved

### After Merge

- ✅ Delete your feature branch
- ✅ Update your fork
- ✅ Celebrate! 🎉

## Troubleshooting

### PR Has Conflicts

```bash
# 1. Update your branch with upstream
git fetch upstream
git merge upstream/main

# 2. Resolve conflicts
# ... edit files ...
git add .
git commit -m "Resolve merge conflicts"

# 3. Push
git push origin feature/my-feature
```

### Tests Failing in CI

1. Check the CI logs in the PR
2. Run tests locally: `task test:run`
3. Fix the issues
4. Commit and push

### Code Style Issues

```bash
# Fix automatically
task quality:ecs:fix

# Commit the fixes
git add .
git commit -m "style: fix code style"
git push origin feature/my-feature
```

## Summary

**Complete workflow:**

1. ✅ Setup development environment
2. ✅ Create feature branch
3. ✅ Make changes
4. ✅ Test thoroughly
5. ✅ Commit with clear messages
6. ✅ Push to your fork
7. ✅ Create pull request
8. ✅ Respond to feedback
9. ✅ Clean up after merge

**Quick commands:**
```bash
# Update fork
task fork:update

# Create branch
git checkout -b feature/my-feature

# Test
task test:run

# Fix code style
task quality:ecs:fix

# Commit and push
git add .
git commit -m "feat: add feature"
git push -u origin feature/my-feature
```

## Next Steps

- [Contributing Guide](/data-helpers/guides/contributing/) - Learn about code style and testing
- [Development Setup](/data-helpers/guides/development-setup/) - Setup your development environment
- [Test Matrix](/data-helpers/guides/test-matrix/) - Learn about testing
- [Taskfile Reference](/data-helpers/guides/taskfile-reference/) - All available Task commands

