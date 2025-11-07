---
title: Fork Sync
description: Keep your fork up-to-date with upstream changes
---

Learn how to keep your fork synchronized with the upstream repository to receive the latest updates, bug fixes, and features.

## Why Sync Your Fork?

The original repository receives updates from other contributors. You need to sync your fork to:

- ✅ Get the latest features and bug fixes
- ✅ Stay compatible with upstream changes
- ✅ Reduce merge conflicts when contributing
- ✅ Keep your fork's tags up-to-date

## Automatic Sync (Recommended)

The easiest way to keep your fork up-to-date is using the automated GitHub Actions workflow.

### Setup Automatic Sync

:::tip[Automatic Upstream Sync]
Your fork is automatically synchronized with upstream (code + tags) every hour via GitHub Actions workflow.

**Setup required** (one-time):
1. Create a Personal Access Token:
   - Go to [GitHub Settings → Developer settings → Personal access tokens → Tokens (classic)](https://github.com/settings/tokens)
   - Click **Generate new token (classic)**
   - Name: `Data Helpers Sync`
   - Scopes: ✅ `repo` + ✅ `workflow`
   - Copy the token
2. Add to your fork:
   - Go to **Settings** → **Secrets and variables** → **Actions** → **Secrets**
   - Click **New repository secret**
   - Name: `DATA_HELPERS_PAT`
   - Paste your token
   - Click **Add secret**

**When it runs:**
- ✅ Automatically every hour
- ✅ Manually via Actions → "Sync Upstream"

**What it syncs:**
- ✅ Code changes from `upstream/main` are merged into your fork
- ✅ New tags from upstream are synced to your fork
- ⏭️ Existing tags in your fork are skipped (not overwritten)
- 🔒 Your custom tags remain untouched
- ⚠️ Merge conflicts require manual resolution
:::

### Manual Trigger

You can manually trigger the sync workflow:

1. Go to your fork on GitHub
2. Click **Actions** tab
3. Select **Sync Upstream** workflow
4. Click **Run workflow**
5. Click **Run workflow** button

## GitHub "Sync Fork" Button

GitHub provides a built-in "Sync fork" button for quick updates.

### How to Use

1. **Go to your fork on GitHub**
2. **Click "Sync fork"** (appears when your fork is behind)
3. **Click "Update branch"**

:::tip[Automatic Tag Sync]
When you use GitHub's "Sync fork" button, tags can be automatically synchronized from upstream to your fork via GitHub Actions workflow.

**Setup Required:** Same PAT as above (`DATA_HELPERS_PAT`)

**When It Runs**
- ✅ After clicking GitHub's "Sync fork" button (push to main branch)
- ✅ Manually via Actions → "Sync Upstream Tags"
- ⏭️ Only if `DATA_HELPERS_PAT` secret is configured (otherwise skipped)
- ⏭️ Skipped in the original repository (only runs in forks)

**What It Does**

1. Checks if this is a fork (skips if original repository)
2. Adds upstream remote (`https://github.com/event4u-app/data-helpers.git`)
3. Fetches all tags from upstream
4. Pushes new tags to your fork (skips existing tags)

**Behavior:**
- ✅ New tags from upstream are synced to your fork
- ⏭️ Existing tags in your fork are skipped (not overwritten)
- 🔒 Your custom tags remain untouched
:::

## Manual Git Commands

For more control, you can sync your fork manually using Git commands.

### Option 1: Using Taskfile (Recommended)

The project includes convenient Task commands:

```bash
# Update fork with latest changes from upstream
task fork:update

# Sync tags from upstream
task fork:sync-tags

# Complete fork sync (code + tags)
task fork:sync
```

### Option 2: Manual Git Commands

```bash
# Switch to main branch
git checkout main

# Fetch updates from upstream
git fetch upstream

# Merge upstream changes
git merge upstream/main

# Push to your fork
git push origin main

# Sync tags
git fetch upstream --tags
git push origin --tags
```

### Sync Specific Branch

```bash
# Fetch from upstream
git fetch upstream

# Merge specific branch
git merge upstream/dev-main

# Push to your fork
git push origin dev-main
```

## Keeping Feature Branches Updated

When working on a feature branch, you may need to sync it with the latest upstream changes.

### Update Feature Branch

```bash
# Switch to your feature branch
git checkout feature/my-feature

# Fetch latest from upstream
git fetch upstream

# Merge upstream/main into your feature branch
git merge upstream/main

# Resolve any conflicts if needed
# ... edit files ...
git add .
git commit -m "Merge upstream changes"

# Push updated branch
git push origin feature/my-feature
```

### Rebase Feature Branch (Alternative)

```bash
# Switch to your feature branch
git checkout feature/my-feature

# Fetch latest from upstream
git fetch upstream

# Rebase your branch on top of upstream/main
git rebase upstream/main

# Resolve any conflicts if needed
# ... edit files ...
git add .
git rebase --continue

# Force push (rebase rewrites history)
git push origin feature/my-feature --force
```

:::caution[Force Push Warning]
Only use `--force` on your own feature branches, never on shared branches like `main`.
:::

## Troubleshooting

### Merge Conflicts

If you get merge conflicts when syncing:

```bash
# 1. Identify conflicting files
git status

# 2. Edit conflicting files and resolve conflicts
# Look for conflict markers: <<<<<<<, =======, >>>>>>>

# 3. Stage resolved files
git add path/to/resolved/file.php

# 4. Complete the merge
git commit -m "Resolve merge conflicts with upstream"

# 5. Push to your fork
git push origin main
```

### Diverged Branches

If your fork has diverged significantly:

```bash
# Option 1: Reset to upstream (⚠️ loses your changes)
git fetch upstream
git reset --hard upstream/main
git push origin main --force

# Option 2: Create a backup first
git branch backup-main
git reset --hard upstream/main
git push origin main --force
```

### Failed Automatic Sync

If the automatic sync workflow fails:

1. Check the workflow run in **Actions** tab
2. Look for error messages
3. Common issues:
   - Merge conflicts (requires manual resolution)
   - Missing `DATA_HELPERS_PAT` secret
   - PAT expired or lacks required scopes

## Best Practices

### Regular Syncing

- ✅ Sync your fork regularly (at least weekly)
- ✅ Sync before starting new features
- ✅ Sync before creating pull requests
- ✅ Use automatic sync for hands-off maintenance

### Tag Management

- ✅ Keep tags synced with upstream
- ✅ Don't delete upstream tags
- ✅ Use custom tag prefixes for your tags (e.g., `custom-v1.0.0`)
- ✅ Document custom tags in your fork's README

### Branch Strategy

- ✅ Keep `main` branch clean (only upstream changes)
- ✅ Create feature branches for your changes
- ✅ Regularly sync feature branches with upstream
- ✅ Delete merged feature branches

## Summary

**Sync Methods:**

1. **Automatic (Recommended)** - GitHub Actions every hour
2. **GitHub Button** - Quick one-click sync
3. **Taskfile** - `task fork:sync`
4. **Manual Git** - Full control with Git commands

**What Gets Synced:**

- ✅ Code changes from `upstream/main`
- ✅ New tags from upstream
- ✅ Branch updates
- 🔒 Your custom changes remain safe

## Next Steps

- [Fork Setup](/data-helpers/guides/fork-setup/) - Initial fork configuration
- [Pull Requests](/data-helpers/guides/pull-requests/) - Contribute changes back to upstream
- [Taskfile Reference](/data-helpers/guides/taskfile-reference/) - All available Task commands

