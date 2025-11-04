<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Frameworks\Laravel\Commands;

use event4u\DataHelpers\Console\WarmCacheCommand as BaseWarmCacheCommand;
use Illuminate\Console\Command;

final class WarmCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dto:warm-cache
                            {directories?* : Directories to scan for SimpleDtos}
                            {--no-validate : Skip cache validation after warming}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up persistent cache for SimpleDtos';

    /** Execute the console command. */
    public function handle(): int
    {
        $this->info('Data Helpers - Cache Warming');
        $this->newLine();

        /** @var array<string> $directories */
        $directories = $this->argument('directories') ?? [];
        $validate = !$this->option('no-validate');

        // If no directories specified, find DTO directories in project
        if ([] === $directories) {
            $projectRoot = BaseWarmCacheCommand::detectProjectRoot();
            $directories = BaseWarmCacheCommand::findDtoDirectories($projectRoot);

            $this->comment('No directories specified, scanning from project root: ' . $projectRoot);
            $this->comment('Found ' . count($directories) . ' potential DTO directories');
            $this->newLine();
        }

        $command = new BaseWarmCacheCommand();
        $exitCode = $command->execute($directories, verbose: true, validate: $validate);

        if (0 === $exitCode) {
            $this->newLine();
            $this->info('✅  Cache warming completed successfully');
        } else {
            $this->newLine();
            $this->error('❌  Cache warming completed with errors');
        }

        return $exitCode;
    }
}
