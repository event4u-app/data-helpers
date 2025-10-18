<?php

declare(strict_types=1);

namespace PHPSTORM_META {

    use event4u\DataHelpers\SimpleDTO;
    use event4u\DataHelpers\SimpleDTO\DataCollection;

    // SimpleDTO::fromArray() returns the specific DTO type
    override(SimpleDTO::fromArray(0), map([
        '' => '@',
    ]));

    // SimpleDTO::validateAndCreate() returns the specific DTO type
    override(SimpleDTO::validateAndCreate(0), map([
        '' => '@',
    ]));

    // SimpleDTO::validate() returns array
    override(SimpleDTO::validate(0), type(0));

    // SimpleDTO::collection() returns DataCollection of the specific DTO type
    override(SimpleDTO::collection(0), map([
        '' => '@|DataCollection',
    ]));

    // DataCollection::forDto() returns DataCollection of the specific DTO type
    override(DataCollection::forDto(0), map([
        '' => '@|DataCollection',
    ]));

    // DataCollection::wrapDto() returns DataCollection of the specific DTO type
    override(DataCollection::wrapDto(0), map([
        '' => '@|DataCollection',
    ]));

    // Eloquent integration (if available)
    override(\event4u\DataHelpers\SimpleDTO\SimpleDTOEloquentTrait::fromModel(0), map([
        '' => '@',
    ]));

    // Doctrine integration (if available)
    override(\event4u\DataHelpers\SimpleDTO\SimpleDTODoctrineTrait::fromEntity(0), map([
        '' => '@',
    ]));

    // Cast attributes - provide autocomplete for cast types
    expectedArguments(
        \event4u\DataHelpers\SimpleDTO\SimpleDTOCastsTrait::casts(),
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
        \event4u\DataHelpers\SimpleDTO\Attributes\Between::__construct(),
        0,
        argumentsSet('validationRuleValues')
    );

    expectedArguments(
        \event4u\DataHelpers\SimpleDTO\Attributes\Min::__construct(),
        0,
        argumentsSet('validationRuleValues')
    );

    expectedArguments(
        \event4u\DataHelpers\SimpleDTO\Attributes\Max::__construct(),
        0,
        argumentsSet('validationRuleValues')
    );

    expectedArguments(
        \event4u\DataHelpers\SimpleDTO\Attributes\In::__construct(),
        0,
        argumentsSet('validationRuleValues')
    );

    expectedArguments(
        \event4u\DataHelpers\SimpleDTO\Attributes\NotIn::__construct(),
        0,
        argumentsSet('validationRuleValues')
    );

    expectedArguments(
        \event4u\DataHelpers\SimpleDTO\Attributes\Regex::__construct(),
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
        \event4u\DataHelpers\SimpleDTO\Attributes\MapFrom::__construct(),
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
        \event4u\DataHelpers\SimpleDTO\Attributes\MapTo::__construct(),
        0,
        argumentsSet('commonPropertyNames')
    );

    // MapInputName attribute - provide autocomplete for naming conventions
    expectedArguments(
        \event4u\DataHelpers\SimpleDTO\Attributes\MapInputName::__construct(),
        0,
        argumentsSet('namingConventions')
    );

    expectedArguments(
        \event4u\DataHelpers\SimpleDTO\Attributes\MapOutputName::__construct(),
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
        \event4u\DataHelpers\SimpleDTO\Attributes\Computed::__construct(),
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
        \event4u\DataHelpers\SimpleDTO\Attributes\Lazy::__construct(),
        0,
        argumentsSet('lazyOptions')
    );

    registerArgumentsSet(
        'lazyOptions',
        'when',
    );

    // DataCollectionOf attribute - provide type hints
    expectedArguments(
        \event4u\DataHelpers\SimpleDTO\Attributes\DataCollectionOf::__construct(),
        0,
        argumentsSet('dtoClasses')
    );

    registerArgumentsSet(
        'dtoClasses',
        \event4u\DataHelpers\SimpleDTO::class,
    );

    // TypeScript Generator - provide autocomplete for export types
    expectedArguments(
        \event4u\DataHelpers\SimpleDTO\TypeScriptGenerator::generate(),
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
        \event4u\DataHelpers\SimpleDTO\TypeScriptGenerator::generate(),
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

