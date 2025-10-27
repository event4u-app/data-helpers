<?php

declare(strict_types=1);

namespace PHPSTORM_META {

    use event4u\DataHelpers\SimpleDto;
    use event4u\DataHelpers\SimpleDto\DataCollection;

    // SimpleDto::fromArray() returns the specific Dto type
    override(SimpleDto::fromArray(0), map([
        '' => '@',
    ]));

    // SimpleDto::validateAndCreate() returns the specific Dto type
    override(SimpleDto::validateAndCreate(0), map([
        '' => '@',
    ]));

    // SimpleDto::validate() returns array
    override(SimpleDto::validate(0), type(0));

    // SimpleDto::collection() returns DataCollection of the specific Dto type
    override(SimpleDto::collection(0), map([
        '' => '@|DataCollection',
    ]));

    // DataCollection::forDto() returns DataCollection of the specific Dto type
    override(DataCollection::forDto(0), map([
        '' => '@|DataCollection',
    ]));

    // DataCollection::wrapDto() returns DataCollection of the specific Dto type
    override(DataCollection::wrapDto(0), map([
        '' => '@|DataCollection',
    ]));

    // Eloquent integration (if available)
    override(\event4u\DataHelpers\SimpleDto\SimpleDtoEloquentTrait::fromModel(0), map([
        '' => '@',
    ]));

    // Doctrine integration (if available)
    override(\event4u\DataHelpers\SimpleDto\SimpleDtoDoctrineTrait::fromEntity(0), map([
        '' => '@',
    ]));

    // Cast attributes - provide autocomplete for cast types
    expectedArguments(
        \event4u\DataHelpers\SimpleDto\SimpleDtoCastsTrait::casts(),
        0,
        argumentsSet('simpleDtoCastTypes')
    );

    registerArgumentsSet(
        'simpleDtoCastTypes',
        'array',
        'boolean',
        'bool',
        'collection',
        'datetime',
        'date',
        'decimal',
        'decimal:2',
        'encrypted',
        'enum',
        'float',
        'hashed',
        'integer',
        'int',
        'json',
        'string',
        'timestamp',
    );

    // Validation attributes - provide autocomplete for validation rules
    expectedArguments(
        \event4u\DataHelpers\SimpleDto\Attributes\Between::__construct(),
        0,
        argumentsSet('validationRuleValues')
    );

    expectedArguments(
        \event4u\DataHelpers\SimpleDto\Attributes\Min::__construct(),
        0,
        argumentsSet('validationRuleValues')
    );

    expectedArguments(
        \event4u\DataHelpers\SimpleDto\Attributes\Max::__construct(),
        0,
        argumentsSet('validationRuleValues')
    );

    expectedArguments(
        \event4u\DataHelpers\SimpleDto\Attributes\In::__construct(),
        0,
        argumentsSet('validationRuleValues')
    );

    expectedArguments(
        \event4u\DataHelpers\SimpleDto\Attributes\NotIn::__construct(),
        0,
        argumentsSet('validationRuleValues')
    );

    expectedArguments(
        \event4u\DataHelpers\SimpleDto\Attributes\Regex::__construct(),
        0,
        argumentsSet('validationRuleValues')
    );

    registerArgumentsSet(
        'validationRuleValues',
        0,
        1,
        18,
        100,
        255,
        '/^[a-z]+$/',
        '/^[0-9]+$/',
        ['value1', 'value2'],
    );

    // MapFrom attribute - provide autocomplete for common property names
    expectedArguments(
        \event4u\DataHelpers\SimpleDto\Attributes\MapFrom::__construct(),
        0,
        argumentsSet('commonPropertyNames')
    );

    registerArgumentsSet(
        'commonPropertyNames',
        'id',
        'name',
        'email',
        'created_at',
        'updated_at',
        'user_id',
        'user.name',
        'user.email',
        'address.street',
        'address.city',
    );

    // MapTo attribute - provide autocomplete for common property names
    expectedArguments(
        \event4u\DataHelpers\SimpleDto\Attributes\MapTo::__construct(),
        0,
        argumentsSet('commonPropertyNames')
    );

    // MapInputName attribute - provide autocomplete for naming conventions
    expectedArguments(
        \event4u\DataHelpers\SimpleDto\Attributes\MapInputName::__construct(),
        0,
        argumentsSet('namingConventions')
    );

    expectedArguments(
        \event4u\DataHelpers\SimpleDto\Attributes\MapOutputName::__construct(),
        0,
        argumentsSet('namingConventions')
    );

    registerArgumentsSet(
        'namingConventions',
        'snake_case',
        'camelCase',
        'kebab-case',
        'PascalCase',
    );

    // Computed attribute - provide autocomplete for options
    expectedArguments(
        \event4u\DataHelpers\SimpleDto\Attributes\Computed::__construct(),
        0,
        argumentsSet('computedOptions')
    );

    registerArgumentsSet(
        'computedOptions',
        'name',
        'lazy',
        'cache',
    );

    // Lazy attribute - provide autocomplete for options
    expectedArguments(
        \event4u\DataHelpers\SimpleDto\Attributes\Lazy::__construct(),
        0,
        argumentsSet('lazyOptions')
    );

    registerArgumentsSet(
        'lazyOptions',
        'when',
    );

    // DataCollectionOf attribute - provide type hints
    expectedArguments(
        \event4u\DataHelpers\SimpleDto\Attributes\DataCollectionOf::__construct(),
        0,
        argumentsSet('dtoClasses')
    );

    registerArgumentsSet(
        'dtoClasses',
        \event4u\DataHelpers\SimpleDto::class,
    );

    // TypeScript Generator - provide autocomplete for export types
    expectedArguments(
        \event4u\DataHelpers\SimpleDto\TypeScriptGenerator::generate(),
        1,
        argumentsSet('typescriptExportTypes')
    );

    registerArgumentsSet(
        'typescriptExportTypes',
        'export',
        'declare',
        '',
    );

    // TypeScript Generator options
    expectedArguments(
        \event4u\DataHelpers\SimpleDto\TypeScriptGenerator::generate(),
        2,
        argumentsSet('typescriptOptions')
    );

    registerArgumentsSet(
        'typescriptOptions',
        ['includeComments' => true],
        ['includeComments' => false],
        ['sortProperties' => true],
        ['sortProperties' => false],
    );
}

