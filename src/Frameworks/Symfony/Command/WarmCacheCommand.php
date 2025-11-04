<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Frameworks\Symfony\Command;

use event4u\DataHelpers\Console\WarmCacheCommand as BaseWarmCacheCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'dto:warm-cache',
    description: 'Warm up persistent cache for SimpleDtos'
)]
final class WarmCacheCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument(
                'directories',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Directories to scan for SimpleDtos'
            )
            ->addOption('no-validate', null, InputOption::VALUE_NONE, 'Skip cache validation after warming');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Data Helpers - Cache Warming');

        /** @var array<string> $directories */
        $directories = $input->getArgument('directories') ?? [];
        $validate = !$input->getOption('no-validate');

        // If no directories specified, find DTO directories in project
        if ([] === $directories) {
            $projectRoot = BaseWarmCacheCommand::detectProjectRoot();
            $directories = BaseWarmCacheCommand::findDtoDirectories($projectRoot);

            $io->comment('No directories specified, scanning from project root: ' . $projectRoot);
            $io->comment('Found ' . count($directories) . ' potential DTO directories');
            $io->newLine();
        }

        $command = new BaseWarmCacheCommand();
        $exitCode = $command->execute($directories, verbose: true, validate: $validate);

        if (0 === $exitCode) {
            $io->newLine();
            $io->success('Cache warming completed successfully');
        } else {
            $io->newLine();
            $io->error('Cache warming completed with errors');
        }

        return $exitCode;
    }
}
