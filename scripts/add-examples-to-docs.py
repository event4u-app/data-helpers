#!/usr/bin/env python3
"""
Script to add example and test sections to documentation pages
"""

import os
import re
from pathlib import Path

# Define the documentation pages and their examples
DOCS_CONFIG = {
    "starlight/src/content/docs/simple-dto/type-casting.md": {
        "examples": [
            ("Basic Casts", "examples/simple-dto/type-casting/basic-casts.php", "Common type casts"),
            ("All Casts", "examples/simple-dto/type-casting/all-casts.php", "Complete cast overview"),
            ("Enum Cast", "examples/simple-dto/type-casting/enum-cast.php", "Enum casting"),
            ("Collection Cast", "examples/simple-dto/type-casting/collection-cast.php", "Collection casting"),
            ("Timestamp Cast", "examples/simple-dto/type-casting/timestamp-cast.php", "Date/time casting"),
            ("Hashed Cast", "examples/simple-dto/type-casting/hashed-cast.php", "Password hashing"),
            ("Encrypted Cast", "examples/simple-dto/type-casting/encrypted-cast.php", "Data encryption"),
            ("Lazy Cast", "examples/simple-dto/type-casting/lazy-cast.php", "Lazy loading casts"),
        ],
        "tests": [
            "tests/Unit/SimpleDTO/Casts/CastTest.php - Cast functionality tests",
            "tests/Unit/SimpleDTO/Casts/EnumCastTest.php - Enum cast tests",
            "tests/Unit/SimpleDTO/Casts/CollectionCastTest.php - Collection cast tests",
        ],
        "test_filter": "Cast"
    },
    "starlight/src/content/docs/simple-dto/validation.md": {
        "examples": [
            ("Basic Validation", "examples/simple-dto/validation/basic-validation.php", "Simple validation rules"),
            ("Advanced Validation", "examples/simple-dto/validation/advanced-validation.php", "Complex validation scenarios"),
            ("Request Validation Core", "examples/simple-dto/validation/request-validation-core.php", "Core request validation"),
            ("Laravel Validation", "examples/simple-dto/validation/request-validation-laravel.php", "Laravel integration"),
            ("Symfony Validation", "examples/simple-dto/validation/request-validation-symfony.php", "Symfony integration"),
            ("Validation Modes", "examples/simple-dto/validation/validation-modes.php", "Different validation modes"),
            ("Nested Validation", "examples/simple-dto/validation/nested-validation.php", "Validating nested DTOs"),
        ],
        "tests": [
            "tests/Unit/ValidationModesTest.php - Validation mode tests",
            "tests/Unit/SimpleDTO/ValidationTest.php - Core validation tests",
            "tests/Unit/SimpleDTO/NestedValidationTest.php - Nested validation tests",
        ],
        "test_filter": "Validation"
    },
    "starlight/src/content/docs/simple-dto/conditional-properties.md": {
        "examples": [
            ("Basic Conditional", "examples/simple-dto/conditional-properties/basic-conditional.php", "Simple conditional properties"),
            ("WhenCallback with Parameters", "examples/simple-dto/conditional-properties/whencallback-with-parameters.php", "Callbacks with parameters"),
            ("With Method", "examples/simple-dto/conditional-properties/with-method.php", "Using with() method"),
            ("Context-Based Conditions", "examples/simple-dto/conditional-properties/context-based-conditions.php", "Context-aware conditions"),
            ("Custom Conditions", "examples/simple-dto/conditional-properties/custom-conditions.php", "Creating custom conditions"),
            ("Laravel Attributes", "examples/simple-dto/conditional-properties/laravel-conditional-attributes.php", "Laravel-specific attributes"),
            ("Symfony Attributes", "examples/simple-dto/conditional-properties/symfony-conditional-attributes.php", "Symfony-specific attributes"),
        ],
        "tests": [
            "tests/Unit/SimpleDTO/ConditionalPropertiesTest.php - Conditional property tests",
            "tests/Unit/SimpleDTO/ContextTest.php - Context tests",
        ],
        "test_filter": "Conditional"
    },
    "starlight/src/content/docs/simple-dto/lazy-properties.md": {
        "examples": [
            ("Basic Lazy", "examples/simple-dto/lazy-properties/basic-lazy.php", "Simple lazy properties"),
            ("Lazy Union Types", "examples/simple-dto/lazy-properties/lazy-union-types.php", "Lazy with union types"),
            ("Optional Lazy Combinations", "examples/simple-dto/lazy-properties/optional-lazy-combinations.php", "Combining optional and lazy"),
        ],
        "tests": [
            "tests/Unit/SimpleDTO/LazyPropertiesTest.php - Lazy property tests",
        ],
        "test_filter": "Lazy"
    },
    "starlight/src/content/docs/simple-dto/computed-properties.md": {
        "examples": [
            ("Basic Computed", "examples/simple-dto/computed-properties/basic-computed.php", "Simple computed properties"),
        ],
        "tests": [
            "tests/Unit/SimpleDTO/ComputedPropertiesTest.php - Computed property tests",
        ],
        "test_filter": "Computed"
    },
    "starlight/src/content/docs/simple-dto/collections.md": {
        "examples": [
            ("Data Collection", "examples/simple-dto/collections/data-collection.php", "Working with collections"),
            ("DTO Sorting", "examples/simple-dto/collections/dto-sorting.php", "Sorting DTOs in collections"),
        ],
        "tests": [
            "tests/Unit/SimpleDTO/CollectionTest.php - Collection tests",
        ],
        "test_filter": "Collection"
    },
    "starlight/src/content/docs/simple-dto/security-visibility.md": {
        "examples": [
            ("Visibility Hidden", "examples/simple-dto/security-visibility/visibility-hidden.php", "Hiding properties"),
            ("Visibility Context", "examples/simple-dto/security-visibility/visibility-context.php", "Context-based visibility"),
            ("Visibility Explained", "examples/simple-dto/security-visibility/visibility-explained.php", "Detailed explanation"),
            ("Real World Example", "examples/simple-dto/security-visibility/visibility-real-world.php", "Practical use case"),
            ("Static Provider", "examples/simple-dto/security-visibility/visibility-static-provider.php", "Static visibility provider"),
        ],
        "tests": [
            "tests/Unit/SimpleDTO/VisibilityTest.php - Visibility tests",
        ],
        "test_filter": "Visibility"
    },
    "starlight/src/content/docs/simple-dto/typescript-generation.md": {
        "examples": [
            ("Basic Generation", "examples/simple-dto/typescript-generation/basic-generation.php", "Generate TypeScript types"),
            ("Generator Options", "examples/simple-dto/typescript-generation/generator-options.php", "Customizing generation"),
        ],
        "tests": [
            "tests/Unit/SimpleDTO/TypeScriptGeneratorTest.php - TypeScript generation tests",
        ],
        "test_filter": "TypeScript"
    },
    "starlight/src/content/docs/simple-dto/serialization.md": {
        "examples": [
            ("Serializers", "examples/simple-dto/serialization/serializers.php", "Serialization examples"),
            ("Transformers", "examples/simple-dto/serialization/transformers.php", "Data transformation"),
            ("Normalizers", "examples/simple-dto/serialization/normalizers.php", "Data normalization"),
            ("Serializer Options", "examples/simple-dto/serialization/serializer-options.php", "Customizing serialization"),
        ],
        "tests": [
            "tests/Unit/SimpleDTO/SerializationTest.php - Serialization tests",
        ],
        "test_filter": "Serialization"
    },
    "starlight/src/content/docs/simple-dto/property-mapping.md": {
        "examples": [
            ("Basic Mapping", "examples/simple-dto/property-mapping/basic-mapping.php", "Property name mapping"),
        ],
        "tests": [
            "tests/Unit/SimpleDTO/PropertyMappingTest.php - Property mapping tests",
        ],
        "test_filter": "PropertyMapping"
    },
    "starlight/src/content/docs/framework-integration/symfony.md": {
        "examples": [
            ("Symfony Doctrine", "examples/framework-integration/symfony/symfony-doctrine.php", "Symfony with Doctrine"),
        ],
        "tests": [
            "tests/Unit/Frameworks/Symfony/SymfonyIntegrationTest.php - Symfony integration tests",
            "tests-e2e/Symfony/ - End-to-end Symfony tests",
        ],
        "test_filter": "Symfony"
    },
    "starlight/src/content/docs/framework-integration/doctrine.md": {
        "examples": [
            ("Doctrine Integration", "examples/framework-integration/doctrine/doctrine-integration.php", "Working with Doctrine entities"),
        ],
        "tests": [
            "tests/Unit/DataAccessor/DataAccessorDoctrineTest.php - Doctrine tests",
            "tests/Unit/DataMutator/DataMutatorDoctrineTest.php - Doctrine mutator tests",
        ],
        "test_filter": "Doctrine"
    },
}


