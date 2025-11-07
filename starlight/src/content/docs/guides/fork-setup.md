---
title: Fork Setup
description: Create and configure your fork of Data Helpers
---

Learn how to create your own fork of Data Helpers and use it in your projects.

## What is a Fork?

A fork is your personal copy of the repository on GitHub. You have full control over your fork and can make any changes without affecting the original repository.

**Benefits of forking:**
- ✅ Make custom changes for your organization
- ✅ Test changes before contributing back
- ✅ Keep your modifications separate from upstream
- ✅ Use specific versions in your projects

## Creating Your Fork

### Step 1: Fork on GitHub

1. **Go to the repository:**
   ```
   https://github.com/event4u-app/data-helpers
   ```

2. **Click the "Fork" button** in the top-right corner

3. **Select your account** as the destination

4. **Wait for GitHub to create your fork**
   - GitHub will redirect you to your fork: `https://github.com/YOUR-USERNAME/data-helpers`

✅ **Done!** You now have your own copy of the repository.

### Step 2: Clone Your Fork

```bash
# Clone your fork (replace YOUR-USERNAME with your GitHub username)
git clone git@github.com:YOUR-USERNAME/data-helpers.git

# Navigate into the directory
cd data-helpers
```

### Step 3: Verify the Remote

```bash
# Check the remote URL
git remote -v
```

**Output:**
```
origin  git@github.com:YOUR-USERNAME/data-helpers.git (fetch)
origin  git@github.com:YOUR-USERNAME/data-helpers.git (push)
```

✅ **Done!** Your fork is now on your local machine.

### Step 4: Add Upstream Remote

The "upstream" remote points to the original repository. This allows you to pull updates from the original repository into your fork.

```bash
# Add the original repository as "upstream"
git remote add upstream git@github.com:event4u-app/data-helpers.git

# Verify both remotes
git remote -v
```

**Output:**
```
origin    git@github.com:YOUR-USERNAME/data-helpers.git (fetch)
origin    git@github.com:YOUR-USERNAME/data-helpers.git (push)
upstream  git@github.com:event4u-app/data-helpers.git (fetch)
upstream  git@github.com:event4u-app/data-helpers.git (push)
```

✅ **Done!** You can now pull updates from the original repository.

## Using Your Fork in Projects

Once you have your fork set up, you can use it in your projects instead of the original package.

### Configuration

Add this to your project's `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/YOUR-USERNAME/data-helpers"
    }
  ],
  "require": {
    "event4u-app/data-helpers": "dev-main"
  }
}
```

Then run:
```bash
composer update event4u-app/data-helpers
```

**Important Notes:**
- Replace `YOUR-USERNAME` with your GitHub username or organization
- The package name stays `event4u-app/data-helpers` (from the original `composer.json`)
- Composer will use your fork's repository instead of Packagist
- No Packagist registration needed
- GitHub Releases are not required (Composer only uses Git tags)

### Version Options

#### Option 1: Latest Development Version (`dev-main`)

```json
{
  "require": {
    "event4u-app/data-helpers": "dev-main"
  }
}
```

- ✅ Always uses the latest code from your fork's `main` branch
- ✅ No tags required
- ⚠️ May include unreleased changes
- 💡 Best for: Development, testing new features

**Example:**
```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/YOUR-USERNAME/data-helpers"
    }
  ],
  "require": {
    "event4u-app/data-helpers": "dev-main"
  }
}
```

#### Option 2: Semantic Versioning (`^1.7`)

```json
{
  "require": {
    "event4u-app/data-helpers": "^1.7"
  }
}
```

- ✅ Uses semantic versioning (allows `1.7.0`, `1.7.1`, `1.8.0`, but not `2.0.0`)
- ✅ Requires synced tags from upstream
- ✅ More stable for production
- 💡 Best for: Production environments

**Example:**
```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/YOUR-USERNAME/data-helpers"
    }
  ],
  "require": {
    "event4u-app/data-helpers": "^1.7"
  }
}
```

#### Option 3: Exact Version (`1.7.5`)

```json
{
  "require": {
    "event4u-app/data-helpers": "1.7.5"
  }
}
```

- ✅ Locks to exact version
- ✅ Most stable option
- ✅ Requires synced tags from upstream
- 💡 Best for: Critical production systems

**Example:**
```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/YOUR-USERNAME/data-helpers"
    }
  ],
  "require": {
    "event4u-app/data-helpers": "1.7.5"
  }
}
```

#### Option 4: Version Range (`>=1.7 <2.0`)

```json
{
  "require": {
    "event4u-app/data-helpers": ">=1.7 <2.0"
  }
}
```

- ✅ Flexible version range
- ✅ Requires synced tags from upstream
- 💡 Best for: When you need specific version boundaries

**Example:**
```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/YOUR-USERNAME/data-helpers"
    }
  ],
  "require": {
    "event4u-app/data-helpers": ">=1.7 <2.0"
  }
}
```

#### Option 5: Tilde Version (`~1.7.0`)

```json
{
  "require": {
    "event4u-app/data-helpers": "~1.7.0"
  }
}
```

- ✅ Allows patch updates only (e.g., `1.7.0`, `1.7.1`, `1.7.2`, but not `1.8.0`)
- ✅ Requires synced tags from upstream
- 💡 Best for: When you want bug fixes but no new features

**Example:**
```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/YOUR-USERNAME/data-helpers"
    }
  ],
  "require": {
    "event4u-app/data-helpers": "~1.7.0"
  }
}
```

## Next Steps

- [Fork Sync](/data-helpers/guides/fork-sync/) - Keep your fork up-to-date with upstream
- [Pull Requests](/data-helpers/guides/pull-requests/) - Contribute changes back to upstream
- [Development Setup](/data-helpers/guides/development-setup/) - Setup your development environment

