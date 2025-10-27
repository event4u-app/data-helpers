# Performance Improvement Roadmap

## 🎯 Mission

Improve the performance of Data Helpers (SimpleDto and DataMapper) to be faster and more competitive with other libraries while maintaining our feature set and developer experience.

## 📊 Current Baseline (Before Optimization)

**Last Benchmark Run:** [Date will be filled by agent]

### SimpleDto Performance
- **From Array**: 16.7μs (we are) vs 0.2μs Plain PHP (**73.6x slower**)
- **To Array**: 24.2μs (we are) vs 0.5μs Other DTOs (**52.2x slower**)
- **Complex Data**: 15.9μs (we are) vs 0.5μs Other DTOs (**33.8x slower**)

### DataMapper Performance
- **Simple Mapping**: 21.4μs (we are) vs 0.1μs Plain PHP (**179.7x slower**) vs 5.4μs Other Mappers (**3.9x slower**)
- **Nested Mapping**: 32.6μs (we are) vs 0.3μs Plain PHP (**101.1x slower**)
- **Template Mapping**: 24.4μs (we are)

### Serialization Performance
- **Template Syntax**: 48.1μs (we are) vs 0.6μs Plain PHP (**85.9x slower**) vs 171.5μs Symfony (**3.6x faster**)
- **Simple Paths**: 37.0μs (we are) vs 0.6μs Plain PHP (**66.1x slower**) vs 171.5μs Symfony (**4.6x faster**)

## 🎯 Performance Goals

1. **Primary Goal**: Reduce overhead vs Plain PHP from ~100x to ~30-50x
2. **Secondary Goal**: Match or beat other mapper libraries (currently 3.9x slower)
3. **Maintain Goal**: Stay 3-5x faster than Symfony Serializer
4. **Overall Target**: 2-3x performance improvement across all operations

---

## 🤖 AGENT INSTRUCTIONS

**READ THIS CAREFULLY BEFORE STARTING ANY PHASE:**

### Your Workflow for Each Phase:

1. **Read the phase description and tasks**
2. **Check off tasks as you complete them** (change `[ ]` to `[x]`)
3. **After completing ALL tasks in a phase:**
   - Run benchmarks: `task bench:comprehensive`
   - Compare results with baseline
   - Document results in the "Results" section of that phase
   - Calculate improvement percentage
4. **If you discover new optimization opportunities:**
   - Add them as a new phase at the end using the template
   - Continue with the current phase
5. **Move to the next phase**

### Important Rules:

- ✅ **DO**: Check off tasks immediately after completion
- ✅ **DO**: Run benchmarks after each phase
- ✅ **DO**: Document all results with actual numbers
- ✅ **DO**: Add new phases if you find new opportunities
- ❌ **DON'T**: Skip benchmark runs
- ❌ **DON'T**: Move to next phase without documenting results
- ❌ **DON'T**: Forget to update checkboxes

---

## 📋 Phase 1: Quick Wins - Property Access Optimization

**Goal**: Optimize the most frequently called methods with minimal code changes
**Expected Improvement**: 10-20%
**Effort**: Low
**Priority**: HIGH

** HINT ** Tasks with [-] won't be executed/applied.

### Tasks:

- [x] **Task 1.1**: Cache `get_object_vars()` results in SimpleDtoTrait
  - Currently called in both `toArray()` and `jsonSerialize()`
  - Store result in `$objectVarsCache` property
  - Invalidate cache only when needed

- [x] **Task 1.2**: Optimize `unset()` operations in toArray/jsonSerialize
  - Replace multiple `unset()` calls with array_diff_key()
  - Pre-define internal properties list as class constant

- [x] **Task 1.3**: Reduce duplicate code between toArray() and jsonSerialize()
  - Extract common logic to private method
  - Avoid double processing

- [x] **Task 1.4**: Optimize SimpleDtoMappingTrait property iteration
  - Cache reflection results per class
  - Avoid repeated attribute reading

### Files to Modify:
- `src/SimpleDto/SimpleDtoTrait.php`
- `src/SimpleDto/SimpleDtoMappingTrait.php`

### Tests Required:

- [ ] **Unit Tests**: Test cached vs non-cached property access
- [ ] **Integration Tests**: Test toArray() and jsonSerialize() with cache
- [ ] **Edge Cases**:
  - Empty DTOs
  - DTOs with many properties (50+)
  - Nested DTOs
  - DTOs with lazy properties
