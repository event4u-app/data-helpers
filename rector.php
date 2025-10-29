<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Concat\JoinStringConcatRector;
use Rector\CodeQuality\Rector\FuncCall\SortNamedParamRector;
use Rector\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodeQuality\Rector\Switch_\SwitchTrueToIfRector;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\CodingStyle\Rector\FuncCall\FunctionFirstClassCallableRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\CodingStyle\Rector\Stmt\RemoveUselessAliasInUseStatementRector;
use Rector\CodingStyle\Rector\String_\SymplifyQuoteEscapeRector;
use Rector\CodingStyle\Rector\String_\UseClassKeywordForClassNameResolutionRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\DeadCode\Rector\ClassConst\RemoveUnusedPrivateClassConstantRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\DeadCode\Rector\MethodCall\RemoveNullArgOnNullDefaultParamRector;
use Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector;
use Rector\DeadCode\Rector\PropertyProperty\RemoveNullPropertyInitializationRector;
use Rector\DeadCode\Rector\Stmt\RemoveUnreachableStatementRector;
use Rector\DeadCode\Rector\TryCatch\RemoveDeadTryCatchRector;
use Rector\EarlyReturn\Rector\If_\ChangeOrIfContinueToMultiContinueRector;
use Rector\EarlyReturn\Rector\Return_\ReturnBinaryOrToEarlyReturnRector;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php73\Rector\BooleanOr\IsCountableRector;
use Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
use Rector\Renaming\Rector\FuncCall\RenameFunctionRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\Transform\Rector\FuncCall\FuncCallToConstFetchRector;
use Rector\TypeDeclaration\Rector\Class_\MergeDateTimePropertyTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\Class_\PropertyTypeFromStrictSetterGetterRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeFromPropertyTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationBasedOnParentClassMethodRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByMethodCallTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictFluentReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictParamRector;
use Rector\TypeDeclaration\Rector\ClassMethod\StrictArrayParamDimFetchRector;
use Rector\TypeDeclaration\Rector\ClassMethod\StrictStringParamConcatRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/benchmarks',
        __DIR__ . '/examples',
        __DIR__ . '/scripts',
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/types',
    ])
    ->withSkipPath(__DIR__ . '/src/Support')
    ->withSkipPath(__DIR__ . '/tests-e2e/*/vendor')
    ->withSkipPath(__DIR__ . '/tests-e2e/*/composer.lock')
    ->withRootFiles()
    ->withPHPStanConfigs([__DIR__ . '/phpstan.neon'])
    ->withPhpSets(php82: true)
    ->withSets([
        LevelSetList::UP_TO_PHP_82,
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
        SetList::NAMING,
        SetList::INSTANCEOF,
        SetList::EARLY_RETURN,
    ])
    ->withImportNames(
        importShortClasses: true,
        removeUnusedImports: true
    )
    ->withConfiguredRule(RenameFunctionRector::class, [
        'split' => 'explode',
        'join' => 'implode',
        'sizeof' => 'count',
        'chop' => 'rtrim',
        'doubleval' => 'floatval',
        'gzputs' => 'gzwrites',
        'fputs' => 'fwrite',
        'ini_alter' => 'ini_set',
        'is_double' => 'is_float',
        'is_integer' => 'is_int',
        'is_long' => 'is_int',
        'is_real' => 'is_float',
        'is_writeable' => 'is_writable',
        'key_exists' => 'array_key_exists',
        'pos' => 'current',
        'strchr' => 'strstr',
        'mbstrcut' => 'mb_strcut',
        'mbstrlen' => 'mb_strlen',
        'mbstrpos' => 'mb_strpos',
        'mbstrrpos' => 'mb_strrpos',
        'mbsubstr' => 'mb_substr',
    ])
    ->withConfiguredRule(FuncCallToConstFetchRector::class, [
        'php_sapi_name' => 'PHP_SAPI',
        'pi' => 'M_PI',
    ])
    ->withSkip([
        IsCountableRector::class,
        StringClassNameToClassConstantRector::class,
        RenamePropertyToMatchTypeRector::class,
        RenameVariableToMatchNewTypeRector::class,
        RenameVariableToMatchMethodCallReturnTypeRector::class,
        RestoreDefaultNullToNullableTypePropertyRector::class,
        RenameParamToMatchTypeRector::class,
        JoinStringConcatRector::class,
        RemoveUnusedPrivateMethodParameterRector::class,
        SimplifyIfElseToTernaryRector::class,
        SimplifyBoolIdenticalTrueRector::class,
        SwitchTrueToIfRector::class,
        UseClassKeywordForClassNameResolutionRector::class,
        RemoveUselessAliasInUseStatementRector::class,
        PrivatizeLocalGetterToPropertyRector::class,
        ChangeOrIfContinueToMultiContinueRector::class,
        ReturnBinaryOrToEarlyReturnRector::class,
        RemoveDeadTryCatchRector::class,
        MakeInheritedMethodVisibilitySameAsParentRector::class,
        RemoveNullPropertyInitializationRector::class,
        RemoveNonExistingVarAnnotationRector::class,
        RemoveUnusedPromotedPropertyRector::class,
        RemoveUnusedPublicMethodParameterRector::class,
        RemoveUnreachableStatementRector::class,
        RemoveUnusedVariableAssignRector::class,
        ReturnTypeFromStrictParamRector::class,
        AddParamTypeFromPropertyTypeRector::class,
        MergeDateTimePropertyTypeDeclarationRector::class,
        PropertyTypeFromStrictSetterGetterRector::class,
        ParamTypeByMethodCallTypeRector::class,
        TypedPropertyFromAssignsRector::class,
        AddReturnTypeDeclarationBasedOnParentClassMethodRector::class,
        ReturnTypeFromStrictFluentReturnRector::class,
        ReturnNeverTypeRector::class,
        StrictArrayParamDimFetchRector::class,
        StrictStringParamConcatRector::class,
        RemoveAlwaysTrueIfConditionRector::class,
        RenameForeachValueVariableToMatchExprVariableRector::class,
        RemoveUnusedPrivateClassConstantRector::class,
        NewlineAfterStatementRector::class,
        SymplifyQuoteEscapeRector::class,
        DisallowedEmptyRuleFixerRector::class,
        RemoveUnusedPrivateMethodRector::class,
        SortNamedParamRector::class,
        NullToStrictStringFuncCallArgRector::class,
        FunctionFirstClassCallableRector::class,
        RemoveNullArgOnNullDefaultParamRector::class,
    ]);
