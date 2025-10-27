<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Frameworks\Symfony\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Console command to generate SimpleDto classes.
 *
 * Usage:
 *   bin/console make:dto UserDto
 *   bin/console make:dto UserDto --validation
 *   bin/console make:dto UserDto --collection
 *   bin/console make:dto UserDto --resource
 */
#[AsCommand(
    name: 'make:dto',
    description: 'Create a new SimpleDto class',
)]
class MakeDtoCommand extends Command
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
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the Dto class')
            ->addOption('validation', null, InputOption::VALUE_NONE, 'Add validation attributes')
            ->addOption('collection', null, InputOption::VALUE_NONE, 'Add DataCollection support')
            ->addOption('resource', null, InputOption::VALUE_NONE, 'Generate a resource Dto with common fields')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite existing file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = (string)$input->getArgument('name');
        $validation = (bool)$input->getOption('validation');
        $collection = (bool)$input->getOption('collection');
        $resource = (bool)$input->getOption('resource');
        $force = (bool)$input->getOption('force');

        // Ensure name ends with Dto
        if (!str_ends_with($name, 'Dto')) {
            $name .= 'Dto';
        }

        // Get path
        $path = $this->getPath($name);

        // Check if file exists
        if ($this->filesystem->exists($path) && !$force) {
            $io->error(sprintf('Dto [%s] already exists!', $name));
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

        $io->success(sprintf('Dto [%s] created successfully.', $name));
        $io->info('Location: ' . $path);

        return Command::SUCCESS;
    }

    /** Get the destination path for the Dto. */
    protected function getPath(string $name): string
    {
        return $this->projectDir . '/src/Dto/' . $name . '.php';
    }

    /** Generate the Dto content. */
    protected function generateContent(string $name, bool $validation, bool $collection, bool $resource): string
    {
        $namespace = 'App\\Dto';
        $className = $name;

        $uses = [
            'use event4u\DataHelpers\SimpleDto;',
        ];

        if ($validation) {
            $uses[] = 'use event4u\DataHelpers\SimpleDto\Attributes\Email;';
            $uses[] = 'use event4u\DataHelpers\SimpleDto\Attributes\Required;';
            $uses[] = 'use event4u\DataHelpers\SimpleDto\Attributes\Min;';
            $uses[] = 'use event4u\DataHelpers\SimpleDto\Attributes\Max;';
        }

        if ($collection) {
            $uses[] = 'use event4u\DataHelpers\SimpleDto\Attributes\DataCollectionOf;';
            $uses[] = 'use event4u\DataHelpers\SimpleDto\DataCollection;';
        }

        $usesStr = implode(PHP_EOL, $uses);

        if ($resource) {
            return $this->generateResourceDto($namespace, $className, $usesStr, $validation, $collection);
        }

        return $this->generateBasicDto($namespace, $className, $usesStr, $validation, $collection);
    }

    /** Generate a basic Dto. */
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

        #[DataCollectionOf(ItemDto::class)]
        public readonly DataCollection $items,
PHP;
        }

        $propertiesStr = implode('', $properties);

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

{$uses}

class {$className} extends SimpleDto
{
    public function __construct(
{$propertiesStr}
    ) {}
}

PHP;
    }

    /** Generate a resource Dto with common fields. */
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

        #[DataCollectionOf(ItemDto::class)]
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

class {$className} extends SimpleDto
{
    public function __construct(
{$propertiesStr}
    ) {}
{$casts}
}

PHP;
    }
}
