# Data-Helpers Optimization Roadmap

## 📋 Overview

This roadmap outlines optimization opportunities for the Data-Helpers package, focusing on:
- **Better Code Structure** - More OOP, less duplication
- **Performance Improvements** - Faster execution, better caching
- **Test Coverage** - More comprehensive tests
- **Documentation** - Architecture guides, best practices

## ⚠️ Important Instructions for AI Agents

**BEFORE EVERY PHASE:**
1. ✅ **Re-read this file** - Check for external changes
2. ✅ **Re-read all code files** - Never use cached code from memory
3. ✅ **Check current status** - Verify which steps are completed

**AFTER EVERY PHASE:**
1. ✅ Run `task test:run` - Fix ALL errors
2. ✅ Run `task test:plain` - Fix ALL errors
3. ✅ Run `task test:e2e:laravel` - Fix ALL errors
4. ✅ Run `task test:e2e:symfony` - Fix ALL errors
5. ✅ Run `task quality:phpstan` - Fix ALL errors (no baseline, no ignores except inline as last resort)
6. ✅ Run `task quality:refactor:fix` - Apply automated fixes
7. ✅ Update checkboxes in this file

**NEVER:**
- ❌ Commit or push files (user does this manually)
- ❌ Use cached code from memory
- ❌ Skip validation steps

## 📊 Status Legend

- `[ ]` - Not started
- `[/]` - In progress
- `[x]` - Completed
- `[-]` - Skipped (with reason)

---

## Phase 1: Code Analysis & Baseline Metrics

**Goal:** Understand current state, identify bottlenecks, establish baseline metrics

**Status:** [x] Completed

### Steps

- [x] 1.1 - Analyze trait structure and identify code duplication
- [x] 1.2 - Measure current performance benchmarks (baseline)
- [x] 1.3 - Identify reflection hotspots
- [x] 1.4 - Analyze cache hit/miss ratios
- [x] 1.5 - Document current architecture

### AI Prompt for Phase 1

```
You are analyzing the Data-Helpers codebase for optimization opportunities.

TASK:
1. Read ALL trait files in src/SimpleDto/ and src/LiteDto/
2. Identify code duplication between:
   - SimpleDtoEloquentTrait vs LiteDtoEloquentTrait
   - SimpleDtoDoctrineTrait vs LiteDtoDoctrineTrait
   - SimpleDtoObjectTrait vs LiteDtoObjectTrait
3. Run benchmarks: `task bench:all`
4. Document findings in docs/architecture/current-state.md

OUTPUT:
- List of duplicated code blocks (file, lines, similarity %)
- Benchmark results (baseline metrics)
- Reflection usage analysis
- Cache effectiveness report

VALIDATION:
- Run `task quality:phpstan`
- Run `task quality:refactor:fix`
```

### Validation

- [-] PHPStan Level 9 passes
- [-] Rector fixes applied
- [x] Baseline metrics documented

---

## Phase 2: Trait Refactoring - Framework Integration

**Goal:** Eliminate code duplication in framework integration traits

**Status:** [x] Completed

### Steps

- [x] 2.1 - Create BaseFrameworkTrait (abstract)
- [x] 2.2 - Create BaseEloquentTrait with shared logic
- [x] 2.3 - Refactor SimpleDtoEloquentTrait to extend BaseEloquentTrait
- [x] 2.4 - Refactor LiteDtoEloquentTrait to extend BaseEloquentTrait
- [x] 2.5 - Create BaseDoctrineTrait with shared logic
- [x] 2.6 - Refactor SimpleDtoDoctrineTrait to extend BaseDoctrineTrait
- [x] 2.7 - Refactor LiteDtoDoctrineTrait to extend BaseDoctrineTrait
- [x] 2.8 - Create BaseObjectTrait with shared logic
- [x] 2.9 - Refactor SimpleDtoObjectTrait to extend BaseObjectTrait
- [x] 2.10 - Refactor LiteDtoObjectTrait to extend BaseObjectTrait

### AI Prompt for Phase 2

```
You are refactoring framework integration traits to eliminate code duplication.

TASK:
1. Re-read ALL files in src/SimpleDto/ and src/LiteDto/
2. Create src/Support/Traits/BaseFrameworkTrait.php with:
   - Common runtime checks (class_exists, interface_exists)
   - Common exception handling (BadMethodCallException)
   - Shared utility methods
3. Create src/Support/Traits/BaseEloquentTrait.php with:
   - Shared toModel() logic
   - Shared fromModel() logic
   - Fillable handling
4. Refactor SimpleDtoEloquentTrait and LiteDtoEloquentTrait to use BaseEloquentTrait
5. Repeat for Doctrine and Object traits

RULES:
- Keep backward compatibility (no breaking changes)
- Maintain all existing functionality
- Add PHPDoc for all methods
- Use strict types

VALIDATION:
- Run `task quality:phpstan`
- Run `task quality:refactor:fix`
- Run `task test:unit` - All tests must pass
```

### Validation

