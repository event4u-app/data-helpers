#!/bin/bash

# Script to reorganize examples into structured folders

cd "$(dirname "$0")/.." || exit 1

echo "🔄 Reorganizing examples..."

# Main Classes - DataAccessor
mv examples/01-data-accessor.php examples/main-classes/data-accessor/basic-usage.php

# Main Classes - DataMutator
mv examples/02-data-mutator.php examples/main-classes/data-mutator/basic-usage.php

# Main Classes - DataMapper
mv examples/03-data-mapper-simple.php examples/main-classes/data-mapper/simple-mapping.php
mv examples/04-data-mapper-with-hooks.php examples/main-classes/data-mapper/with-hooks.php
mv examples/05-data-mapper-pipeline.php examples/main-classes/data-mapper/pipeline.php
mv examples/08-mapped-data-model.php examples/main-classes/data-mapper/mapped-data-model.php
mv examples/09-template-expressions.php examples/main-classes/data-mapper/template-expressions.php
mv examples/10-reverse-mapping.php examples/main-classes/data-mapper/reverse-mapping.php
mv examples/83-dto-mapper-integration.php examples/main-classes/data-mapper/dto-integration.php

# Main Classes - DataFilter
mv examples/20-data-filter.php examples/main-classes/data-filter/basic-usage.php
mv examples/12-wildcard-where-clause.php examples/main-classes/data-filter/wildcard-where.php
mv examples/13-custom-wildcard-operators.php examples/main-classes/data-filter/custom-wildcard-operators.php
mv examples/14-distinct-like-operators.php examples/main-classes/data-filter/distinct-like-operators.php
mv examples/15-group-by-aggregations.php examples/main-classes/data-filter/group-by-aggregations.php
mv examples/16-query-builder.php examples/main-classes/data-filter/query-builder.php
mv examples/19-callback-filters.php examples/main-classes/data-filter/callback-filters.php
mv examples/21-custom-operators.php examples/main-classes/data-filter/custom-operators.php
mv examples/22-complex-queries.php examples/main-classes/data-filter/complex-queries.php

# SimpleDTO - Creating DTOs
mv examples/23-simple-dto.php examples/simple-dto/creating-dtos/basic-dto.php
mv examples/47-dto-factory.php examples/simple-dto/creating-dtos/dto-factory.php
mv examples/48-wrapping.php examples/simple-dto/creating-dtos/wrapping.php
mv examples/58-optional-properties.php examples/simple-dto/creating-dtos/optional-properties.php

# SimpleDTO - Type Casting
mv examples/24-simple-dto-casts.php examples/simple-dto/type-casting/basic-casts.php
mv examples/25-simple-dto-all-casts.php examples/simple-dto/type-casting/all-casts.php
mv examples/29-enum-cast.php examples/simple-dto/type-casting/enum-cast.php
mv examples/30-collection-cast.php examples/simple-dto/type-casting/collection-cast.php
mv examples/31-timestamp-cast.php examples/simple-dto/type-casting/timestamp-cast.php
mv examples/32-hashed-cast.php examples/simple-dto/type-casting/hashed-cast.php
mv examples/33-encrypted-cast.php examples/simple-dto/type-casting/encrypted-cast.php
mv examples/54-lazy-cast.php examples/simple-dto/type-casting/lazy-cast.php

# SimpleDTO - Validation
mv examples/26-simple-dto-validation.php examples/simple-dto/validation/basic-validation.php
mv examples/27-simple-dto-validation-advanced.php examples/simple-dto/validation/advanced-validation.php
mv examples/63-request-validation-core.php examples/simple-dto/validation/request-validation-core.php
mv examples/64-request-validation-laravel.php examples/simple-dto/validation/request-validation-laravel.php
mv examples/65-request-validation-symfony.php examples/simple-dto/validation/request-validation-symfony.php
mv examples/66-validation-modes.php examples/simple-dto/validation/validation-modes.php
mv examples/67-nested-validation.php examples/simple-dto/validation/nested-validation.php
mv examples/68-html-error-responses.php examples/simple-dto/validation/html-error-responses.php
mv examples/69-advanced-validation-attributes.php examples/simple-dto/validation/advanced-validation-attributes.php
mv examples/70-laravel-validation-integration.php examples/simple-dto/validation/laravel-validation-integration.php
mv examples/71-symfony-validation-integration.php examples/simple-dto/validation/symfony-validation-integration.php
mv examples/72-advanced-rule-combinations.php examples/simple-dto/validation/advanced-rule-combinations.php

# SimpleDTO - Property Mapping
mv examples/28-simple-dto-mapping.php examples/simple-dto/property-mapping/basic-mapping.php

# SimpleDTO - Serialization
mv examples/49-serializers.php examples/simple-dto/serialization/serializers.php
mv examples/50-transformers.php examples/simple-dto/serialization/transformers.php
mv examples/51-normalizers.php examples/simple-dto/serialization/normalizers.php
mv examples/88-serializer-options.php examples/simple-dto/serialization/serializer-options.php

# SimpleDTO - Conditional Properties
mv examples/73-conditional-properties.php examples/simple-dto/conditional-properties/basic-conditional.php
mv examples/74-whencallback-with-parameters.php examples/simple-dto/conditional-properties/whencallback-with-parameters.php
mv examples/74-with-method.php examples/simple-dto/conditional-properties/with-method.php
mv examples/75-context-based-conditions.php examples/simple-dto/conditional-properties/context-based-conditions.php
mv examples/75-custom-conditions.php examples/simple-dto/conditional-properties/custom-conditions.php
mv examples/76-laravel-conditional-attributes.php examples/simple-dto/conditional-properties/laravel-conditional-attributes.php
mv examples/77-symfony-conditional-attributes.php examples/simple-dto/conditional-properties/symfony-conditional-attributes.php

