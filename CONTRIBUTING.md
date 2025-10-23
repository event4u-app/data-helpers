# Contributing to Data Helpers

Thank you for considering contributing to Data Helpers! 🎉

## 📚 Complete Documentation

For detailed contributing guidelines, please visit our comprehensive documentation:

**👉 [Contributing Guide](https://event4u-app.github.io/data-helpers/guides/contributing/)**

## 🚀 Quick Start

### New to Forking?

If you're new to the fork and pull request workflow, check out our step-by-step guide:

**👉 [Fork & Pull Request Guide](https://event4u-app.github.io/data-helpers/guides/fork-and-pull-request/)**

### Prerequisites

- PHP 8.2 or higher
- Composer
- Git
- Docker & Docker Compose (recommended)
- Task (optional but recommended)

### Quick Setup

```bash
# 1. Fork the repository on GitHub
# 2. Clone your fork
git clone git@github.com:YOUR_USERNAME/data-helpers.git
cd data-helpers

# 3. Setup development environment
task dev:setup

# 4. Run tests
task test:run
```

## 🧪 Testing

```bash
# Run all tests
task test:run

# Run unit tests only
task test:unit

# Run complete test matrix
task test:matrix
```

**Learn more:** [Test Matrix Guide](https://event4u-app.github.io/data-helpers/guides/test-matrix/)

## ✨ Code Quality

```bash
# Fix code style
task quality:ecs:fix

# Run PHPStan
task quality:phpstan

# Run all quality checks
task quality:check
```

**Learn more:** [Development Setup](https://event4u-app.github.io/data-helpers/guides/development-setup/)

## 🔄 Development Workflow

```bash
# 1. Create a feature branch
git checkout -b feature/my-awesome-feature

# 2. Make changes and commit
git add .
git commit -m "feat: add new feature"

# 3. Run quality checks
task dev:pre-commit

# 4. Push and create PR
git push origin feature/my-awesome-feature
```

**Learn more:** [Fork & Pull Request Guide](https://event4u-app.github.io/data-helpers/guides/fork-and-pull-request/)

## 📚 Additional Resources

- **[Contributing Guide](https://event4u-app.github.io/data-helpers/guides/contributing/)** - Complete guidelines
- **[Fork & Pull Request Guide](https://event4u-app.github.io/data-helpers/guides/fork-and-pull-request/)** - Step-by-step workflow
- **[Development Setup](https://event4u-app.github.io/data-helpers/guides/development-setup/)** - Setup your environment
- **[Test Matrix](https://event4u-app.github.io/data-helpers/guides/test-matrix/)** - Learn about testing
- **[Taskfile Reference](https://event4u-app.github.io/data-helpers/guides/taskfile-reference/)** - All available commands

## 🤝 Getting Help

- **Documentation:** https://event4u-app.github.io/data-helpers/
- **Issues:** https://github.com/event4u-app/data-helpers/issues
- **Discussions:** https://github.com/event4u-app/data-helpers/discussions

---

Thank you for contributing to Data Helpers! 🎉

