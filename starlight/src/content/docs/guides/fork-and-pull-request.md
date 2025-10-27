---
title: Fork & Pull Request Guide
description: Step-by-step guide for forking, working locally, and creating pull requests
---

Complete step-by-step guide for contributing to Data Helpers via fork and pull request workflow.

## Introduction

This guide covers the complete workflow:

1. **Fork** - Create your own copy of the repository
2. **Clone** - Download your fork to your local machine
3. **Work** - Make changes and commit them
4. **Push** - Upload your changes to your fork
5. **Pull Request** - Request to merge your changes into the original repository
6. **Sync** - Keep your fork up-to-date with the original repository

## Step 1: Fork the Repository

### What is a Fork?

A fork is your personal copy of the repository on GitHub. You have full control over your fork and can make any changes without affecting the original repository.

### How to Fork

1. **Go to the repository:**
   ```
   https://github.com/event4u-app/data-helpers
   ```

2. **Click the "Fork" button** in the top-right corner

3. **Select your account** as the destination

4. **Wait for GitHub to create your fork**
   - GitHub will redirect you to your fork: `https://github.com/YOUR_USERNAME/data-helpers`

✅ **Done!** You now have your own copy of the repository.

## Step 2: Clone Your Fork

### Clone to Your Local Machine

```bash
# Clone your fork (replace YOUR_USERNAME with your GitHub username)
git clone git@github.com:YOUR_USERNAME/data-helpers.git

# Navigate into the directory
cd data-helpers
```

### Verify the Remote

```bash
# Check the remote URL
git remote -v
```

**Output:**
```
origin  git@github.com:YOUR_USERNAME/data-helpers.git (fetch)
origin  git@github.com:YOUR_USERNAME/data-helpers.git (push)
```

✅ **Done!** Your fork is now on your local machine.

## Step 3: Add Upstream Remote

### What is Upstream?

The "upstream" remote points to the original repository. This allows you to pull updates from the original repository into your fork.

### Add Upstream Remote

```bash
# Add the original repository as "upstream"
git remote add upstream git@github.com:event4u-app/data-helpers.git

# Verify both remotes
git remote -v
```

**Output:**
```
origin    git@github.com:YOUR_USERNAME/data-helpers.git (fetch)
origin    git@github.com:YOUR_USERNAME/data-helpers.git (push)
upstream  git@github.com:event4u-app/data-helpers.git (fetch)
upstream  git@github.com:event4u-app/data-helpers.git (push)
```

✅ **Done!** You can now pull updates from the original repository.

## Step 4: Setup Development Environment

### Install Dependencies

```bash
# Install Composer dependencies
composer install

# Or using Task (if Docker is available)
task install
```

### Start Docker Containers (Optional)

```bash
# Start Docker containers
task docker:up

# Verify containers are running
task docker:ps
```

### Run Tests

```bash
# Run tests to ensure everything works
task test:run

# Or without Docker
vendor/bin/pest
```

✅ **Done!** Your development environment is ready.

## Step 5: Create a Feature Branch

### Why a Feature Branch?

Always create a new branch for your changes. This keeps your `main` branch clean and makes it easier to work on multiple features simultaneously.

### Create and Switch to a New Branch

```bash
# Create and switch to a new branch
git checkout -b feature/my-awesome-feature

# Or for bug fixes
git checkout -b fix/bug-description
```

**Branch naming conventions:**
- `feature/` - New features
- `fix/` - Bug fixes
- `docs/` - Documentation changes
- `refactor/` - Code refactoring
- `test/` - Test additions or changes

✅ **Done!** You're now on your feature branch.

## Step 6: Make Your Changes

### Edit Files

Make your changes using your favorite editor:

```bash
# Example: Edit a file
vim src/DataMapper/DataMapper.php

# Or open in your IDE
code .
```

### Check Your Changes

```bash
# See which files you've changed
git status

# See the actual changes
git diff
```

### Run Quality Checks

```bash
# Fix code style
task quality:ecs:fix

# Run PHPStan
task quality:phpstan

# Run tests
task test:run
```

✅ **Done!** Your changes are ready to commit.

## Step 7: Commit Your Changes

### Stage Your Changes

```bash
# Stage all changes
git add .

# Or stage specific files
git add src/DataMapper/DataMapper.php
git add tests/Unit/DataMapper/DataMapperTest.php
```

### Commit with a Good Message

```bash
# Commit with a descriptive message
git commit -m "feat: add support for nested wildcard mapping"
```

**Commit message format (Conventional Commits):**
- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation changes
- `style:` - Code style changes (formatting)
- `refactor:` - Code refactoring
- `test:` - Test changes
- `chore:` - Build/tooling changes

**Examples:**
```bash
git commit -m "feat: add support for nested wildcard mapping"
git commit -m "fix: resolve issue with null values in DataAccessor"
git commit -m "docs: update DataMapper examples"
git commit -m "test: add tests for wildcard iteration"
```

✅ **Done!** Your changes are committed locally.

## Step 8: Push to Your Fork

### Push Your Branch

```bash
# Push your branch to your fork
git push origin feature/my-awesome-feature
```

**First time pushing this branch?** Git will show:
```
To github.com:YOUR_USERNAME/data-helpers.git
 * [new branch]      feature/my-awesome-feature -> feature/my-awesome-feature
```

