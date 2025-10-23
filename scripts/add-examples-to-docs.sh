#!/bin/bash

# Script to add example and test sections to documentation pages

cd "$(dirname "$0")/.." || exit 1

echo "📝 Adding example and test sections to documentation pages..."

# Function to add sections to a markdown file
add_sections() {
    local file=$1
    local examples=$2
    local tests=$3
    local test_filter=$4

    # Check if file already has "## Code Examples" section
    if grep -q "## Code Examples" "$file"; then
        echo "⏭️  Skipping $file (already has examples section)"
        return
    fi

    # Find the "## See Also" section and insert before it
    if grep -q "## See Also" "$file"; then
        # Create temporary file with new content
        awk -v examples="$examples" -v tests="$tests" -v filter="$test_filter" '
        /^## See Also/ {
            print "## Code Examples"
            print ""
            print "The following working examples demonstrate this feature:"
            print ""
            print examples
            print ""
            print "All examples are fully tested and can be run directly."
            print ""
            print "## Related Tests"
            print ""
            print "Key test files:"
            print ""
            print tests
            print ""
            print "Run the tests:"
            print ""
            print "```bash"
            print "# Run tests"
            print "task test:unit -- --filter=" filter
            print "```"
            print ""
        }
        { print }
        ' "$file" > "$file.tmp"
        
        mv "$file.tmp" "$file"
        echo "✅  Updated $file"
    else
        echo "⚠️  No 'See Also' section found in $file"
    fi
}

# SimpleDTO - Type Casting
add_sections \
    "starlight/src/content/docs/simple-dto/type-casting.md" \
    "- [**Basic Casts**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/type-casting/basic-casts.php) - Common type casts
