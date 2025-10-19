# SimpleDTO Documentation

**Version:** 3.0 (Phase 17 Complete)  
**PHP:** 8.2+  
**Framework:** Framework-agnostic (Laravel, Symfony, Plain PHP)

---

## 📚 Table of Contents

### Getting Started
- [Introduction](01-introduction.md) - What is SimpleDTO and why use it?
- [Installation](02-installation.md) - How to install and configure
- [Quick Start](03-quick-start.md) - Your first DTO in 5 minutes
- [Basic Usage](04-basic-usage.md) - Core features and concepts

### Core Features
- [Creating DTOs](05-creating-dtos.md) - Different ways to create DTOs
- [Type Casting](06-type-casting.md) - Built-in and custom casts
- [Validation](07-validation.md) - Auto rule inferring and validation
- [Property Mapping](08-property-mapping.md) - MapFrom, MapTo, transformers
- [Serialization](09-serialization.md) - toArray(), toJson(), XML, YAML

### Advanced Features
- [Conditional Properties](10-conditional-properties.md) - 18 conditional attributes
- [with() Method](11-with-method.md) - Dynamic property addition
- [Context-Based Conditions](12-context-based-conditions.md) - Context-aware properties
- [Lazy Properties](13-lazy-properties.md) - Lazy loading and evaluation
- [Computed Properties](14-computed-properties.md) - Calculated properties
- [Collections](15-collections.md) - DataCollection and pagination
- [Nested DTOs](16-nested-dtos.md) - Working with nested structures

### Framework Integration
- [Laravel Integration](17-laravel-integration.md) - Eloquent, validation, commands
- [Symfony Integration](18-symfony-integration.md) - Doctrine, security, validation
- [Plain PHP](19-plain-php.md) - Using without frameworks

### Validation & Security
- [Validation Attributes](20-validation-attributes.md) - All validation attributes
- [Custom Validation](21-custom-validation.md) - Creating custom rules
- [Security & Visibility](22-security-visibility.md) - Hidden properties, sanitization

### Developer Tools
- [TypeScript Generation](23-typescript-generation.md) - Generate TypeScript types
- [IDE Support](24-ide-support.md) - PHPStorm, VS Code integration
- [Artisan Commands](25-artisan-commands.md) - Laravel commands
- [Console Commands](26-console-commands.md) - Symfony commands

### Performance & Optimization
- [Performance](27-performance.md) - Benchmarks and optimization
- [Caching](28-caching.md) - Validation and cast caching
- [Best Practices](29-best-practices.md) - Tips and recommendations

### Migration & Comparison
- [Migration from Spatie](30-migration-from-spatie.md) - Step-by-step guide
- [Comparison with Spatie](31-comparison-with-spatie.md) - Feature comparison
- [Troubleshooting](32-troubleshooting.md) - Common issues and solutions

### API Reference
- [Attributes Reference](33-attributes-reference.md) - All attributes
- [Casts Reference](34-casts-reference.md) - All casts
- [Traits Reference](35-traits-reference.md) - All traits
- [Interfaces Reference](36-interfaces-reference.md) - All interfaces

### Examples & Recipes
- [Real-World Examples](37-real-world-examples.md) - Production examples
- [API Resources](38-api-resources.md) - REST API examples
- [Form Requests](39-form-requests.md) - Form handling examples
- [Testing DTOs](40-testing-dtos.md) - Unit and integration tests

---

## 🚀 Quick Links

### Most Popular Topics
1. [Quick Start Guide](03-quick-start.md) - Get started in 5 minutes
2. [Conditional Properties](10-conditional-properties.md) - 18 powerful attributes
3. [Laravel Integration](17-laravel-integration.md) - Laravel-specific features
4. [Migration from Spatie](30-migration-from-spatie.md) - Switch from Spatie Data
5. [Best Practices](29-best-practices.md) - Write better DTOs

