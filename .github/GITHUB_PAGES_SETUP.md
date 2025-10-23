# GitHub Pages Setup

## Overview

The documentation is automatically deployed to GitHub Pages when you push to the `main` branch.

## One-Time Setup

### 1. Enable GitHub Pages

1. Go to your repository on GitHub
2. Click on **Settings**
3. Scroll to **Pages** in the left menu
4. Under **Source** select:
   - Source: **GitHub Actions**
5. Click **Save**

### 2. Set Workflow Permissions

1. Go to **Settings** → **Actions** → **General**
2. Scroll to **Workflow permissions**
3. Select: **Read and write permissions**
4. Enable: **Allow GitHub Actions to create and approve pull requests**
5. Click **Save**

## How It Works

### On Push to `main`:
1. ✅ Documentation is built
2. ✅ Artifact is created (available for 30 days)
3. ✅ **Automatic deployment to GitHub Pages**
4. ✅ Documentation is **permanently** available

### On Pull Requests:
1. ✅ Documentation is built (test)
2. ✅ Artifact is created
3. ❌ **NO** deployment to GitHub Pages

## URLs

After the first deployment, the documentation is available at:

```
https://<username>.github.io/<repository-name>/
```

For this repository:
```
https://event4u-app.github.io/data-helpers/
```

## Availability

### Artifact (temporary):
- ⏱️  Available for **30 days**
- 🔒 Only for repository members
- 📥 Must be downloaded manually

### GitHub Pages (permanent):
- ♾️  **Permanently** available (as long as the repo exists)
- 🌐 **Publicly** accessible
- 🔄 Automatically updated on every push to `main`
- ✅ Available even after 300+ days

## Check Deployment Status

1. Go to the **Actions** tab
2. Select the latest workflow run
3. View the jobs:
   - `build-docs` - Builds the documentation
   - `deploy-pages` - Deploys to GitHub Pages

## Local Preview

Before pushing, you can build the documentation locally:

```bash
# Build and save to dist/
task docs:build:dist

# Start preview
task docs:preview
```

## Troubleshooting

### "Pages deployment failed"

**Problem:** GitHub Pages is not enabled or permissions are missing.

**Solution:**
1. Enable GitHub Pages (see above)
2. Set workflow permissions (see above)
3. Push again

### "404 Not Found" after deployment

**Problem:** The page is not yet available.

**Solution:**
- Wait 1-2 minutes after deployment
- Clear browser cache (Ctrl+F5)
- Check the URL

### Deployment is skipped

**Problem:** The `deploy-pages` job doesn't run.

**Reason:** Deployment only occurs on push to `main`, not on PRs.

**Solution:**
- For PRs: Normal behavior, only build is tested
- For push to `main`: Check if the branch is correct

## Workflow Structure

```yaml
┌─────────────────────────────────────────────────────────┐
│                    Push to main                         │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  Job 1: build-docs                                      │
│  ├─ Checkout code                                       │
│  ├─ Setup Node.js                                       │
│  ├─ Install dependencies                                │
│  ├─ Build documentation                                 │
│  ├─ Copy to dist/                                       │
│  └─ Upload artifact (30 days)                           │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  Job 2: deploy-pages (only on main)                     │
│  ├─ Download artifact                                   │
│  ├─ Setup Pages                                         │
│  ├─ Upload Pages artifact                               │
│  └─ Deploy to GitHub Pages ✅                           │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  Documentation live at:                                 │
│  https://event4u-app.github.io/data-helpers/            │
│  ♾️  Permanently available                              │
└─────────────────────────────────────────────────────────┘
```

## Summary

✅ **Automatic:** Deployment happens automatically on push to `main`
✅ **Permanent:** Documentation stays online (even after 300+ days)
✅ **Public:** Anyone can read the documentation
✅ **Up-to-date:** Always the latest version from the `main` branch
✅ **No push needed:** No commits of dist/ files required

## Further Information

- [GitHub Pages Documentation](https://docs.github.com/en/pages)
- [GitHub Actions for Pages](https://github.com/actions/deploy-pages)
- [Astro Deployment Guide](https://docs.astro.build/en/guides/deploy/github/)