- [**All Casts**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/type-casting/all-casts.php) - Complete cast overview
- [**Enum Cast**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/type-casting/enum-cast.php) - Enum casting
- [**Collection Cast**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/type-casting/collection-cast.php) - Collection casting
- [**Timestamp Cast**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/type-casting/timestamp-cast.php) - Date/time casting
- [**Hashed Cast**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/type-casting/hashed-cast.php) - Password hashing
- [**Encrypted Cast**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/type-casting/encrypted-cast.php) - Data encryption
- [**Lazy Cast**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/type-casting/lazy-cast.php) - Lazy loading casts" \
    "- \`tests/Unit/SimpleDTO/Casts/CastTest.php\` - Cast functionality tests
- \`tests/Unit/SimpleDTO/Casts/EnumCastTest.php\` - Enum cast tests
- \`tests/Unit/SimpleDTO/Casts/CollectionCastTest.php\` - Collection cast tests" \
    "Cast"

# SimpleDTO - Validation
add_sections \
    "starlight/src/content/docs/simple-dto/validation.md" \
    "- [**Basic Validation**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/validation/basic-validation.php) - Simple validation rules
- [**Advanced Validation**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/validation/advanced-validation.php) - Complex validation scenarios
- [**Request Validation Core**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/validation/request-validation-core.php) - Core request validation
- [**Laravel Validation**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/validation/request-validation-laravel.php) - Laravel integration
- [**Symfony Validation**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/validation/request-validation-symfony.php) - Symfony integration
- [**Validation Modes**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/validation/validation-modes.php) - Different validation modes
- [**Nested Validation**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/validation/nested-validation.php) - Validating nested DTOs" \
    "- \`tests/Unit/ValidationModesTest.php\` - Validation mode tests
- \`tests/Unit/SimpleDTO/ValidationTest.php\` - Core validation tests
- \`tests/Unit/SimpleDTO/NestedValidationTest.php\` - Nested validation tests" \
    "Validation"

# SimpleDTO - Conditional Properties
add_sections \
    "starlight/src/content/docs/simple-dto/conditional-properties.md" \
    "- [**Basic Conditional**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/conditional-properties/basic-conditional.php) - Simple conditional properties
- [**WhenCallback with Parameters**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/conditional-properties/whencallback-with-parameters.php) - Callbacks with parameters
- [**With Method**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/conditional-properties/with-method.php) - Using with() method
- [**Context-Based Conditions**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/conditional-properties/context-based-conditions.php) - Context-aware conditions
- [**Custom Conditions**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/conditional-properties/custom-conditions.php) - Creating custom conditions
- [**Laravel Attributes**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/conditional-properties/laravel-conditional-attributes.php) - Laravel-specific attributes
- [**Symfony Attributes**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/conditional-properties/symfony-conditional-attributes.php) - Symfony-specific attributes" \
    "- \`tests/Unit/SimpleDTO/ConditionalPropertiesTest.php\` - Conditional property tests
- \`tests/Unit/SimpleDTO/ContextTest.php\` - Context tests" \
    "Conditional"

# SimpleDTO - Lazy Properties
add_sections \
    "starlight/src/content/docs/simple-dto/lazy-properties.md" \
    "- [**Basic Lazy**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/lazy-properties/basic-lazy.php) - Simple lazy properties
- [**Lazy Union Types**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/lazy-properties/lazy-union-types.php) - Lazy with union types
- [**Optional Lazy Combinations**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/lazy-properties/optional-lazy-combinations.php) - Combining optional and lazy" \
    "- \`tests/Unit/SimpleDTO/LazyPropertiesTest.php\` - Lazy property tests" \
    "Lazy"

# SimpleDTO - Computed Properties
add_sections \
    "starlight/src/content/docs/simple-dto/computed-properties.md" \
    "- [**Basic Computed**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/computed-properties/basic-computed.php) - Simple computed properties" \
    "- \`tests/Unit/SimpleDTO/ComputedPropertiesTest.php\` - Computed property tests" \
    "Computed"

# SimpleDTO - Collections
add_sections \
    "starlight/src/content/docs/simple-dto/collections.md" \
    "- [**Data Collection**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/collections/data-collection.php) - Working with collections
- [**DTO Sorting**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/collections/dto-sorting.php) - Sorting DTOs in collections" \
    "- \`tests/Unit/SimpleDTO/CollectionTest.php\` - Collection tests" \
    "Collection"

# SimpleDTO - Security & Visibility
add_sections \
    "starlight/src/content/docs/simple-dto/security-visibility.md" \
    "- [**Visibility Hidden**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/security-visibility/visibility-hidden.php) - Hiding properties
- [**Visibility Context**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/security-visibility/visibility-context.php) - Context-based visibility
- [**Visibility Explained**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/security-visibility/visibility-explained.php) - Detailed explanation
- [**Real World Example**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/security-visibility/visibility-real-world.php) - Practical use case
- [**Static Provider**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/security-visibility/visibility-static-provider.php) - Static visibility provider" \
    "- \`tests/Unit/SimpleDTO/VisibilityTest.php\` - Visibility tests" \
    "Visibility"

# SimpleDTO - TypeScript Generation
add_sections \
    "starlight/src/content/docs/simple-dto/typescript-generation.md" \
    "- [**Basic Generation**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/typescript-generation/basic-generation.php) - Generate TypeScript types
- [**Generator Options**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/typescript-generation/generator-options.php) - Customizing generation" \
    "- \`tests/Unit/SimpleDTO/TypeScriptGeneratorTest.php\` - TypeScript generation tests" \
    "TypeScript"

# SimpleDTO - Serialization
add_sections \
    "starlight/src/content/docs/simple-dto/serialization.md" \
    "- [**Serializers**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/serialization/serializers.php) - Serialization examples
- [**Transformers**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/serialization/transformers.php) - Data transformation
- [**Normalizers**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/serialization/normalizers.php) - Data normalization
- [**Serializer Options**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/serialization/serializer-options.php) - Customizing serialization" \
    "- \`tests/Unit/SimpleDTO/SerializationTest.php\` - Serialization tests" \
    "Serialization"

# Framework Integration - Laravel
add_sections \
    "starlight/src/content/docs/framework-integration/laravel.md" \
    "- [**Basic Usage**](https://github.com/event4u-app/data-helpers/blob/main/examples/framework-integration/laravel/basic-usage.php) - Laravel integration basics
- [**Eloquent Integration**](https://github.com/event4u-app/data-helpers/blob/main/examples/framework-integration/laravel/eloquent-integration.php) - Working with Eloquent models" \
    "- \`tests/Unit/Frameworks/Laravel/LaravelIntegrationTest.php\` - Laravel integration tests
- \`tests-e2e/Laravel/\` - End-to-end Laravel tests" \
    "Laravel"

# Framework Integration - Symfony
add_sections \
    "starlight/src/content/docs/framework-integration/symfony.md" \
    "- [**Symfony Doctrine**](https://github.com/event4u-app/data-helpers/blob/main/examples/framework-integration/symfony/symfony-doctrine.php) - Symfony with Doctrine" \
    "- \`tests/Unit/Frameworks/Symfony/SymfonyIntegrationTest.php\` - Symfony integration tests
- \`tests-e2e/Symfony/\` - End-to-end Symfony tests" \
    "Symfony"

# Framework Integration - Doctrine
add_sections \
    "starlight/src/content/docs/framework-integration/doctrine.md" \
    "- [**Doctrine Integration**](https://github.com/event4u-app/data-helpers/blob/main/examples/framework-integration/doctrine/doctrine-integration.php) - Working with Doctrine entities" \
    "- \`tests/Unit/DataAccessor/DataAccessorDoctrineTest.php\` - Doctrine tests
- \`tests/Unit/DataMutator/DataMutatorDoctrineTest.php\` - Doctrine mutator tests" \
    "Doctrine"

echo ""
echo "✅  Documentation pages updated successfully!"

