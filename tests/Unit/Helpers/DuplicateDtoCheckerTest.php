<?php

declare(strict_types=1);

use Tests\Unit\Helpers\DuplicateDtoChecker;

describe('DuplicateDtoChecker', function(): void {
    test('it finds no duplicates in a clean directory', function(): void {
        /** @phpstan-ignore-next-line uniqid() is fine for test temp directories */
        $tempDir = sys_get_temp_dir() . '/dto_checker_test_' . uniqid();
        mkdir($tempDir);

        // Create a test file with a DTO
        file_put_contents($tempDir . '/Test1.php', '<?php
namespace Tests;
use event4u\DataHelpers\SimpleDto;
class UserDto extends SimpleDto {}
');

        ob_start();
        $duplicates = DuplicateDtoChecker::check($tempDir, false);
        ob_end_clean();

        expect($duplicates)->toBeEmpty();

        // Cleanup
        unlink($tempDir . '/Test1.php');
        rmdir($tempDir);
    });

    test('it finds duplicates when same class name is used in multiple files', function(): void {
        /** @phpstan-ignore-next-line uniqid() is fine for test temp directories */
        $tempDir = sys_get_temp_dir() . '/dto_checker_test_' . uniqid();
        mkdir($tempDir);

        // Create two test files with the same DTO class name
        file_put_contents($tempDir . '/Test1.php', '<?php
namespace Tests;
use event4u\DataHelpers\SimpleDto;
class UserDto extends SimpleDto {}
');

        file_put_contents($tempDir . '/Test2.php', '<?php
namespace Tests;
use event4u\DataHelpers\SimpleDto;
class UserDto extends SimpleDto {}
');

        ob_start();
        $duplicates = DuplicateDtoChecker::check($tempDir, false);
        ob_end_clean();

        expect($duplicates)->toHaveKey('Tests\UserDto')
            ->and($duplicates['Tests\UserDto'])->toHaveCount(2);

        // Cleanup
        unlink($tempDir . '/Test1.php');
        unlink($tempDir . '/Test2.php');
        rmdir($tempDir);
    });

    test('it ignores Fixtures directories', function(): void {
        /** @phpstan-ignore-next-line uniqid() is fine for test temp directories */
        $tempDir = sys_get_temp_dir() . '/dto_checker_test_' . uniqid();
        mkdir($tempDir);
        mkdir($tempDir . '/Fixtures');

        // Create a DTO in main directory
        file_put_contents($tempDir . '/Test1.php', '<?php
namespace Tests;
use event4u\DataHelpers\SimpleDto;
class UserDto extends SimpleDto {}
');

        // Create the same DTO in Fixtures directory (should be ignored)
        file_put_contents($tempDir . '/Fixtures/Test2.php', '<?php
namespace Tests;
use event4u\DataHelpers\SimpleDto;
class UserDto extends SimpleDto {}
');

        ob_start();
        $duplicates = DuplicateDtoChecker::check($tempDir, false);
        ob_end_clean();

        expect($duplicates)->toBeEmpty();

        // Cleanup
        unlink($tempDir . '/Test1.php');
        unlink($tempDir . '/Fixtures/Test2.php');
        rmdir($tempDir . '/Fixtures');
        rmdir($tempDir);
    });

    test('it handles classes without namespace', function(): void {
        /** @phpstan-ignore-next-line uniqid() is fine for test temp directories */
        $tempDir = sys_get_temp_dir() . '/dto_checker_test_' . uniqid();
        mkdir($tempDir);

        // Create two test files with the same DTO class name (no namespace)
        file_put_contents($tempDir . '/Test1.php', '<?php
use event4u\DataHelpers\SimpleDto;
class ProductDto extends SimpleDto {}
');

        file_put_contents($tempDir . '/Test2.php', '<?php
use event4u\DataHelpers\SimpleDto;
class ProductDto extends SimpleDto {}
');

        ob_start();
        $duplicates = DuplicateDtoChecker::check($tempDir, false);
        ob_end_clean();

        expect($duplicates)->toHaveKey('ProductDto')
            ->and($duplicates['ProductDto'])->toHaveCount(2);

        // Cleanup
        unlink($tempDir . '/Test1.php');
        unlink($tempDir . '/Test2.php');
        rmdir($tempDir);
    });

    test('it handles LiteDto classes', function(): void {
        /** @phpstan-ignore-next-line uniqid() is fine for test temp directories */
        $tempDir = sys_get_temp_dir() . '/dto_checker_test_' . uniqid();
        mkdir($tempDir);

        // Create two test files with the same LiteDto class name
        file_put_contents($tempDir . '/Test1.php', '<?php
use event4u\DataHelpers\LiteDto;
class FastDto extends LiteDto {}
');

        file_put_contents($tempDir . '/Test2.php', '<?php
use event4u\DataHelpers\LiteDto;
class FastDto extends LiteDto {}
');

        ob_start();
        $duplicates = DuplicateDtoChecker::check($tempDir, false);
        ob_end_clean();

        expect($duplicates)->toHaveKey('FastDto')
            ->and($duplicates['FastDto'])->toHaveCount(2);

        // Cleanup
        unlink($tempDir . '/Test1.php');
        unlink($tempDir . '/Test2.php');
        rmdir($tempDir);
    });

    test('it throws exception when throwOnDuplicate is true', function(): void {
        /** @phpstan-ignore-next-line uniqid() is fine for test temp directories */
        $tempDir = sys_get_temp_dir() . '/dto_checker_test_' . uniqid();
        mkdir($tempDir);

        // Create two test files with the same DTO class name
        file_put_contents($tempDir . '/Test1.php', '<?php
class DuplicateDto extends \event4u\DataHelpers\SimpleDto\SimpleDto {}
');

        file_put_contents($tempDir . '/Test2.php', '<?php
class DuplicateDto extends \event4u\DataHelpers\SimpleDto\SimpleDto {}
');

        expect(fn(): array => DuplicateDtoChecker::check($tempDir, true))
            ->toThrow(RuntimeException::class);

        // Cleanup
        unlink($tempDir . '/Test1.php');
        unlink($tempDir . '/Test2.php');
        rmdir($tempDir);
    });

    test('it handles different namespaces correctly', function(): void {
        /** @phpstan-ignore-next-line uniqid() is fine for test temp directories */
        $tempDir = sys_get_temp_dir() . '/dto_checker_test_' . uniqid();
        mkdir($tempDir);

        // Create two test files with the same class name but different namespaces
        file_put_contents($tempDir . '/Test1.php', '<?php
namespace Tests\Unit;
use event4u\DataHelpers\SimpleDto;
class UserDto extends SimpleDto {}
');

        file_put_contents($tempDir . '/Test2.php', '<?php
namespace Tests\Integration;
use event4u\DataHelpers\SimpleDto;
class UserDto extends SimpleDto {}
');

        ob_start();
        $duplicates = DuplicateDtoChecker::check($tempDir, false);
        ob_end_clean();

        // Different namespaces = no duplicates
        expect($duplicates)->toBeEmpty();

        // Cleanup
        unlink($tempDir . '/Test1.php');
        unlink($tempDir . '/Test2.php');
        rmdir($tempDir);
    });
});
