# Data Helpers Documentation

This directory contains the documentation for Data Helpers, built with [Starlight](https://starlight.astro.build/) and the [Rapide theme](https://starlight-theme-rapide.vercel.app/).

## Development

All commands are run using Task from the project root:

```bash
# Start development server (http://localhost:4321)
task docs:dev

# Build for production
task docs:build

# Preview production build
task docs:preview

# Check for errors
task docs:check

# Show all available commands
task docs:help
```

## Project Structure

```
documentation/
├── src/
│   ├── content/
│   │   └── docs/           # Documentation pages (Markdown)
│   ├── assets/             # Images and static assets
│   └── styles/             # Custom CSS
├── public/                 # Static files
├── astro.config.mjs        # Astro configuration
└── package.json            # Dependencies
```

## Writing Documentation

Documentation pages are written in Markdown with frontmatter:

```markdown
---
title: Page Title
description: Page description for SEO
---

# Content goes here
```

## Adding Pages

1. Create a new `.md` file in `src/content/docs/`
2. Add frontmatter with title and description
3. Update `astro.config.mjs` sidebar if needed
4. Write content in Markdown

## Docker

All commands run in Docker containers (Node.js 18 Alpine), so you don't need Node.js installed locally.

## More Information

- [Starlight Documentation](https://starlight.astro.build/)
- [Rapide Theme](https://starlight-theme-rapide.vercel.app/)
- [Astro Documentation](https://docs.astro.build/)