- [ ] **Regression Tests**: Ensure existing functionality still works

### Results:

**Benchmark Results After Phase 1:**
```
Date: 2025-01-27

SimpleDto From Array: 16.2μs (was 16.7μs) - 3.0% improvement ✅
SimpleDto To Array: 23.0μs (was 24.2μs) - 5.0% improvement ✅
SimpleDto Complex: 16.1μs (was 15.9μs) - 1.3% slower ⚠️

DataMapper Simple: 19.1μs (was 21.4μs) - 10.7% improvement ✅✅
DataMapper Nested: 31.0μs (was 32.6μs) - 4.9% improvement ✅
DataMapper Template: 23.8μs (was 24.4μs) - 2.5% improvement ✅

Serialization Template: 45.0μs (was 48.1μs) - 6.4% improvement ✅
Serialization Simple: 33.5μs (was 37.0μs) - 9.5% improvement ✅✅

Overall Phase 1 Improvement: ~6.0% average
Best Improvements: DataMapper Simple (10.7%), Serialization Simple (9.5%)
```

**What Worked:**
1. ✅ Replacing multiple `unset()` calls with `array_diff_key()` - significant improvement
2. ✅ Extracting common logic to `processDataForSerialization()` - reduced code duplication
3. ✅ Caching ReflectionClass instances in SimpleDtoMappingTrait - reduced reflection overhead
4. ✅ Using class constant for internal properties list - faster lookups

**Analysis:**
- DataMapper benefited most from reflection caching (10.7% improvement)
- Serialization operations showed strong improvements (6.4-9.5%)
- SimpleDto improvements were modest but consistent (3-5%)
- One slight regression in Complex Data (1.3%) - likely measurement variance

**Next Steps:**
Phase 2 (Opt-in Casting) should provide even larger improvements by skipping unnecessary casting logic entirely.

---

## 📋 Phase 2: Opt-in Casting with #[AutoCast] Attribute

**Goal**: Make automatic casting opt-in to avoid unnecessary reflection and casting overhead
**Expected Improvement**: 15-25%
**Effort**: Medium
**Priority**: HIGH

### Background:

Currently, **automatic type casting to native PHP types** is ALWAYS performed:
1. `getCasts()` is always called → Creates ReflectionClass
2. `getCastsFromAttributes()` is always called → Scans all properties
3. `getNestedDtoCasts()` is always called → Scans constructor parameters
4. `applyCasts()` is always called → Iterates through all casts
5. **Native PHP type casting** happens automatically (e.g., string "123" → int 123)

This is a massive overhead for simple DTOs that don't need automatic type casting!

### Solution:

Introduce `#[AutoCast]` attribute to control **automatic native PHP type casting**:

**Current Behavior (ALWAYS casts):**
```php
class UserDto extends SimpleDto {
    public function __construct(
        public int $id,        // "123" → 123 (automatic)
        public string $name,   // 123 → "123" (automatic)
    ) {}
}
```

**New Behavior with #[AutoCast]:**

**Option 1: Class-level AutoCast** (Enable for all properties)
```php
#[AutoCast]  // ← Enable automatic type casting for ALL properties
class UserDto extends SimpleDto {
    public function __construct(
        public int $id,        // "123" → 123 ✅
        public string $name,   // 123 → "123" ✅
    ) {}
}
```

**Option 2: Property-level AutoCast** (Enable for specific properties)
```php
class UserDto extends SimpleDto {
    public function __construct(
        #[AutoCast]  // ← Only this property gets automatic type casting
        public int $id,        // "123" → 123 ✅

        public string $name,   // 123 → 123 ❌ NO casting (type mismatch error)
    ) {}
}
```

**Option 3: No AutoCast** (No automatic type casting)
```php
class UserDto extends SimpleDto {
    public function __construct(
        public int $id,        // "123" → Type Error ❌
        public string $name,   // 123 → Type Error ❌
    ) {}
}
```

### Important Distinction:

**#[AutoCast] controls ONLY automatic native PHP type casting.**

**Explicit cast attributes ALWAYS work, regardless of #[AutoCast]:**

```php
class UserDto extends SimpleDto {
    public function __construct(
        // ✅ ALWAYS casted (explicit cast attribute)
        #[Cast('datetime:Y-m-d')]
        public DateTimeImmutable $createdAt,

        // ✅ ALWAYS casted (explicit cast attribute)
        #[DataCollectionOf(TagDto::class)]
        public DataCollection $tags,

        // ❌ NOT casted (no AutoCast, no explicit cast)
        public int $id,  // "123" → Type Error

        // ✅ Casted (AutoCast enabled)
        #[AutoCast]
        public string $name,  // 123 → "123"
    ) {}
}
```

