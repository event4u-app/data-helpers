<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

describe('Artisan Commands', function(): void {
    afterEach(function(): void {
        // Clean up generated files
        $files = [
            app_path('Dtos/TestUserDto.php'),
            app_path('Dtos/TestProductDto.php'),
            app_path('Dtos/SpatieUserData.php'),
            app_path('Dtos/SpatieUserData.php.backup'),
            app_path('Data/SpatieTestData.php'),
            app_path('Data/SpatieTestData.php.backup'),
            storage_path('test-types.ts'),
        ];

        foreach ($files as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        // Clean up directories if empty
        $dirs = [
            app_path('Dtos'),
            app_path('Data'),
        ];

        foreach ($dirs as $dir) {
            if (File::isDirectory($dir) && 0 === count(File::files($dir))) {
                File::deleteDirectory($dir);
            }
        }
    });

    it('can generate basic Dto with make:dto', function(): void {
        $exitCode = Artisan::call('make:dto', [
            'name' => 'TestUser',
        ]);

        expect($exitCode)->toBe(0);

        $path = app_path('Dtos/TestUserDto.php');
        expect(File::exists($path))->toBeTrue();

        $content = File::get($path);
        expect($content)->toContain('namespace E2E\Laravel\Dtos;');
        expect($content)->toContain('class TestUserDto extends SimpleDto');
        expect($content)->toContain('public readonly string $name');
        expect($content)->toContain('public readonly string $email');
        expect($content)->not->toContain('#[Required]');
        expect($content)->not->toContain('DataCollection');
    });

    it('can generate Dto with validation attributes', function(): void {
        $exitCode = Artisan::call('make:dto', [
            'name' => 'TestUser',
            '--validation' => true,
        ]);

        expect($exitCode)->toBe(0);

        $path = app_path('Dtos/TestUserDto.php');
        $content = File::get($path);

        expect($content)->toContain('#[Required]');
        expect($content)->toContain('#[Email]');
        expect($content)->toContain('use event4u\DataHelpers\SimpleDto\Attributes\Email;');
        expect($content)->toContain('use event4u\DataHelpers\SimpleDto\Attributes\Required;');
    });

    it('can generate Dto with collection support', function(): void {
        $exitCode = Artisan::call('make:dto', [
            'name' => 'TestUser',
            '--collection' => true,
        ]);

        expect($exitCode)->toBe(0);

        $path = app_path('Dtos/TestUserDto.php');
        $content = File::get($path);

        expect($content)->toContain('DataCollection');
        expect($content)->toContain('#[DataCollectionOf(ItemDto::class)]');
        expect($content)->toContain('use event4u\DataHelpers\SimpleDto\Attributes\DataCollectionOf;');
        expect($content)->toContain('use event4u\DataHelpers\SimpleDto\DataCollection;');
    });

    it('can generate resource Dto', function(): void {
        $exitCode = Artisan::call('make:dto', [
            'name' => 'TestUser',
            '--resource' => true,
        ]);

        expect($exitCode)->toBe(0);

        $path = app_path('Dtos/TestUserDto.php');
        $content = File::get($path);

        expect($content)->toContain('public readonly int $id');
        expect($content)->toContain('public readonly ?string $description = null');
        expect($content)->toContain('public readonly ?DateTimeImmutable $createdAt = null');
        expect($content)->toContain('public readonly ?DateTimeImmutable $updatedAt = null');
        expect($content)->toContain('protected function casts(): array');
        expect($content)->toContain("'createdAt' => 'datetime'");
        expect($content)->toContain("'updatedAt' => 'datetime'");
    });

    it('can generate resource Dto with all options', function(): void {
        $exitCode = Artisan::call('make:dto', [
            'name' => 'TestUser',
            '--resource' => true,
            '--validation' => true,
            '--collection' => true,
        ]);

        expect($exitCode)->toBe(0);

        $path = app_path('Dtos/TestUserDto.php');
        $content = File::get($path);

        expect($content)->toContain('#[Required]');
        expect($content)->toContain('#[Email]');
        expect($content)->toContain('#[Min(3)]');
        expect($content)->toContain('#[Max(255)]');
        expect($content)->toContain('DataCollection');
        expect($content)->toContain('#[DataCollectionOf(ItemDto::class)]');
        expect($content)->toContain('public readonly int $id');
        expect($content)->toContain('protected function casts(): array');
    });

    it('automatically adds Dto suffix', function(): void {
        $exitCode = Artisan::call('make:dto', [
            'name' => 'TestUser',
        ]);

        expect($exitCode)->toBe(0);

        $path = app_path('Dtos/TestUserDto.php');
        expect(File::exists($path))->toBeTrue();

        $content = File::get($path);
        expect($content)->toContain('class TestUserDto extends SimpleDto');
    });

    it('fails if file exists without force', function(): void {
        // Create file first
        Artisan::call('make:dto', ['name' => 'TestUser']);

        // Try to create again without force
        $exitCode = Artisan::call('make:dto', ['name' => 'TestUser']);

        expect($exitCode)->toBe(1);
    });

    it('overwrites file with force option', function(): void {
        // Create file first
        Artisan::call('make:dto', ['name' => 'TestUser']);

        // Overwrite with force
        $exitCode = Artisan::call('make:dto', [
            'name' => 'TestUser',
            '--force' => true,
        ]);

        expect($exitCode)->toBe(0);
    });

    it('can generate TypeScript interfaces with dto:typescript', function(): void {
        // Create a Dto first
        Artisan::call('make:dto', [
            'name' => 'TestProduct',
            '--resource' => true,
        ]);

        // Dump autoload to make class available
        exec('cd ' . base_path() . ' && composer dump-autoload 2>&1');

        // Generate TypeScript
        $exitCode = Artisan::call('dto:typescript', [
            '--path' => 'app/Dtos',
            '--output' => 'storage/test-types.ts',
        ]);

        expect($exitCode)->toBe(0);

        $path = storage_path('test-types.ts');
        expect(File::exists($path))->toBeTrue();

        $content = File::get($path);
        expect($content)->toContain('export interface TestProductDto');
        expect($content)->toContain('id: number');
        expect($content)->toContain('name: string');
        expect($content)->toContain('email: string');
        expect($content)->toContain('description: string | null');
        expect($content)->toContain('createdAt: string');
        expect($content)->toContain('updatedAt: string');
    });

    it('can migrate Spatie Data class with dto:migrate-spatie', function(): void {
        // Create a Spatie Data class
        $spatieClass = <<<'PHP'
<?php

declare(strict_types=1);

namespace E2E\Laravel\Dtos;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Email;

class SpatieUserData extends Data
{
    public function __construct(
        #[Required]
        public string $name,
        #[Required, Email]
        public string $email,
        public int $age,
    ) {}
}
PHP;

        $path = app_path('Dtos/SpatieUserData.php');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $spatieClass);

        // Run migration
        $exitCode = Artisan::call('dto:migrate-spatie', [
            '--path' => 'app/Dtos',
            '--force' => true,
        ]);

        expect($exitCode)->toBe(0);

        // Check migrated content
        $content = File::get($path);

        // Check base class changed
        expect($content)->toContain('use event4u\DataHelpers\SimpleDto\SimpleDto;');
        expect($content)->toContain('class SpatieUserData extends SimpleDto');
        expect($content)->not->toContain('use Spatie\LaravelData\Data;');
        expect($content)->not->toContain('extends Data');

        // Check attributes namespace changed
        expect($content)->toContain('use event4u\DataHelpers\SimpleDto\Attributes\Required;');
        expect($content)->toContain('use event4u\DataHelpers\SimpleDto\Attributes\Email;');
        expect($content)->not->toContain('use Spatie\LaravelData\Attributes\Validation\Required;');
        expect($content)->not->toContain('use Spatie\LaravelData\Attributes\Validation\Email;');

        // Check readonly added to properties
        expect($content)->toContain('public readonly string $name');
        expect($content)->toContain('public readonly string $email');
        expect($content)->toContain('public readonly int $age');
    });

    it('can migrate with --dry-run option', function(): void {
        // Create a Spatie Data class
        $spatieClass = <<<'PHP'
<?php

declare(strict_types=1);

namespace E2E\Laravel\Dtos;

use Spatie\LaravelData\Data;

class SpatieUserData extends Data
{
    public function __construct(
        public string $name,
    ) {}
}
PHP;

        $path = app_path('Dtos/SpatieUserData.php');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $spatieClass);

        $originalContent = File::get($path);

        // Run migration with dry-run
        $exitCode = Artisan::call('dto:migrate-spatie', [
            '--path' => 'app/Dtos',
            '--dry-run' => true,
            '--force' => true,
        ]);

        expect($exitCode)->toBe(0);

        // Check file was NOT modified
        $content = File::get($path);
        expect($content)->toBe($originalContent);
        expect($content)->toContain('use Spatie\LaravelData\Data;');
        expect($content)->toContain('extends Data');
    });

    it('can create backup files with --backup option', function(): void {
        // Create a Spatie Data class
        $spatieClass = <<<'PHP'
<?php

declare(strict_types=1);

namespace E2E\Laravel\Dtos;

use Spatie\LaravelData\Data;

class SpatieUserData extends Data
{
    public function __construct(
        public string $name,
    ) {}
}
PHP;

        $path = app_path('Dtos/SpatieUserData.php');
        $backupPath = $path . '.backup';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $spatieClass);

        // Run migration with backup
        $exitCode = Artisan::call('dto:migrate-spatie', [
            '--path' => 'app/Dtos',
            '--backup' => true,
            '--force' => true,
        ]);

        expect($exitCode)->toBe(0);

        // Check backup file exists
        expect(File::exists($backupPath))->toBeTrue();

        // Check backup contains original content
        $backupContent = File::get($backupPath);
        expect($backupContent)->toContain('use Spatie\LaravelData\Data;');
        expect($backupContent)->toContain('extends Data');

        // Check original file was migrated
        $content = File::get($path);
        expect($content)->toContain('use event4u\DataHelpers\SimpleDto\SimpleDto;');
        expect($content)->toContain('extends SimpleDto');
    });

    it('can migrate WithCast attribute to Cast', function(): void {
        // Create a Spatie Data class with WithCast
        $spatieClass = <<<'PHP'
<?php

declare(strict_types=1);

namespace E2E\Laravel\Dtos;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\WithCast;

class SpatieUserData extends Data
{
    public function __construct(
        #[WithCast(DateTimeImmutableCast::class)]
        public string $createdAt,
    ) {}
}
PHP;

        $path = app_path('Dtos/SpatieUserData.php');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $spatieClass);

        // Run migration
        $exitCode = Artisan::call('dto:migrate-spatie', [
            '--path' => 'app/Dtos',
            '--force' => true,
        ]);

        expect($exitCode)->toBe(0);

        // Check WithCast was replaced with Cast
        $content = File::get($path);
        expect($content)->toContain('#[Cast(DateTimeImmutableCast::class)]');
        expect($content)->not->toContain('#[WithCast(');
    });

    it('handles multiple Spatie Data classes in directory', function(): void {
        // Create multiple Spatie Data classes
        $spatieClass1 = <<<'PHP'
<?php

namespace E2E\Laravel\Data;

use Spatie\LaravelData\Data;

class SpatieTestData extends Data
{
    public function __construct(public string $name) {}
}
PHP;

        $spatieClass2 = <<<'PHP'
<?php

namespace E2E\Laravel\Dtos;

use Spatie\LaravelData\Data;

class SpatieUserData extends Data
{
    public function __construct(public string $email) {}
}
PHP;

        $path1 = app_path('Data/SpatieTestData.php');
        $path2 = app_path('Dtos/SpatieUserData.php');

        File::ensureDirectoryExists(dirname($path1));
        File::ensureDirectoryExists(dirname($path2));
        File::put($path1, $spatieClass1);
        File::put($path2, $spatieClass2);

        // Run migration on app directory (should find both)
        $exitCode = Artisan::call('dto:migrate-spatie', [
            '--path' => 'app',
            '--force' => true,
        ]);

        expect($exitCode)->toBe(0);

        // Check both files were migrated
        $content1 = File::get($path1);
        expect($content1)->toContain('use event4u\DataHelpers\SimpleDto\SimpleDto;');
        expect($content1)->toContain('extends SimpleDto');
        expect($content1)->toContain('public readonly string $name');

        $content2 = File::get($path2);
        expect($content2)->toContain('use event4u\DataHelpers\SimpleDto\SimpleDto;');
        expect($content2)->toContain('extends SimpleDto');
        expect($content2)->toContain('public readonly string $email');
    });
})->group('laravel');

