<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

describe('Artisan Commands', function(): void {
    afterEach(function(): void {
        // Clean up generated files
        $files = [
            app_path('DTOs/TestUserDTO.php'),
            app_path('DTOs/TestProductDTO.php'),
            storage_path('test-types.ts'),
        ];

        foreach ($files as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        // Clean up DTOs directory if empty
        $dtosDir = app_path('DTOs');
        if (File::isDirectory($dtosDir) && 0 === count(File::files($dtosDir))) {
            File::deleteDirectory($dtosDir);
        }
    });

    it('can generate basic DTO with make:dto', function(): void {
        $exitCode = Artisan::call('make:dto', [
            'name' => 'TestUser',
        ]);

        expect($exitCode)->toBe(0);

        $path = app_path('DTOs/TestUserDTO.php');
        expect(File::exists($path))->toBeTrue();

        $content = File::get($path);
        expect($content)->toContain('namespace E2E\Laravel\DTOs;');
        expect($content)->toContain('class TestUserDTO extends SimpleDTO');
        expect($content)->toContain('public readonly string $name');
        expect($content)->toContain('public readonly string $email');
        expect($content)->not->toContain('#[Required]');
        expect($content)->not->toContain('DataCollection');
    });

    it('can generate DTO with validation attributes', function(): void {
        $exitCode = Artisan::call('make:dto', [
            'name' => 'TestUser',
            '--validation' => true,
        ]);

        expect($exitCode)->toBe(0);

        $path = app_path('DTOs/TestUserDTO.php');
        $content = File::get($path);

        expect($content)->toContain('#[Required]');
        expect($content)->toContain('#[Email]');
        expect($content)->toContain('use event4u\DataHelpers\SimpleDTO\Attributes\Email;');
        expect($content)->toContain('use event4u\DataHelpers\SimpleDTO\Attributes\Required;');
    });

    it('can generate DTO with collection support', function(): void {
        $exitCode = Artisan::call('make:dto', [
            'name' => 'TestUser',
            '--collection' => true,
        ]);

        expect($exitCode)->toBe(0);

        $path = app_path('DTOs/TestUserDTO.php');
        $content = File::get($path);

        expect($content)->toContain('DataCollection');
        expect($content)->toContain('#[DataCollectionOf(ItemDTO::class)]');
        expect($content)->toContain('use event4u\DataHelpers\SimpleDTO\Attributes\DataCollectionOf;');
        expect($content)->toContain('use event4u\DataHelpers\SimpleDTO\DataCollection;');
    });

    it('can generate resource DTO', function(): void {
        $exitCode = Artisan::call('make:dto', [
            'name' => 'TestUser',
            '--resource' => true,
        ]);

        expect($exitCode)->toBe(0);

        $path = app_path('DTOs/TestUserDTO.php');
        $content = File::get($path);

        expect($content)->toContain('public readonly int $id');
        expect($content)->toContain('public readonly ?string $description = null');
        expect($content)->toContain('public readonly ?DateTimeImmutable $createdAt = null');
        expect($content)->toContain('public readonly ?DateTimeImmutable $updatedAt = null');
        expect($content)->toContain('protected function casts(): array');
        expect($content)->toContain("'createdAt' => 'datetime'");
        expect($content)->toContain("'updatedAt' => 'datetime'");
    });

    it('can generate resource DTO with all options', function(): void {
        $exitCode = Artisan::call('make:dto', [
            'name' => 'TestUser',
            '--resource' => true,
            '--validation' => true,
            '--collection' => true,
        ]);

        expect($exitCode)->toBe(0);

        $path = app_path('DTOs/TestUserDTO.php');
        $content = File::get($path);

        expect($content)->toContain('#[Required]');
        expect($content)->toContain('#[Email]');
        expect($content)->toContain('#[Min(3)]');
        expect($content)->toContain('#[Max(255)]');
        expect($content)->toContain('DataCollection');
        expect($content)->toContain('#[DataCollectionOf(ItemDTO::class)]');
        expect($content)->toContain('public readonly int $id');
        expect($content)->toContain('protected function casts(): array');
    });

    it('automatically adds DTO suffix', function(): void {
        $exitCode = Artisan::call('make:dto', [
            'name' => 'TestUser',
        ]);

        expect($exitCode)->toBe(0);

        $path = app_path('DTOs/TestUserDTO.php');
        expect(File::exists($path))->toBeTrue();

        $content = File::get($path);
        expect($content)->toContain('class TestUserDTO extends SimpleDTO');
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
        // Create a DTO first
        Artisan::call('make:dto', [
            'name' => 'TestProduct',
            '--resource' => true,
        ]);

        // Dump autoload to make class available
        exec('cd ' . base_path() . ' && composer dump-autoload 2>&1');

        // Generate TypeScript
        $exitCode = Artisan::call('dto:typescript', [
            '--path' => 'app/DTOs',
            '--output' => 'storage/test-types.ts',
        ]);

        expect($exitCode)->toBe(0);

        $path = storage_path('test-types.ts');
        expect(File::exists($path))->toBeTrue();

        $content = File::get($path);
        expect($content)->toContain('export interface TestProductDTO');
        expect($content)->toContain('id: number');
        expect($content)->toContain('name: string');
        expect($content)->toContain('email: string');
        expect($content)->toContain('description: string | null');
        expect($content)->toContain('createdAt: string');
        expect($content)->toContain('updatedAt: string');
    });
})->group('laravel');