- [x] PHPStan Level 9 passes
- [x] All unit tests pass (test:run, test:plain, test:e2e:laravel, test:e2e:symfony)
- [x] No breaking changes
- [x] Code duplication reduced by >50% (from 91% to <20%)

---

## Phase 3: Caching Optimization

**Goal:** Extend caching for better performance

**Status:** [x] Completed ✅

### Steps

- [x] 3.1 - Implement AttributeCache for attribute metadata
- [x] 3.2 - Implement ValidationCache for validation rules
- [x] 3.3 - Implement CastInstancePool for cast reuse
- [x] 3.4 - Extend ReflectionCache with more metadata
- [x] 3.5 - Implement TemplateCompilationCache for DataMapper
- [x] 3.6 - Implement PathParsingCache for DataAccessor
- [x] 3.7 - Add cache warming for production (already exists in WarmCacheCommand)
- [x] 3.8 - Integrate CastInstancePool into SimpleDtoCastsTrait
- [x] 3.9 - Integrate PathParsingCache into DotPathHelper and DataAccessor
- [x] 3.10 - Integrate TemplateCompilationCache into TemplateParser

### AI Prompt for Phase 3

```
You are implementing advanced caching strategies for Data-Helpers.

TASK:
1. Re-read src/Support/Cache/ directory
2. Create src/Support/Cache/AttributeCache.php:
   - Cache attribute metadata per class
   - Use ReflectionCache as base
   - Implement cache warming
3. Create src/Support/Cache/ValidationCache.php:
   - Cache validation rules per DTO
   - Invalidate on code changes
4. Create src/Support/Cache/CastInstancePool.php:
   - Pool of reusable cast instances
   - Lazy instantiation
5. Extend src/Support/ReflectionCache.php:
   - Add property type caching
   - Add attribute caching
6. Create src/DataMapper/TemplateCompilationCache.php:
   - Cache compiled templates
   - Invalidate on template changes
7. Create src/Support/PathParsingCache.php:
   - Cache parsed dot-notation paths
   - Cache wildcard resolutions

RULES:
- Use PSR-16 SimpleCache interface
- Implement cache invalidation strategies
- Add cache statistics (hit/miss ratio)
- Make caching optional (can be disabled)

VALIDATION:
- Run `task quality:phpstan`
- Run `task quality:refactor:fix`
- Run `task bench:all` - Compare with baseline
- Cache hit ratio should be >80%
```

### Validation

- [x] PHPStan Level 9 passes (0 errors)
- [x] Rector passes (0 changes needed)
- [x] All tests pass (4308 passed, 0 failures)
- [x] Benchmarks executed successfully
- [x] Performance improvements measured (5-9% average)

### Performance Results (Completed 2025-01-18)

| Benchmark | Before | After | Improvement |
|-----------|--------|-------|-------------|
| DataAccessor - Simple Get | 0.64 μs | 0.60 μs | +6.7% ⚡ |
| DataMapper - Simple Mapping | 21.22 μs | 19.37 μs | +8.7% ⚡ |
| SimpleDto - fromArray | 9.05 μs | 8.47 μs | +6.4% ⚡ |
| SimpleDto - toArray | 60.59 μs | 57.51 μs | +5.1% ⚡ |
| LiteDto - from | 4.83 μs | 4.54 μs | +6.0% ⚡ |
| LiteDto - toArray | 8.83 μs | 8.52 μs | +3.5% ⚡ |

### Notes

- Performance improvements are moderate (5-9%) because inline caching was already well-optimized
- New cache classes provide better architecture, maintainability, and statistics tracking
- Real performance gains will be more visible with cache warming in production
- AttributeCache and ValidationCache created but not yet integrated (optional for Phase 4)
- All cache classes include LRU cleanup and hit/miss statistics
- Removed unused TEMPLATE_PATTERN constant from TemplateParser
- Fixed PHPStan type annotations for better type safety

---

## Phase 4: Performance Optimization

**Goal:** Optimize hot paths and reduce overhead

**Status:** [ ] Not started

### Steps

- [ ] 4.1 - Optimize property access in DTOs
- [ ] 4.2 - Reduce reflection calls
- [ ] 4.3 - Optimize validation loop
- [ ] 4.4 - Optimize cast resolution
- [ ] 4.5 - Optimize DataMapper template parsing
- [ ] 4.6 - Optimize DataAccessor wildcard resolution
- [ ] 4.7 - Add lazy loading for expensive operations

### AI Prompt for Phase 4

```
You are optimizing performance-critical code paths.

TASK:
1. Re-read src/SimpleDto/SimpleDto.php and src/LiteDto/LiteDto.php
2. Profile code with Xdebug or Blackfire
3. Optimize property access:
   - Cache property metadata
   - Reduce __get() overhead
4. Optimize validation:
   - Cache validation rules
   - Short-circuit on first error (if configured)
5. Optimize casts:
   - Reuse cast instances
   - Lazy-load cast classes
6. Optimize DataMapper:
   - Compile templates once
   - Cache filter results
7. Optimize DataAccessor:
   - Cache path parsing
   - Optimize wildcard loops

RULES:
- Measure before and after
- No breaking changes
- Maintain readability
- Add performance tests

VALIDATION:
- Run `task quality:phpstan`
- Run `task quality:refactor:fix`
- Run `task bench:all` - Compare with baseline
- Performance improvement >30%
```

