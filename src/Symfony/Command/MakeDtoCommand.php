<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Symfony\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Console command to generate SimpleDTO classes.
 *
 * Usage:
 *   bin/console make:dto UserDTO
 *   bin/console make:dto UserDTO --validation
 *   bin/console make:dto UserDTO --collection
 *   bin/console make:dto UserDTO --resource
 */
#[AsCommand(
    name: 'make:dto',
    description: 'Create a new SimpleDTO class',
)]
class MakeDtoCommand extends Command
{
    private Filesystem $filesystem;
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        parent::__construct();
        $this->filesystem = new Filesystem();
        $this->projectDir = $projectDir;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the DTO class')
            ->addOption('validation', null, InputOption::VALUE_NONE, 'Add validation attributes')
            ->addOption('collection', null, InputOption::VALUE_NONE, 'Add DataCollection support')
            ->addOption('resource', null, InputOption::VALUE_NONE, 'Generate a resource DTO with common fields')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite existing file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $input->getArgument('name');
        $validation = $input->getOption('validation');
        $collection = $input->getOption('collection');
        $resource = $input->getOption('resource');
        $force = $input->getOption('force');

        // Ensure name ends with DTO
        if (!str_ends_with($name, 'DTO')) {
            $name .= 'DTO';
        }

        // Get path
        $path = $this->getPath($name);

        // Check if file exists
        if ($this->filesystem->exists($path) && !$force) {
            $io->error("DTO [{$name}] already exists!");
            $io->info('Use --force to overwrite.');

            return Command::FAILURE;
        }

        // Create directory if needed
        $directory = dirname($path);
        if (!$this->filesystem->exists($directory)) {
            $this->filesystem->mkdir($directory, 0755);
        }

        // Generate content
        $content = $this->generateContent($name, $validation, $collection, $resource);

        // Write file
        $this->filesystem->dumpFile($path, $content);

        $io->success("DTO [{$name}] created successfully.");
        $io->info("Location: {$path}");

        return Command::SUCCESS;
    }

    /** Get the destination path for the DTO. */
    protected function getPath(string $name): string
    {
        return $this->projectDir . '/src/DTO/' . $name . '.php';
    }

    /** Generate the DTO content. */
    protected function generateContent(string $name, bool $validation, bool $collection, bool $resource): string
    {
        $namespace = 'App\\DTO';
        $className = $name;

        $uses = [
            'use event4u\DataHelpers\SimpleDTO;',
        ];

        if ($validation) {
            $uses[] = 'use event4u\DataHelpers\SimpleDTO\Attributes\Email;';
            $uses[] = 'use event4u\DataHelpers\SimpleDTO\Attributes\Required;';
            $uses[] = 'use event4u\DataHelpers\SimpleDTO\Attributes\Min;';
            $uses[] = 'use event4u\DataHelpers\SimpleDTO\Attributes\Max;';
        }

        if ($collection) {
            $uses[] = 'use event4u\DataHelpers\SimpleDTO\Attributes\DataCollectionOf;';
            $uses[] = 'use event4u\DataHelpers\SimpleDTO\DataCollection;';
        }

        $usesStr = implode("\n", $uses);

        if ($resource) {
            return $this->generateResourceDto($namespace, $className, $usesStr, $validation, $collection);
        }

        return $this->generateBasicDto($namespace, $className, $usesStr, $validation, $collection);
    }

    /** Generate a basic DTO. */
    protected function generateBasicDto(
        string $namespace,
        string $className,
        string $uses,
        bool $validation,
        bool $collection
    ): string {
        $properties = [];

        if ($validation) {
            $properties[] = <<<'PHP'
        #[Required]
        public readonly string $name,
PHP;
            $properties[] = <<<'PHP'

        #[Required]
        #[Email]
        public readonly string $email,
PHP;
        } else {
            $properties[] = <<<'PHP'
        public readonly string $name,
PHP;
            $properties[] = <<<'PHP'

        public readonly string $email,
PHP;
        }

        if ($collection) {
            $properties[] = <<<'PHP'

        #[DataCollectionOf(ItemDTO::class)]
        public readonly DataCollection $items,
PHP;
        }

        $propertiesStr = implode('', $properties);

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

{$uses}

class {$className} extends SimpleDTO
{
    public function __construct(
{$propertiesStr}
    ) {}
}

PHP;
    }

    /** Generate a resource DTO with common fields. */
    protected function generateResourceDto(
        string $namespace,
        string $className,
        string $uses,
        bool $validation,
        bool $collection
    ): string {
        $properties = [];

        if ($validation) {
            $properties[] = <<<'PHP'
        #[Required]
        public readonly int $id,
PHP;
            $properties[] = <<<'PHP'

        #[Required]
        #[Min(3)]
        #[Max(255)]
        public readonly string $name,
PHP;
            $properties[] = <<<'PHP'

        #[Required]
        #[Email]
        public readonly string $email,
PHP;
        } else {
            $properties[] = <<<'PHP'
        public readonly int $id,
PHP;
            $properties[] = <<<'PHP'

        public readonly string $name,
PHP;
            $properties[] = <<<'PHP'

        public readonly string $email,
PHP;
        }

        $properties[] = <<<'PHP'

        public readonly ?string $description = null,
PHP;

        $properties[] = <<<'PHP'

        public readonly ?DateTimeImmutable $createdAt = null,
PHP;

        $properties[] = <<<'PHP'

        public readonly ?DateTimeImmutable $updatedAt = null,
PHP;

        if ($collection) {
            $properties[] = <<<'PHP'

        #[DataCollectionOf(ItemDTO::class)]
        public readonly ?DataCollection $items = null,
PHP;
        }

        $propertiesStr = implode('', $properties);

        $casts = <<<'PHP'

    protected function casts(): array
    {
        return [
            'createdAt' => 'datetime',
            'updatedAt' => 'datetime',
        ];
    }
PHP;

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use DateTimeImmutable;
{$uses}

class {$className} extends SimpleDTO
{
    public function __construct(
{$propertiesStr}
    ) {}
{$casts}
}

PHP;
    }
}