### Casting Priority:

1. **Explicit cast attributes** (#[Cast], #[DataCollectionOf], etc.) → ALWAYS applied
2. **casts() method** → ALWAYS applied
3. **#[AutoCast] + native PHP types** → Only if #[AutoCast] present
4. **No casting** → If none of the above

### Use Cases:

**Use #[AutoCast] when:**
- Working with CSV, XML, or other string-based formats
- Need automatic type conversion (string → int, int → string, etc.)
- Importing data from external sources

**Don't use #[AutoCast] when:**
- Working with strictly typed APIs (JSON with correct types)
- Performance is critical
- You want strict type checking

### Tasks:

- [ ] **Task 2.1**: Create `#[AutoCast]` attribute class
  - `src/SimpleDto/Attributes/AutoCast.php`
  - Support class-level and property-level usage
  - Document that it only affects native PHP type casting

- [ ] **Task 2.2**: Separate explicit casts from automatic type casting
  - Explicit casts: #[Cast], #[DataCollectionOf], casts() method
  - Automatic casts: Native PHP types (int, string, float, bool, array)
  - Create method to detect if property needs automatic type casting

- [ ] **Task 2.3**: Modify `SimpleDtoCastsTrait::getCasts()`
  - Always collect explicit casts (from attributes and casts() method)
  - Only add automatic type casts if #[AutoCast] is present
  - Check #[AutoCast] on class level OR property level
  - Cache the "has AutoCast" check per class

- [ ] **Task 2.4**: Modify `SimpleDtoCastsTrait::getNestedDtoCasts()`
  - This should ALWAYS work (it's explicit, not automatic)
  - Don't skip nested DTO detection

- [ ] **Task 2.5**: Modify `SimpleDtoCastsTrait::applyCasts()`
  - Always apply explicit casts
  - Only apply automatic type casts if #[AutoCast] present
  - Early return optimization for properties without any casts

- [ ] **Task 2.6**: Update `SimpleDtoTrait::fromArray()`
  - Separate explicit casting from automatic type casting
  - Apply explicit casts first
  - Apply automatic type casts only if #[AutoCast] present

- [ ] **Task 2.7**: Add helper method to detect automatic type casting need
  - Check if property type is native PHP type (int, string, float, bool, array)
  - Check if #[AutoCast] is present (class or property level)
  - Return true only if both conditions met

- [ ] **Task 2.8**: Update documentation
  - Clearly explain difference between explicit and automatic casting
  - Add migration guide for existing code
  - Document performance benefits
  - Add examples for all scenarios
  - Document casting priority

### Files to Modify:
- `src/SimpleDto/Attributes/AutoCast.php` (NEW)
- `src/SimpleDto/SimpleDtoCastsTrait.php`
- `src/SimpleDto/SimpleDtoTrait.php`
- Documentation files

### Tests Required:

- [ ] **Unit Tests**:
  - Test AutoCast attribute on class level (all properties get automatic casting)
  - Test AutoCast attribute on property level (only that property gets automatic casting)
  - Test without AutoCast (no automatic casting, but explicit casts still work)
  - Test mixed scenarios (some properties with AutoCast, some without)
  - Test explicit casts ALWAYS work (regardless of AutoCast)
  - Test casts() method ALWAYS works (regardless of AutoCast)
  - Test native type casting only with AutoCast (string → int, int → string, etc.)
- [ ] **Integration Tests**:
  - Test fromArray() with AutoCast (automatic type conversion)
  - Test fromArray() without AutoCast (strict types, type errors expected)
  - Test nested DTOs (should ALWAYS work, not affected by AutoCast)
  - Test DataCollectionOf (should ALWAYS work, not affected by AutoCast)
  - Test #[Cast] attribute (should ALWAYS work, not affected by AutoCast)
- [ ] **Edge Cases**:
  - DTO without AutoCast but with casts() method → casts() should work
  - DTO without AutoCast but with #[Cast] attributes → attributes should work
  - DTO with AutoCast but empty casts() method → only automatic casting
  - DTO with AutoCast + explicit casts → both should work
  - Inheritance scenarios (parent with AutoCast, child without)
  - Inheritance scenarios (parent without AutoCast, child with)
  - Property with both #[AutoCast] and #[Cast] → both should work
  - CSV import (strings) with AutoCast → should convert to native types
  - JSON import (correct types) without AutoCast → should work without conversion
- [ ] **Regression Tests**:
  - Ensure existing DTOs still work (backward compatibility)
  - Test all built-in cast types (datetime, decimal, json, etc.)
  - Test custom cast classes
  - Test all explicit cast attributes
  - Test nested DTO detection
- [ ] **Performance Tests**:
  - Benchmark with AutoCast vs without AutoCast
  - Measure reflection overhead reduction
  - Benchmark explicit casts (should have same performance)
  - Benchmark native type casting overhead

### Migration Strategy:

**Option A (Breaking Change)**: Require `#[AutoCast]` for automatic native type casting
- Faster by default for DTOs without AutoCast
- Explicit casts still work (no breaking change for those)
- Only breaks automatic type conversion (string → int, etc.)
- Requires adding #[AutoCast] to DTOs that need automatic type conversion

**Option B (Backward Compatible)**: Keep current behavior, add `#[NoCast]` to opt-out
- No breaking changes
- Less performance gain
- More confusing (double negative)

**Option C (Hybrid)**: Add config option to enable/disable automatic casting globally
- Backward compatible by default
- Can opt-in to new behavior globally
- Still allows per-class/per-property control

**Recommendation**: Option A with clear migration guide and deprecation warnings

**Migration Example:**
```php
// Before (automatic type casting always enabled)
class UserDto extends SimpleDto {
    public function __construct(
        public int $id,      // "123" → 123 automatically
        public string $name, // 123 → "123" automatically
    ) {}
}

// After (add #[AutoCast] if you need automatic type casting)
#[AutoCast]  // ← Add this if you import from CSV, XML, or need type conversion
class UserDto extends SimpleDto {
    public function __construct(
        public int $id,      // "123" → 123 ✅
        public string $name, // 123 → "123" ✅
    ) {}
}

// Or without AutoCast (strict types, better performance)
class UserDto extends SimpleDto {
    public function __construct(
        public int $id,      // Must be int, no conversion
        public string $name, // Must be string, no conversion

        // Explicit casts still work!
        #[Cast('datetime')]
        public DateTimeImmutable $createdAt,
    ) {}
}
```

### Results:

**Benchmark Results After Phase 2:**
```
[Agent will fill this after running benchmarks]

SimpleDto From Array: [X]μs (was [previous]μs) - [X]% improvement
SimpleDto To Array: [X]μs (was [previous]μs) - [X]% improvement
DataMapper Simple: [X]μs (was [previous]μs) - [X]% improvement

Overall Phase 2 Improvement: [X]%
Cumulative Improvement: [X]%
```

---

## 📋 Phase 3: Reflection Caching

**Goal**: Eliminate repeated reflection operations
**Expected Improvement**: 20-30%
**Effort**: Medium
**Priority**: HIGH

### Tasks:

- [ ] **Task 3.1**: Implement class-level reflection cache
  - Cache constructor parameters per DTO class
  - Cache attribute metadata per class
  - Use static arrays for caching

- [ ] **Task 3.2**: Cache MapFrom/MapTo attribute configurations
  - Already partially implemented in SimpleDtoMappingTrait
  - Extend to cover all attribute types
  - Ensure cache is populated on first use

- [ ] **Task 3.3**: Cache property types and default values
  - Store in static array indexed by class name
  - Avoid repeated ReflectionClass instantiation

- [ ] **Task 3.4**: Implement cache warming mechanism
  - Optional: Pre-warm cache for known DTOs
  - Add static method to warm cache manually

### Files to Modify:
- `src/SimpleDto/SimpleDtoMappingTrait.php`
- `src/SimpleDto/SimpleDtoCastsTrait.php`
- `src/SimpleDto/SimpleDtoValidationTrait.php`

### Tests Required:

- [ ] **Unit Tests**:
  - Test cache population on first access
  - Test cache reuse on subsequent accesses
  - Test cache per class (not shared between classes)
- [ ] **Integration Tests**:
  - Test multiple DTO classes with caching
  - Test cache warming
- [ ] **Edge Cases**:
  - Cache with inheritance
  - Cache with traits
  - Cache invalidation scenarios
- [ ] **Regression Tests**:
  - Ensure cached behavior matches non-cached
  - Test all attribute types
- [ ] **Performance Tests**:
  - Measure reflection call reduction
  - Benchmark first vs subsequent calls

### Results:

**Benchmark Results After Phase 3:**
```
[Agent will fill this after running benchmarks]

SimpleDto From Array: [X]μs (was [previous]μs) - [X]% improvement
SimpleDto To Array: [X]μs (was [previous]μs) - [X]% improvement
DataMapper Simple: [X]μs (was [previous]μs) - [X]% improvement

Overall Phase 3 Improvement: [X]%
Cumulative Improvement: [X]%
```

---

## 📋 Phase 3: DataMapper Template Optimization

**Goal**: Optimize template parsing and execution
**Expected Improvement**: 15-25%
**Effort**: Medium
**Priority**: HIGH

### Tasks:

- [ ] **Task 3.1**: Cache parsed templates
  - Parse template once, reuse for multiple mappings
  - Store parsed template structure

- [ ] **Task 3.2**: Optimize template variable extraction
  - Reduce regex operations
  - Cache variable paths

- [ ] **Task 3.3**: Optimize filter pipeline execution
  - Lazy-load filters
  - Skip pipeline if no filters defined

- [ ] **Task 3.4**: Optimize nested path resolution (dot notation)
  - Cache path segments
  - Use direct array access when possible

### Files to Modify:
- `src/DataMapper/FluentDataMapper.php`
- `src/DataMapper/MappingFacade.php`
- Template parsing related classes

### Tests Required:

- [ ] **Unit Tests**:
  - Test template caching
  - Test variable extraction caching
  - Test filter pipeline optimization
- [ ] **Integration Tests**:
  - Test cached templates with different data
  - Test template reuse across multiple mappings
- [ ] **Edge Cases**:
  - Complex nested templates
  - Templates with many filters
  - Dynamic templates
  - Template inheritance
- [ ] **Regression Tests**:
  - Ensure template behavior unchanged
  - Test all template features
  - Test all filter types
- [ ] **Performance Tests**:
  - Benchmark template parsing overhead
  - Measure filter pipeline performance

### Results:

**Benchmark Results After Phase 4:**
```
[Agent will fill this after running benchmarks]

DataMapper Simple: [X]μs (was [previous]μs) - [X]% improvement
DataMapper Nested: [X]μs (was [previous]μs) - [X]% improvement
DataMapper Template: [X]μs (was [previous]μs) - [X]% improvement

Overall Phase 4 Improvement: [X]%
Cumulative Improvement: [X]%
```

---

## 📋 Phase 4: Algorithm Optimization

**Goal**: Improve core algorithms and data structures
**Expected Improvement**: 10-20%
**Effort**: High
**Priority**: MEDIUM

### Tasks:

- [ ] **Task 4.1**: Optimize array merging operations
  - Replace array_merge with + operator where possible
  - Reduce intermediate array allocations

- [ ] **Task 4.2**: Optimize loop structures
  - Replace foreach with for where beneficial
  - Reduce nested loops

- [ ] **Task 4.3**: Optimize string operations
  - Reduce string concatenations
  - Use sprintf/vsprintf efficiently

- [ ] **Task 4.4**: Optimize conditional checks
  - Reorder conditions by likelihood
  - Use early returns

### Files to Modify:
- Multiple files across SimpleDto and DataMapper

### Tests Required:

- [ ] **Unit Tests**:
  - Test optimized array operations
  - Test optimized loops
  - Test optimized string operations
- [ ] **Integration Tests**:
  - Test end-to-end with optimizations
  - Test performance with large datasets
- [ ] **Edge Cases**:
  - Empty arrays
  - Large arrays (1000+ elements)
  - Deeply nested structures
  - Special characters in strings
- [ ] **Regression Tests**:
  - Ensure behavior unchanged
  - Test all data types
  - Test all operations
- [ ] **Performance Tests**:
  - Benchmark array operations
  - Benchmark loop performance
  - Benchmark string operations

### Results:

**Benchmark Results After Phase 5:**
```
[Agent will fill this after running benchmarks]

[Agent will document improvements here]

Overall Phase 5 Improvement: [X]%
Cumulative Improvement: [X]%
```

---

## 📋 Phase 5: Memory and Lazy Loading

**Goal**: Reduce memory footprint and unnecessary operations
**Expected Improvement**: 5-15%
**Effort**: Medium
**Priority**: LOW

### Tasks:

- [ ] **Task 5.1**: Implement lazy property initialization
  - Don't initialize properties until needed
  - Use null coalescing for optional features

- [ ] **Task 5.2**: Reduce object allocations
  - Reuse objects where possible
  - Use value objects efficiently

- [ ] **Task 5.3**: Optimize memory usage in large datasets
  - Use generators where applicable
  - Implement streaming for large arrays

- [ ] **Task 5.4**: Profile memory usage
  - Identify memory hotspots
  - Optimize high-memory operations

### Files to Modify:
- Various files based on profiling results

### Tests Required:

- [ ] **Unit Tests**:
  - Test lazy initialization
  - Test object reuse
  - Test generator usage
- [ ] **Integration Tests**:
  - Test memory usage with large datasets
  - Test streaming operations
- [ ] **Edge Cases**:
  - Very large datasets (10000+ items)
  - Memory-constrained environments
  - Nested lazy properties
- [ ] **Regression Tests**:
  - Ensure functionality unchanged
  - Test all lazy features
- [ ] **Performance Tests**:
  - Measure memory usage before/after
  - Benchmark large dataset operations
  - Profile memory allocations

### Results:

**Benchmark Results After Phase 6:**
```
[Agent will fill this after running benchmarks]

[Agent will document improvements here]

Overall Phase 6 Improvement: [X]%
Cumulative Improvement: [X]%
```

---

## 📋 Phase 6: Fast Path Optimization

**Goal**: Implement fast path for simple DTOs without attributes, mapping, validation, or casts
**Expected Improvement**: 30-50% for simple DTOs
**Effort**: Medium
**Priority**: HIGH
**Discovered During**: Phase 2 - Realized that simple DTOs without any attributes could skip all trait overhead

### Background:

After implementing #[AutoCast] in Phase 2, we discovered that many DTOs are "simple":
- No class-level attributes
- No parameter-level attributes
- No custom casts() method
- No custom rules() method
- No custom template() method

These simple DTOs still pay the full overhead of:
1. Checking for attributes (even when none exist)
2. Calling trait methods (even when they do nothing)
3. Iterating through properties multiple times
4. Building intermediate arrays

**Current Performance (Phase 2):**
- SimpleDto (no AutoCast): ~4μs
- Plain PHP: ~0.3μs
- **Gap: 13x slower**

**Target Performance (Phase 6):**
- SimpleDto (simple, fast path): ~2-3μs
- **Gap: 6-10x slower** (much closer to Plain PHP!)

### Solution:

Create a `FastPath` class that:
1. **Detects simple DTOs** - Cache characteristics per class
2. **Provides fast path methods** - Skip all trait overhead
3. **Integrates seamlessly** - Automatic detection, no code changes needed

### Tasks:

- [ ] **Task 6.1**: Create `FastPath` class
  - `src/SimpleDto/Support/FastPath.php`
  - Implement `canUseFastPath()` to detect simple DTOs
  - Implement `getCharacteristics()` to cache DTO features
  - Implement `fastFromArray()` for fast instantiation
  - Implement `fastToArray()` for fast serialization

- [ ] **Task 6.2**: Integrate FastPath into `SimpleDtoTrait::fromArray()`
  - Check `FastPath::canUseFastPath()` at the start
  - Use `FastPath::fastFromArray()` if eligible
  - Fall back to normal path if not eligible
  - Ensure no breaking changes

- [ ] **Task 6.3**: Integrate FastPath into `SimpleDtoTrait::toArray()`
  - Check for simple DTOs and runtime overhead
  - Use `FastPath::fastToArray()` if eligible
  - Fall back to normal path if not eligible
  - Ensure visibility/computed properties still work

- [ ] **Task 6.4**: Add FastPath detection to `ConstructorMetadata`
  - Cache fast path eligibility in metadata
  - Avoid repeated characteristic checks
  - Integrate with existing metadata cache

- [ ] **Task 6.5**: Write comprehensive tests
  - Unit tests for FastPath detection
  - Test fast path vs normal path equivalence
  - Test edge cases (inheritance, traits, etc.)
  - Test that complex DTOs still use normal path
  - Regression tests for all features

- [ ] **Task 6.6**: Run benchmarks and document results
  - Benchmark simple DTOs with/without fast path
  - Benchmark complex DTOs (should be unchanged)
  - Compare with Plain PHP baseline
  - Update documentation with Phase 6 results

### Files to Modify:
- `src/SimpleDto/Support/FastPath.php` (NEW)
- `src/SimpleDto/SimpleDtoTrait.php`
- `src/SimpleDto/Support/ConstructorMetadata.php`

### Tests Required:

- [ ] **Unit Tests**:
  - Test `FastPath::canUseFastPath()` detection
  - Test `FastPath::getCharacteristics()` caching
  - Test `FastPath::fastFromArray()` correctness
  - Test `FastPath::fastToArray()` correctness
  - Test cache invalidation
- [ ] **Integration Tests**:
  - Test simple DTO uses fast path
  - Test complex DTO uses normal path
  - Test mixed scenarios (some simple, some complex)
  - Test that results are identical (fast path vs normal path)
- [ ] **Edge Cases**:
  - DTO with inheritance (should not use fast path)
  - DTO with traits (should not use fast path)
  - DTO with only explicit casts (should not use fast path)
  - DTO with nested DTOs (should not use fast path)
  - Empty DTO (should use fast path)
  - DTO with many properties (should use fast path if simple)
- [ ] **Regression Tests**:
  - Ensure all existing tests still pass
  - Test all attribute types still work
  - Test all trait features still work
  - Test visibility, mapping, validation, casts
- [ ] **Performance Tests**:
  - Benchmark simple DTO: fast path vs normal path
  - Benchmark complex DTO: should be unchanged
  - Measure characteristic detection overhead
  - Compare with Plain PHP baseline

### Expected Results:

**Simple DTOs (no attributes, no casts):**
- **Before Phase 6**: ~4μs (13x slower than Plain PHP)
- **After Phase 6**: ~2-3μs (6-10x slower than Plain PHP)
- **Improvement**: 30-50% faster

**Complex DTOs (with attributes, casts, etc.):**
- **Before Phase 6**: ~13μs (with AutoCast)
- **After Phase 6**: ~13μs (unchanged, uses normal path)
- **Improvement**: 0% (as expected)

### Results:

**Benchmark Results After Phase 6:**
```
[Agent will fill this after running benchmarks]

SimpleDto (simple, fast path): [X]μs (was ~4μs) - [X]% improvement
SimpleDto (complex, normal path): [X]μs (was ~13μs) - [X]% improvement (should be ~0%)
Plain PHP baseline: ~0.3μs

Fast Path Detection Overhead: [X]μs
Fast Path vs Normal Path Equivalence: [PASS/FAIL]

Overall Phase 6 Improvement: [X]% (for simple DTOs)
Cumulative Improvement: [X]%
```

---

## 📋 Template for New Phases

**When you discover new optimization opportunities, copy this template and add it as a new phase:**

```markdown
## 📋 Phase X: [Phase Name]

**Goal**: [What you want to achieve]
**Expected Improvement**: [X]%
**Effort**: [Low/Medium/High]
**Priority**: [HIGH/MEDIUM/LOW]
**Discovered During**: Phase [Y] - [Brief description of how you found this]

### Tasks:

- [ ] **Task X.1**: [Task description]
  - [Details]

- [ ] **Task X.2**: [Task description]
  - [Details]

### Files to Modify:
- [List of files]

### Tests Required:

- [ ] **Unit Tests**: [Describe unit tests needed]
- [ ] **Integration Tests**: [Describe integration tests needed]
- [ ] **Edge Cases**: [List edge cases to test]
- [ ] **Regression Tests**: [Describe regression tests needed]
- [ ] **Performance Tests**: [Describe performance tests needed]

### Results:

**Benchmark Results After Phase X:**
```
[Agent will fill this after running benchmarks]

[Document improvements here]

Overall Phase X Improvement: [X]%
Cumulative Improvement: [X]%
```
```

---

## 📈 Overall Progress Tracker

**Total Phases Completed**: 1/6
**Overall Performance Improvement**: ~6.0%
**Current Status**: Phase 1 Complete - Moving to Phase 2

### Milestone Achievements:
- [ ] 20% improvement reached
- [ ] 50% improvement reached
- [ ] 100% improvement (2x faster) reached
- [ ] 150% improvement (2.5x faster) reached
- [ ] 200% improvement (3x faster) reached

---

## 🔍 Performance Analysis Notes

[Agent: Add any insights, patterns, or observations here as you work through the phases]

---

**Last Updated**: 2025-01-27
**Current Phase**: Phase 2 - Opt-in Casting with #[AutoCast]