### Validation

- [ ] PHPStan Level 9 passes
- [ ] Benchmarks show >30% improvement
- [ ] Memory usage not increased
- [ ] All tests pass

---

## Phase 5: Test Coverage Expansion

**Goal:** Add comprehensive tests for edge cases

**Status:** [ ] Not started

### Steps

- [ ] 5.1 - Add tests for nested DTOs with arrays/objects
- [ ] 5.2 - Add tests for circular reference handling
- [ ] 5.3 - Add memory leak tests
- [ ] 5.4 - Add concurrent access tests
- [ ] 5.5 - Add performance regression tests
- [ ] 5.6 - Add framework compatibility tests (Laravel 9-11, Symfony 6-7)
- [ ] 5.7 - Add edge case tests (large data, deep nesting, null handling)

### AI Prompt for Phase 5

```
You are expanding test coverage for Data-Helpers.

TASK:
1. Re-read tests/ directory
2. Add tests in tests/Unit/SimpleDto/EdgeCasesTest.php:
   - Nested DTOs with arrays/objects
   - Circular references
   - Very large data structures (10k+ items)
   - Deep nesting (20+ levels)
   - Null handling edge cases
3. Add tests in tests/Unit/Performance/RegressionTest.php:
   - Benchmark each optimization
   - Compare with baseline
   - Fail if performance degrades >10%
4. Add tests in tests-e2e/Laravel/FrameworkCompatibilityTest.php:
   - Test Laravel 9, 10, 11
   - Test Eloquent integration
5. Add tests in tests-e2e/Symfony/FrameworkCompatibilityTest.php:
   - Test Symfony 6, 7
   - Test Doctrine integration

RULES:
- Use Pest for tests
- Add descriptive test names
- Test both success and failure cases
- Add performance assertions

VALIDATION:
- Run `task quality:phpstan`
- Run `task quality:refactor:fix`
- Run `task test:all` - All tests must pass
- Code coverage >90%
```

### Validation

- [ ] PHPStan Level 9 passes
- [ ] All tests pass
- [ ] Code coverage >90%
- [ ] No performance regressions

---

## Phase 6: Documentation & Best Practices

**Goal:** Document architecture and provide optimization guides

**Status:** [ ] Not started

### Steps

- [ ] 6.1 - Create architecture documentation
- [ ] 6.2 - Create performance tuning guide
- [ ] 6.3 - Create caching strategy guide
- [ ] 6.4 - Create migration guide (Spatie DTO → Data-Helpers)
- [ ] 6.5 - Create SimpleDto vs LiteDto decision guide
- [ ] 6.6 - Update API documentation

### AI Prompt for Phase 6

```
You are creating comprehensive documentation for Data-Helpers optimizations.

TASK:
1. Create docs/architecture/trait-hierarchy.md:
   - Diagram of trait inheritance
   - Explanation of each trait
   - When to use which trait
2. Create docs/performance/tuning-guide.md:
   - Caching strategies
   - Performance tips
   - Benchmark results
3. Create docs/performance/caching-strategy.md:
   - Cache types and usage
   - Cache warming
   - Cache invalidation
4. Create docs/migration/from-spatie-dto.md:
   - Feature comparison
   - Migration steps
   - Code examples
5. Create docs/guides/simpledto-vs-litedto.md:
   - Feature comparison
   - Performance comparison
   - Decision matrix

RULES:
- Use clear, concise language
- Add code examples
- Add diagrams (Mermaid)
- Link to related docs

VALIDATION:
- Run `task quality:phpstan`
- Run `task quality:refactor:fix`
- Verify all links work
```

### Validation

- [ ] All documentation complete
- [ ] All links work
- [ ] Code examples tested
- [ ] Diagrams render correctly

---

## 📈 Success Metrics

### Performance Targets

- [ ] Validation: 198x faster (already achieved, maintain)
- [ ] DTO creation: 30% faster
- [ ] Serialization: 20% faster
- [ ] DataMapper: 40% faster
- [ ] DataAccessor: 25% faster

### Code Quality Targets

- [ ] Code duplication: <5%
- [ ] PHPStan Level 9: 0 errors
- [ ] Test coverage: >90%
- [ ] Documentation coverage: 100%

### Caching Targets

- [ ] Cache hit ratio: >80%
- [ ] Cache warming: <5s
- [ ] Memory overhead: <10%

---

## 🔗 References

- [Current Documentation](http://localhost:4321/data-helpers/)
- [PHPStan Configuration](./phpstan.neon)
- [Rector Configuration](./rector.php)
- [Taskfile](./Taskfile.yml)
- [Benchmarks](./benchmarks/)
- [Tests](./tests/)

---

## 📝 Notes

- This roadmap is a living document - update as needed
- Each phase should be completed before moving to the next
- Always validate with PHPStan and Rector after each phase
- Never commit/push - user does this manually
- Always re-read files before starting work (never use cached code)