### Feature Highlights
- ✅ **18 Conditional Attributes** - 9x more than Spatie Data
- ✅ **Framework Agnostic** - Works with Laravel, Symfony, Plain PHP
- ✅ **3x Faster** - Performance optimized
- ✅ **95+ Tests** - Thoroughly tested
- ✅ **Zero Dependencies** - Core has no dependencies

---

## 📖 Documentation Structure

### Beginner Path
1. Read [Introduction](01-introduction.md)
2. Follow [Installation](02-installation.md)
3. Try [Quick Start](03-quick-start.md)
4. Learn [Basic Usage](04-basic-usage.md)
5. Explore [Creating DTOs](05-creating-dtos.md)

### Intermediate Path
1. Master [Type Casting](06-type-casting.md)
2. Learn [Validation](07-validation.md)
3. Understand [Property Mapping](08-property-mapping.md)
4. Explore [Conditional Properties](10-conditional-properties.md)
5. Use [with() Method](11-with-method.md)

### Advanced Path
1. Deep dive into [Context-Based Conditions](12-context-based-conditions.md)
2. Optimize with [Lazy Properties](13-lazy-properties.md)
3. Master [Collections](15-collections.md)
4. Integrate with [Laravel](17-laravel-integration.md) or [Symfony](18-symfony-integration.md)
5. Generate [TypeScript](23-typescript-generation.md)

### Framework-Specific Paths

#### Laravel Developers
1. [Laravel Integration](17-laravel-integration.md)
2. [Eloquent Integration](17-laravel-integration.md#eloquent)
3. [Validation Attributes](20-validation-attributes.md)
4. [Artisan Commands](25-artisan-commands.md)
5. [API Resources](38-api-resources.md)

#### Symfony Developers
1. [Symfony Integration](18-symfony-integration.md)
2. [Doctrine Integration](18-symfony-integration.md#doctrine)
3. [Security Integration](18-symfony-integration.md#security)
4. [Console Commands](26-console-commands.md)
5. [Form Requests](39-form-requests.md)

---

## 🎯 Use Cases

### API Development
- [API Resources](38-api-resources.md) - REST API responses
- [Conditional Properties](10-conditional-properties.md) - Role-based data
- [Serialization](09-serialization.md) - JSON, XML, YAML
- [TypeScript Generation](23-typescript-generation.md) - Frontend types

### Form Handling
- [Form Requests](39-form-requests.md) - Form validation
- [Validation](07-validation.md) - Auto rule inferring
- [Property Mapping](08-property-mapping.md) - Input transformation
- [Custom Validation](21-custom-validation.md) - Custom rules

### Data Transformation
- [Type Casting](06-type-casting.md) - Type conversion
- [Property Mapping](08-property-mapping.md) - Name mapping
- [Nested DTOs](16-nested-dtos.md) - Complex structures
- [Collections](15-collections.md) - Array handling

### Security & Privacy
- [Security & Visibility](22-security-visibility.md) - Hidden properties
- [Conditional Properties](10-conditional-properties.md) - Role-based access
- [Context-Based Conditions](12-context-based-conditions.md) - Dynamic visibility
- [Validation](07-validation.md) - Input sanitization

---

## 💡 Tips for Reading

### Symbols Used
- ✅ Feature available and stable
- 🔄 Feature in development
- ⚠️ Important note or warning
- 💡 Tip or best practice
- 📝 Example code
- 🎯 Use case or scenario

### Code Examples
All code examples are tested and working. You can find the complete examples in the `examples/` directory.

### Version Notes
This documentation is for SimpleDTO 3.0 (Phase 17 Complete). Features marked with version numbers indicate when they were introduced.

---

## 🤝 Contributing

Found an error or want to improve the documentation?
- Open an issue on GitHub
- Submit a pull request
- Join our Discord community

---

## 📞 Support

- **Documentation:** You're reading it!
- **Examples:** See `examples/` directory
- **Issues:** GitHub Issues
- **Discussions:** GitHub Discussions
- **Discord:** [Join our server](#)

---

## 📄 License

SimpleDTO is open-source software licensed under the MIT license.

---

**Next:** [Introduction](01-introduction.md) - Learn what SimpleDTO is and why you should use it.

