<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Frameworks\Symfony\Command;

use event4u\DataHelpers\SimpleDto\Config\TypeScriptGeneratorOptions;
use event4u\DataHelpers\SimpleDto\Enums\TypeScriptExportType;
use event4u\DataHelpers\SimpleDto\TypeScriptGenerator;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Throwable;

/**
 * Console command to generate TypeScript interfaces from SimpleDtos.
 *
 * Usage:
 *   bin/console dto:typescript
 *   bin/console dto:typescript --output=assets/types/dtos.ts
 *   bin/console dto:typescript --path=src/Dto
 *   bin/console dto:typescript --watch
 */
#[AsCommand(
    name: 'dto:typescript',
    description: 'Generate TypeScript interfaces from SimpleDtos',
)]
class DtoTypeScriptCommand extends Command
{
    private readonly Filesystem $filesystem;

    public function __construct(private readonly string $projectDir)
    {
        parent::__construct();
        $this->filesystem = new Filesystem();
    }

    protected function configure(): void
    {
        $this
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Output file path', 'assets/types/dtos.ts')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Path to scan for Dtos', 'src/Dto')
            ->addOption(
                'export',
                null,
                InputOption::VALUE_REQUIRED,
                'Export type (export, declare, or empty)',
                'export'
            )
            ->addOption('no-comments', null, InputOption::VALUE_NONE, 'Disable comments in generated interfaces')
            ->addOption('sort', null, InputOption::VALUE_NONE, 'Sort properties alphabetically')
            ->addOption('watch', null, InputOption::VALUE_NONE, 'Watch for changes and regenerate');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $outputPath = (string)$input->getOption('output');
        $path = (string)$input->getOption('path');
        $exportType = (string)$input->getOption('export');
        $includeComments = !$input->getOption('no-comments');
        $sort = (bool)$input->getOption('sort');
        $watch = (bool)$input->getOption('watch');

        // Get full paths
        $outputPath = $this->projectDir . '/' . $outputPath;
        $scanPath = $this->projectDir . '/' . $path;

        if ($watch) {
            return $this->watchMode($io, $scanPath, $outputPath, $exportType, $includeComments, $sort);
        }

        return $this->generateOnce($io, $scanPath, $outputPath, $exportType, $includeComments, $sort);
    }

    /** Generate TypeScript interfaces once. */
    protected function generateOnce(
        SymfonyStyle $io,
        string $scanPath,
        string $outputPath,
        string $exportType,
        bool $includeComments,
        bool $sort
    ): int {
        $io->info('Scanning for Dtos...');

        // Find all Dto classes
        $dtoClasses = $this->findDtoClasses($scanPath);

        if ([] === $dtoClasses) {
            $io->warning('No Dto classes found in ' . $scanPath);

            return Command::FAILURE;
        }

        $io->info('Found ' . count($dtoClasses) . ' Dto classes');

        // Generate TypeScript
        $generator = new TypeScriptGenerator();
        /** @var array<class-string> $dtoClassStrings */
        $dtoClassStrings = $dtoClasses;

        // Build options from command parameters
        $options = new TypeScriptGeneratorOptions(
            exportType: TypeScriptExportType::fromString($exportType) ?? TypeScriptExportType::None,
            includeComments: $includeComments,
            sortProperties: $sort,
        );

        $typescript = $generator->generate($dtoClassStrings, $options);

        // Ensure directory exists
        $directory = dirname($outputPath);
        if (!$this->filesystem->exists($directory)) {
            $this->filesystem->mkdir($directory, 0755);
        }

        // Write file
        $this->filesystem->dumpFile($outputPath, $typescript);

        $io->success('TypeScript interfaces generated successfully!');
        $io->info('Output: ' . $outputPath);
        $io->info('Size: ' . filesize($outputPath) . ' bytes');

        return Command::SUCCESS;
    }

    /** Watch mode - regenerate on file changes. */
    protected function watchMode(
        SymfonyStyle $io,
        string $scanPath,
        string $outputPath,
        string $exportType,
        bool $includeComments,
        bool $sort
    ): int {
        $io->info('👀 Watching for changes...');
        $io->info('📁 Scanning: ' . $scanPath);
        $io->info('📄 Output: ' . $outputPath);
        $io->info('Press Ctrl+C to stop');
        $io->newLine();

        $lastHash = '';

        /** @phpstan-ignore-next-line */
        while (true) {
            // Get current hash of all Dto files
            $currentHash = $this->getDirectoryHash($scanPath);

            if ($currentHash !== $lastHash) {
                $io->info('[' . date('H:i:s') . '] Change detected, regenerating...');

                $result = $this->generateOnce($io, $scanPath, $outputPath, $exportType, $includeComments, $sort);

                if (Command::SUCCESS === $result) {
                    $io->info('[' . date('H:i:s') . '] ✅  Regenerated successfully');
                } else {
                    $io->error('[' . date('H:i:s') . '] ❌  Regeneration failed');
                }

                $io->newLine();
                $lastHash = $currentHash;
            }

            sleep(1);
        }
    }

    /**
     * Find all Dto classes in a directory.
     *
     * @return list<string>
     */
    protected function findDtoClasses(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $finder = new Finder();
        $finder->files()->in($path)->name('*.php');

        $dtoClasses = [];

        foreach ($finder as $file) {
            $className = $this->getClassNameFromFile($file->getPathname());

            if (null === $className) {
                continue;
            }

            // Check if class uses SimpleDtoTrait
            if ($this->isSimpleDto($className)) {
                $dtoClasses[] = $className;
            }
        }

        return $dtoClasses;
    }

    /** Get class name from file. */
    protected function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if (false === $content) {
            return null;
        }

        // Extract namespace
        if (!preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches)) {
            return null;
        }

        $namespace = $namespaceMatches[1];

        // Extract class name
        if (!preg_match('/class\s+(\w+)/', $content, $classMatches)) {
            return null;
        }

        $className = $classMatches[1];
        $fullClassName = $namespace . '\\' . $className;

        // Try to load the class
        if (!class_exists($fullClassName)) {
            require_once $filePath;
        }

        return $fullClassName;
    }

    /** Check if class is a SimpleDto. */
    protected function isSimpleDto(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        try {
            $reflection = new ReflectionClass($className);
            $traits = $this->getAllTraits($reflection);

            return in_array('event4u\DataHelpers\SimpleDto\SimpleDtoTrait', $traits, true);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Get all traits used by a class (including parent classes).
     *
     * @param ReflectionClass<object> $reflection
     * @return array<string>
     */
    protected function getAllTraits(ReflectionClass $reflection): array
    {
        $traits = [];

        // Get traits from current class
        $traits = array_merge($traits, $reflection->getTraitNames());

        // Get traits from parent classes
        $parent = $reflection->getParentClass();
        while (false !== $parent) {
            $traits = array_merge($traits, $parent->getTraitNames());
            $parent = $parent->getParentClass();
        }

        return array_unique($traits);
    }

    /** Get hash of all files in directory. */
    protected function getDirectoryHash(string $path): string
    {
        if (!is_dir($path)) {
            return '';
        }

        $finder = new Finder();
        $finder->files()->in($path)->name('*.php');

        $hashes = [];

        foreach ($finder as $file) {
            /** @phpstan-ignore-next-line */
            $hashes[] = hash_file('sha256', $file->getPathname());
        }

        /** @phpstan-ignore-next-line */
        return hash('sha256', implode('', $hashes));
    }
}