# SimpleDTO - Lazy Properties
mv examples/43-lazy-properties.php examples/simple-dto/lazy-properties/basic-lazy.php
mv examples/59-lazy-union-types.php examples/simple-dto/lazy-properties/lazy-union-types.php
mv examples/60-optional-lazy-combinations.php examples/simple-dto/lazy-properties/optional-lazy-combinations.php

# SimpleDTO - Computed Properties
mv examples/39-computed-properties.php examples/simple-dto/computed-properties/basic-computed.php

# SimpleDTO - Collections
mv examples/42-data-collection.php examples/simple-dto/collections/data-collection.php
mv examples/90-dto-sorting.php examples/simple-dto/collections/dto-sorting.php

# SimpleDTO - Security & Visibility
mv examples/34-visibility-hidden.php examples/simple-dto/security-visibility/visibility-hidden.php
mv examples/35-visibility-context.php examples/simple-dto/security-visibility/visibility-context.php
mv examples/36-visibility-explained.php examples/simple-dto/security-visibility/visibility-explained.php
mv examples/37-visibility-real-world.php examples/simple-dto/security-visibility/visibility-real-world.php
mv examples/38-visibility-static-provider.php examples/simple-dto/security-visibility/visibility-static-provider.php

# SimpleDTO - TypeScript Generation
mv examples/44-typescript-generation.php examples/simple-dto/typescript-generation/basic-generation.php
mv examples/87-typescript-generator-options.php examples/simple-dto/typescript-generation/generator-options.php

# SimpleDTO - IDE Support
mv examples/45-better-error-messages.php examples/simple-dto/ide-support/better-error-messages.php
mv examples/46-ide-support.php examples/simple-dto/ide-support/ide-support.php

# Framework Integration - Laravel
mv examples/06-laravel.php examples/framework-integration/laravel/basic-usage.php
mv examples/40-eloquent-integration.php examples/framework-integration/laravel/eloquent-integration.php

# Framework Integration - Symfony/Doctrine
mv examples/07-symfony-doctrine.php examples/framework-integration/symfony/symfony-doctrine.php
mv examples/41-doctrine-integration.php examples/framework-integration/doctrine/doctrine-integration.php

# Advanced - Pipelines
mv examples/52-pipeline.php examples/advanced/pipelines/basic-pipeline.php

# Advanced - Template Expressions (copy from data-mapper)
cp examples/main-classes/data-mapper/template-expressions.php examples/advanced/template-expressions/template-expressions.php 2>/dev/null || true

# Advanced - Reverse Mapping (copy from data-mapper)
cp examples/main-classes/data-mapper/reverse-mapping.php examples/advanced/reverse-mapping/reverse-mapping.php 2>/dev/null || true

# Advanced - Hooks & Events (copy from data-mapper)
cp examples/main-classes/data-mapper/with-hooks.php examples/advanced/hooks-events/with-hooks.php 2>/dev/null || true

# Advanced - Callback Filters (copy from data-filter)
cp examples/main-classes/data-filter/callback-filters.php examples/advanced/callback-filters/callback-filters.php 2>/dev/null || true

# Advanced - Query Builder (copy from data-filter)
cp examples/main-classes/data-filter/query-builder.php examples/advanced/query-builder/query-builder.php 2>/dev/null || true

# Advanced - Group By (copy from data-filter)
cp examples/main-classes/data-filter/group-by-aggregations.php examples/advanced/group-by/group-by-aggregations.php 2>/dev/null || true

# Performance - Optimization
mv examples/53-performance.php examples/performance/optimization/performance.php
mv examples/55-optimized-reflection.php examples/performance/optimization/optimized-reflection.php

# Performance - Benchmarks
mv examples/56-benchmarking.php examples/performance/benchmarks/benchmarking.php
mv examples/57-performance-testing.php examples/performance/benchmarks/performance-testing.php

# Real World Examples
mv examples/78-real-world-ecommerce.php examples/real-world/ecommerce.php
mv examples/79-real-world-blog.php examples/real-world/blog.php
mv examples/80-api-resources-complete.php examples/real-world/api-resources-complete.php
mv examples/81-form-requests-complete.php examples/real-world/form-requests-complete.php
mv examples/82-advanced-features-showcase.php examples/real-world/advanced-features-showcase.php
mv examples/62-api-integration.php examples/real-world/api-integration.php
mv examples/61-partial-updates.php examples/real-world/partial-updates.php

# Troubleshooting
mv examples/11-exception-handling.php examples/troubleshooting/exception-handling.php

# Core Concepts - Wildcards (copy from data-filter)
cp examples/main-classes/data-filter/wildcard-where.php examples/core-concepts/wildcards/wildcard-where.php 2>/dev/null || true

# Attributes
mv examples/85-naming-convention-enum.php examples/attributes/naming-convention-enum.php
mv examples/86-comparison-operator-enum.php examples/attributes/comparison-operator-enum.php

echo "✅  Examples reorganized successfully!"
echo ""
echo "📁 New structure:"
echo "   - main-classes/"
echo "   - simple-dto/"
echo "   - framework-integration/"
echo "   - advanced/"
echo "   - performance/"
echo "   - real-world/"
echo "   - troubleshooting/"
echo "   - core-concepts/"
echo "   - attributes/"

