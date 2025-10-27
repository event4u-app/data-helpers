<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Frameworks\Symfony\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Symfony console command to generate Dto classes.
 *
 * Usage:
 * ```bash
 * php bin/console make:dto UserDto
 * php bin/console make:dto UserDto --validate
 * ```
 */
#[AsCommand(
    name: 'make:dto',
    description: 'Create a new Dto class',
)]
class MakeDtoCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the Dto')
            ->addOption('validate', null, InputOption::VALUE_NONE, 'Add ValidateRequest attribute')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite existing file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = (string)$input->getArgument('name');
        $validate = (bool)$input->getOption('validate');
        $force = (bool)$input->getOption('force');

        // Ensure name ends with Dto
        if (!str_ends_with($name, 'Dto')) {
            $name .= 'Dto';
        }

        // Generate Dto
        $path = $this->getPath($name);

        // Check if file exists
        if (file_exists($path) && !$force) {
            $io->error(sprintf('Dto %s already exists!', $name));
            return Command::FAILURE;
        }

        // Generate content
        $content = $this->getDtoStub($name, $validate);

        // Create directory if needed
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Write file
        file_put_contents($path, $content);

        $io->success(sprintf('Dto %s created successfully!', $name));
        $io->text('Location: ' . $path);

        return Command::SUCCESS;
    }

    /** Get file path for generated class. */
    private function getPath(string $className): string
    {
        // Assuming standard Symfony structure
        return getcwd() . sprintf('/src/Dto/%s.php', $className);
    }

    /** Get Dto stub content. */
    private function getDtoStub(string $className, bool $validate): string
    {
        $validateAttribute = $validate ? '#[ValidateRequest(throw: true)]' . PHP_EOL : '';
        $validateUse = $validate ? 'use event4u\\DataHelpers\\SimpleDto\\Attributes\\ValidateRequest;' . PHP_EOL : '';

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Dto;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Email;
use event4u\DataHelpers\SimpleDto\Attributes\Min;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
{$validateUse}
{$validateAttribute}class {$className} extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string \$email,

        #[Required]
        #[Min(3)]
        public readonly string \$name,
    ) {}
}

PHP;
    }
}
