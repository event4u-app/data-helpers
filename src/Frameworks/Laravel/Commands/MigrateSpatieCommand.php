<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Frameworks\Laravel\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Artisan command to migrate Spatie Laravel Data classes to SimpleDto.
 *
 * Usage:
 *   php artisan dto:migrate-spatie
 *   php artisan dto:migrate-spatie --path=app/Data/Api
 *   php artisan dto:migrate-spatie --dry-run
 *   php artisan dto:migrate-spatie --backup
 *
 * @phpstan-ignore-next-line - Laravel is an optional dependency
 */
class MigrateSpatieCommand extends Command
{
    /** @var Application */
    public $laravel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dto:migrate-spatie
                            {--path=app/Data : Path to scan for Spatie Data classes}
                            {--dry-run : Preview changes without modifying files}
                            {--backup : Create backup files before migration}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Spatie Laravel Data classes to SimpleDto';

    /** Execute the console command. */
    public function handle(Filesystem $files): int
    {
        /** @phpstan-ignore-next-line */
        $path = (string)$this->option('path');
        /** @phpstan-ignore-next-line */
        $dryRun = (bool)$this->option('dry-run');
        /** @phpstan-ignore-next-line */
        $backup = (bool)$this->option('backup');
        /** @phpstan-ignore-next-line */
        $force = (bool)$this->option('force');

        // Get full path
        /** @phpstan-ignore-next-line */
        $scanPath = $this->laravel->basePath($path);

        if (!$files->isDirectory($scanPath)) {
            /** @phpstan-ignore-next-line */
            $this->error('Directory not found: ' . $scanPath);
            /** @phpstan-ignore-next-line - Laravel Command constant */
            return self::FAILURE;
        }

        /** @phpstan-ignore-next-line */
        $this->info('🔍 Scanning for Spatie Data classes...');

        // Find all Spatie Data classes
        $spatieClasses = $this->findSpatieDataClasses($files, $scanPath);

        if ([] === $spatieClasses) {
            /** @phpstan-ignore-next-line */
            $this->warn('No Spatie Data classes found in ' . $scanPath);
            /** @phpstan-ignore-next-line - Laravel Command constant */
            return self::FAILURE;
        }

        /** @phpstan-ignore-next-line */
        $this->info('Found ' . count($spatieClasses) . ' Spatie Data classes');
        /** @phpstan-ignore-next-line */
        $this->newLine();

        // Show files to be migrated
        foreach ($spatieClasses as $file) {
            /** @phpstan-ignore-next-line */
            $this->info('  - ' . str_replace($this->laravel->basePath() . '/', '', $file));
        }

        /** @phpstan-ignore-next-line */
        $this->newLine();

        if ($dryRun) {
            /** @phpstan-ignore-next-line */
            $this->info('🔍 DRY RUN MODE - No files will be modified');
            /** @phpstan-ignore-next-line */
            $this->newLine();
        }

        // Confirm migration
        /** @phpstan-ignore-next-line */
        if (!$force && !$dryRun && !$this->confirm('Do you want to proceed with the migration?')) {
            /** @phpstan-ignore-next-line */
            $this->info('Migration cancelled.');
            /** @phpstan-ignore-next-line - Laravel Command constant */
            return self::SUCCESS;
        }

        // Migrate files
        $migrated = 0;
        $failed = 0;

        foreach ($spatieClasses as $file) {
            try {
                $this->migrateFile($files, $file, $dryRun, $backup);
                $migrated++;
            } catch (Exception $e) {
                /** @phpstan-ignore-next-line */
                $this->error('Failed to migrate ' . $file . ': ' . $e->getMessage());
                $failed++;
            }
        }

        /** @phpstan-ignore-next-line */
        $this->newLine();

        if ($dryRun) {
            /** @phpstan-ignore-next-line */
            $this->info(sprintf('✅  Would migrate %d files', $migrated));
        } else {
            /** @phpstan-ignore-next-line */
            $this->info(sprintf('✅  Successfully migrated %d files', $migrated));
        }

        if (0 < $failed) {
            /** @phpstan-ignore-next-line */
            $this->error(sprintf('❌  Failed to migrate %d files', $failed));
        }

        if (!$dryRun && 0 < $migrated) {
            /** @phpstan-ignore-next-line */
            $this->newLine();
            /** @phpstan-ignore-next-line */
            $this->info('📝 Next steps:');
            /** @phpstan-ignore-next-line */
            $this->info('  1. Review the migrated files');
            /** @phpstan-ignore-next-line */
            $this->info('  2. Run your tests');
            /** @phpstan-ignore-next-line */
            $this->info('  3. Remove Spatie Data package: composer remove spatie/laravel-data');
        }

        /** @phpstan-ignore-next-line - Laravel Command constants */
        return 0 < $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Find all Spatie Data classes in a directory.
     *
     * @return list<string>
     */
    protected function findSpatieDataClasses(Filesystem $files, string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $finder = new Finder();
        $finder->files()->in($path)->name('*.php');

        $spatieClasses = [];

        foreach ($finder as $file) {
            $content = $files->get($file->getPathname());

            // Check if file uses Spatie Data
            if (str_contains($content, 'Spatie\\LaravelData\\Data')) {
                $spatieClasses[] = $file->getPathname();
            }
        }

        return $spatieClasses;
    }

    /** Migrate a single file. */
    protected function migrateFile(Filesystem $files, string $filePath, bool $dryRun, bool $backup): void
    {
        $content = $files->get($filePath);
        $originalContent = $content;

        // 1. Replace base class import
        $content = str_replace(
            'use Spatie\\LaravelData\\Data;',
            'use event4u\\DataHelpers\\SimpleDto\\SimpleDto;',
            $content
        );

        // 2. Replace extends Data
        $content = preg_replace(
            '/extends\s+Data\b/',
            'extends SimpleDto',
            $content
        ) ?? $content;

        // 3. Replace attribute namespaces
        $content = str_replace(
            'use Spatie\\LaravelData\\Attributes\\Validation\\',
            'use event4u\\DataHelpers\\SimpleDto\\Attributes\\',
            $content
        );

        $content = str_replace(
            'use Spatie\\LaravelData\\Attributes\\',
            'use event4u\\DataHelpers\\SimpleDto\\Attributes\\',
            $content
        );

        // 4. Replace DataCollection
        $content = str_replace(
            'use Spatie\\LaravelData\\DataCollection;',
            'use event4u\\DataHelpers\\SimpleDto\\DataCollection;',
            $content
        );

        // 5. Replace WithCast attribute with Cast
        $content = str_replace('#[WithCast(', '#[Cast(', $content);
        $content = str_replace(
            'use event4u\\DataHelpers\\SimpleDto\\Attributes\\WithCast;',
            'use event4u\\DataHelpers\\SimpleDto\\Attributes\\Cast;',
            $content
        );

        // 6. Add readonly to public properties (if not already present)
        // This matches both regular properties and constructor properties
        // Pattern: public (not followed by readonly or function) -> public readonly
        $content = preg_replace(
            '/\bpublic\s+(?!readonly\b)(?!function\b)/',
            'public readonly ',
            $content
        ) ?? $content;

        if ($dryRun) {
            /** @phpstan-ignore-next-line */
            $this->info('Would migrate: ' . $filePath);
            return;
        }

        // Create backup if requested
        if ($backup) {
            $backupPath = $filePath . '.backup';
            $files->copy($filePath, $backupPath);
        }

        // Write migrated content
        $files->put($filePath, $content);

        /** @phpstan-ignore-next-line */
        $this->info('✅  Migrated: ' . $filePath);
    }
}