### Push Subsequent Changes

```bash
# Make more changes
git add .
git commit -m "feat: improve error handling"

# Push again
git push origin feature/my-awesome-feature
```

✅ **Done!** Your changes are now on GitHub in your fork.

## Step 9: Create a Pull Request

### Open Pull Request on GitHub

1. **Go to your fork on GitHub:**
   ```
   https://github.com/YOUR_USERNAME/data-helpers
   ```

2. **GitHub will show a banner:**
   ```
   feature/my-awesome-feature had recent pushes
   [Compare & pull request]
   ```

3. **Click "Compare & pull request"**

4. **Fill in the PR details:**
   - **Title:** Clear, descriptive title (e.g., "Add support for nested wildcard mapping")
   - **Description:** Explain what you changed and why
   - **Reference issues:** If fixing an issue, mention it (e.g., "Fixes #123")

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

✅ **Done!** Your pull request is created and ready for review.

## Step 10: Respond to Review Feedback

### Make Requested Changes

```bash
# Make the requested changes
vim src/DataMapper/DataMapper.php

# Commit the changes
git add .
git commit -m "fix: address review feedback"

# Push to your branch
git push origin feature/my-awesome-feature
```

**The PR will automatically update** with your new commits!

### Squash Commits (Optional)

If you have many small commits, you might want to squash them:

```bash
# Interactive rebase (last 3 commits)
git rebase -i HEAD~3

# In the editor, change "pick" to "squash" for commits you want to combine
# Save and exit

# Force push (only do this on your own branch!)
git push origin feature/my-awesome-feature --force
```

✅ **Done!** Your PR is updated with the requested changes.

## Step 11: Keep Your Fork Up-to-Date

### Why Sync Your Fork?

The original repository receives updates from other contributors. You need to sync your fork to stay up-to-date.

### Sync Your Fork

```bash
# Switch to main branch
git checkout main

# Fetch updates from upstream
git fetch upstream

# Merge upstream changes into your main branch
git merge upstream/main

# Push updates to your fork
git push origin main
```

### Update Your Feature Branch

```bash
# Switch to your feature branch
git checkout feature/my-awesome-feature

# Merge main into your feature branch
git merge main

# Or rebase (cleaner history)
git rebase main

# Push updates
git push origin feature/my-awesome-feature
```

✅ **Done!** Your fork is up-to-date with the original repository.

## Common Workflows

### Starting a New Feature

```bash
# 1. Update your main branch
git checkout main
git fetch upstream
git merge upstream/main
git push origin main

# 2. Create a new feature branch
git checkout -b feature/new-feature

# 3. Make changes, commit, and push
git add .
git commit -m "feat: add new feature"
git push origin feature/new-feature

# 4. Create PR on GitHub
```

### Fixing a Bug

```bash
# 1. Update your main branch
git checkout main
git fetch upstream
git merge upstream/main

# 2. Create a fix branch
git checkout -b fix/bug-description

# 3. Fix the bug, add tests
git add .
git commit -m "fix: resolve bug description"

# 4. Run tests
task test:run

# 5. Push and create PR
git push origin fix/bug-description
```

### Updating an Existing PR

```bash
# 1. Switch to your feature branch
git checkout feature/my-feature

# 2. Make changes
git add .
git commit -m "fix: address review feedback"

# 3. Push (PR updates automatically)
git push origin feature/my-feature
```

## Troubleshooting

### Merge Conflicts

If you get merge conflicts when syncing:

```bash
# 1. Try to merge
git merge upstream/main

# 2. Git will show conflicts
# CONFLICT (content): Merge conflict in src/DataMapper/DataMapper.php

# 3. Open the file and resolve conflicts
vim src/DataMapper/DataMapper.php

# 4. Look for conflict markers
<<<<<<< HEAD
Your changes
=======
Upstream changes
>>>>>>> upstream/main

# 5. Edit the file to keep the correct code

# 6. Stage the resolved file
git add src/DataMapper/DataMapper.php

# 7. Complete the merge
git commit

# 8. Push
git push origin main
```

### Accidentally Committed to Main

```bash
# 1. Create a new branch from current state
git checkout -b feature/my-feature

# 2. Push the new branch
git push origin feature/my-feature

# 3. Reset main to upstream
git checkout main
git reset --hard upstream/main
git push origin main --force
```

### Need to Undo Last Commit

```bash
# Undo last commit but keep changes
git reset --soft HEAD~1

# Undo last commit and discard changes
git reset --hard HEAD~1
```

## Summary

**Complete workflow:**

1. ✅ Fork the repository on GitHub
2. ✅ Clone your fork locally
3. ✅ Add upstream remote
4. ✅ Create a feature branch
5. ✅ Make changes and commit
6. ✅ Push to your fork
7. ✅ Create a pull request
8. ✅ Respond to feedback
9. ✅ Keep your fork synced

## Next Steps

- [Contributing Guide](/data-helpers/guides/contributing/) - Learn about code style and testing
- [Development Setup](/data-helpers/guides/development-setup/) - Setup your development environment
- [Test Matrix](/data-helpers/guides/test-matrix/) - Learn about testing