def add_sections_to_file(file_path, config):
    """Add example and test sections to a documentation file"""

    # Read the file
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Check if already has examples section
    if "## Code Examples" in content:
        print(f"⏭️  Skipping {file_path} (already has examples section)")
        return False

    # Check if has "## See Also" section
    if "## See Also" not in content:
        print(f"⚠️  No 'See Also' section found in {file_path}")
        return False

    # Build the new sections
    examples_section = "\n## Code Examples\n\nThe following working examples demonstrate this feature:\n\n"

    for title, path, desc in config["examples"]:
        examples_section += f"- [**{title}**](https://github.com/event4u-app/data-helpers/blob/main/{path}) - {desc}\n"

    examples_section += "\nAll examples are fully tested and can be run directly.\n"

    tests_section = "\n## Related Tests\n\nThe functionality is thoroughly tested. Key test files:\n\n"
    for test in config["tests"]:
        # Parse test entry: "path/to/test.php - Description"
        if " - " in test:
            test_path, test_desc = test.split(" - ", 1)
            test_path = test_path.strip()
            test_desc = test_desc.strip()
            # Extract filename from path
            test_filename = test_path.split("/")[-1]
            tests_section += f"- [{test_filename}](https://github.com/event4u-app/data-helpers/blob/main/{test_path}) - {test_desc}\n"
        else:
            # Fallback for entries without description
            test_path = test.strip()
            test_filename = test_path.split("/")[-1]
            tests_section += f"- [{test_filename}](https://github.com/event4u-app/data-helpers/blob/main/{test_path})\n"

    tests_section += f"\nRun the tests:\n\n```bash\n# Run tests\ntask test:unit -- --filter={config['test_filter']}\n```\n"

    # Insert before "## See Also"
    new_content = content.replace("## See Also", examples_section + tests_section + "\n## See Also")

    # Write back
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(new_content)

    print(f"✅  Updated {file_path}")
    return True


def main():
    print("📝 Adding example and test sections to documentation pages...\n")

    updated_count = 0
    for file_path, config in DOCS_CONFIG.items():
        if add_sections_to_file(file_path, config):
            updated_count += 1

    print(f"\n✅  Updated {updated_count} documentation pages successfully!")


if __name__ == "__main__":
    main()

