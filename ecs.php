<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\CastNotation\CastSpacesFixer;
use PhpCsFixer\Fixer\ClassNotation\FinalInternalClassFixer;
use PhpCsFixer\Fixer\ClassNotation\NoNullPropertyInitializationFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\ClassNotation\SelfAccessorFixer;
use PhpCsFixer\Fixer\ClassNotation\SingleClassElementPerStatementFixer;
use PhpCsFixer\Fixer\ConstantNotation\NativeConstantInvocationFixer;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer;
use PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer;
use PhpCsFixer\Fixer\FunctionNotation\StaticLambdaFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\LanguageConstruct\DeclareEqualNormalizeFixer;
use PhpCsFixer\Fixer\Operator\IncrementStyleFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocIndentFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSeparationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSummaryFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocToCommentFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitInternalClassFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitStrictFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestCaseStaticMethodCallsFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestClassRequiresCoversFixer;
use PhpCsFixer\Fixer\ReturnNotation\ReturnAssignmentFixer;
use PhpCsFixer\Fixer\Semicolon\MultilineWhitespaceBeforeSemicolonsFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;
use PhpCsFixer\Fixer\Whitespace\LineEndingFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use PhpCsFixer\Fixer\Whitespace\SingleBlankLineAtEofFixer;
use Symplify\CodingStandard\Fixer\Annotation\RemovePropertyVariableNameDescriptionFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveUselessDefaultCommentFixer;
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\CodingStandard\Fixer\Spacing\MethodChainingNewlineFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/benchmarks',
        __DIR__ . '/examples',
        __DIR__ . '/scripts',
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/types',
    ])
    ->withRootFiles()
    ->withRules([
        TrailingCommaInMultilineFixer::class,
        LineEndingFixer::class,
        SingleBlankLineAtEofFixer::class,
    ])
    ->withConfiguredRule(CastSpacesFixer::class, [
        'space' => 'none',
    ])
    ->withConfiguredRule(DeclareEqualNormalizeFixer::class, [
        'space' => 'none',
    ])
    ->withConfiguredRule(FunctionDeclarationFixer::class, [
        'closure_fn_spacing' => 'none',
        'closure_function_spacing' => 'none',
        'trailing_comma_single_line' => false,
    ])
    ->withConfiguredRule(GlobalNamespaceImportFixer::class, [
        'import_classes' => true,
        'import_constants' => null,
        'import_functions' => null,
    ])
    ->withConfiguredRule(IncrementStyleFixer::class, [
        'style' => 'post',
    ])
    ->withConfiguredRule(LineLengthFixer::class, [
        'line_length' => 120,
        'break_long_lines' => true,
        'inline_short_lines' => false,
    ])
    ->withConfiguredRule(NoExtraBlankLinesFixer::class, [
        'tokens' => [
            'case',
            'continue',
            'curly_brace_block',
            'default',
            'extra',
            'return',
            'square_brace_block',
            'switch',
            'throw',
            'use',
        ],
    ])
    ->withConfiguredRule(OrderedImportsFixer::class, [
        'sort_algorithm' => 'alpha',
        'imports_order' => [
            'class',
            'function',
            'const',
        ],
    ])
    ->withConfiguredRule(PhpdocLineSpanFixer::class, [
        'const' => 'single',
        'property' => 'single',
        'method' => 'single',
    ])
    ->withConfiguredRule(SingleClassElementPerStatementFixer::class, [
        'elements' => [
            'const',
            'property',
        ],
    ])
    ->withConfiguredRule(YodaStyleFixer::class, [
        'always_move_variable' => true,
        'equal' => true,
        'identical' => true,
        'less_and_greater' => true,
    ])
    ->withSkip([
        // Skip rules
        OrderedClassElementsFixer::class,
        MethodChainingNewlineFixer::class,
        MultilineWhitespaceBeforeSemicolonsFixer::class,
        PhpdocToCommentFixer::class,
        NativeFunctionInvocationFixer::class,
        NativeConstantInvocationFixer::class,
        PhpdocSeparationFixer::class,
        PhpdocSummaryFixer::class,
        PhpUnitTestClassRequiresCoversFixer::class,
        NotOperatorWithSuccessorSpaceFixer::class,
        PhpdocAlignFixer::class,
        SelfAccessorFixer::class,
        RemovePropertyVariableNameDescriptionFixer::class,
        NoNullPropertyInitializationFixer::class,
        ReturnAssignmentFixer::class,
        StaticLambdaFixer::class,
        FinalInternalClassFixer::class,
        PhpUnitInternalClassFixer::class,
        PhpUnitTestCaseStaticMethodCallsFixer::class,
        PhpUnitStrictFixer::class,
        GeneralPhpdocAnnotationRemoveFixer::class,
        RemoveUselessDefaultCommentFixer::class,
        PhpdocIndentFixer::class,
        DeclareStrictTypesFixer::class,
        StrictComparisonFixer::class,

        // Skip paths - don't replace FQN in Support classes
        __DIR__ . '/src/Support',

        // Skip E2E test vendor directories and lock files
        __DIR__ . '/tests-e2e/*/vendor',
        __DIR__ . '/tests-e2e/*/composer.lock',
    ]);
