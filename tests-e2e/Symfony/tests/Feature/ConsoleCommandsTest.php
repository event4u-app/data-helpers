<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

describe('Console Commands', function(): void {
    beforeEach(function(): void {
        $this->kernel = new Kernel('test', true);
        $this->kernel->boot();
        $this->application = new Application($this->kernel);
        $this->filesystem = new Filesystem();
        $this->projectDir = $this->kernel->getProjectDir();
    });

    afterEach(function(): void {
        // Clean up generated files
        $files = [
            $this->projectDir . '/src/DTO/TestUserDTO.php',
            $this->projectDir . '/src/DTO/TestProductDTO.php',
            $this->projectDir . '/storage/test-types.ts',
        ];

        foreach ($files as $file) {
            if ($this->filesystem->exists($file)) {
                $this->filesystem->remove($file);
            }
        }

        // Clean up DTO directory if empty
        $dtoDir = $this->projectDir . '/src/DTO';
        if ($this->filesystem->exists($dtoDir) && 0 === count(scandir($dtoDir)) - 2) {
            $this->filesystem->remove($dtoDir);
        }

        $this->kernel->shutdown();
    });

    it('can generate basic DTO with make:dto', function(): void {
        $command = $this->application->find('make:dto');
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            'name' => 'TestUser',
        ]);

        expect($exitCode)->toBe(0);
        expect($commandTester->getStatusCode())->toBe(0);

        $path = $this->projectDir . '/src/DTO/TestUserDTO.php';
        expect($this->filesystem->exists($path))->toBeTrue();

        $content = file_get_contents($path);
        expect($content)->toContain('namespace App\DTO;');
        expect($content)->toContain('class TestUserDTO extends SimpleDTO');
        expect($content)->toContain('public readonly string $name');
        expect($content)->toContain('public readonly string $email');
        expect($content)->not->toContain('#[Required]');
        expect($content)->not->toContain('DataCollection');

        // Verify output
        $output = $commandTester->getDisplay();
        expect($output)->toContain('DTO [TestUserDTO] created successfully');
    });

    it('can generate DTO with validation attributes', function(): void {
        $command = $this->application->find('make:dto');
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            'name' => 'TestUser',
            '--validation' => true,
        ]);

        expect($exitCode)->toBe(0);
        expect($commandTester->getStatusCode())->toBe(0);

        $path = $this->projectDir . '/src/DTO/TestUserDTO.php';
        expect($this->filesystem->exists($path))->toBeTrue();

        $content = file_get_contents($path);
        expect($content)->toContain('#[Required]');
        expect($content)->toContain('#[Email]');
        expect($content)->toContain('use event4u\DataHelpers\SimpleDTO\Attributes\Email;');
        expect($content)->toContain('use event4u\DataHelpers\SimpleDTO\Attributes\Required;');

        // Verify output
        $output = $commandTester->getDisplay();
        expect($output)->toContain('DTO [TestUserDTO] created successfully');
    });

    it('can generate DTO with collection support', function(): void {
        $command = $this->application->find('make:dto');
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            'name' => 'TestUser',
            '--collection' => true,
        ]);

        expect($exitCode)->toBe(0);
        expect($commandTester->getStatusCode())->toBe(0);

        $path = $this->projectDir . '/src/DTO/TestUserDTO.php';
        expect($this->filesystem->exists($path))->toBeTrue();

        $content = file_get_contents($path);
        expect($content)->toContain('DataCollection');
        expect($content)->toContain('#[DataCollectionOf(ItemDTO::class)]');
        expect($content)->toContain('use event4u\DataHelpers\SimpleDTO\Attributes\DataCollectionOf;');
        expect($content)->toContain('use event4u\DataHelpers\SimpleDTO\DataCollection;');

        // Verify output
        $output = $commandTester->getDisplay();
        expect($output)->toContain('DTO [TestUserDTO] created successfully');
    });

    it('can generate resource DTO', function(): void {
        $command = $this->application->find('make:dto');
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            'name' => 'TestUser',
            '--resource' => true,
        ]);

        expect($exitCode)->toBe(0);
        expect($commandTester->getStatusCode())->toBe(0);

        $path = $this->projectDir . '/src/DTO/TestUserDTO.php';
        expect($this->filesystem->exists($path))->toBeTrue();

        $content = file_get_contents($path);
        expect($content)->toContain('public readonly int $id');
        expect($content)->toContain('public readonly ?string $description = null');
        expect($content)->toContain('public readonly ?DateTimeImmutable $createdAt = null');
        expect($content)->toContain('public readonly ?DateTimeImmutable $updatedAt = null');
        expect($content)->toContain('protected function casts(): array');
        expect($content)->toContain("'createdAt' => 'datetime'");
        expect($content)->toContain("'updatedAt' => 'datetime'");

        // Verify output
        $output = $commandTester->getDisplay();
        expect($output)->toContain('DTO [TestUserDTO] created successfully');
    });

    it('can generate resource DTO with all options', function(): void {
        $command = $this->application->find('make:dto');
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            'name' => 'TestUser',
            '--resource' => true,
            '--validation' => true,
            '--collection' => true,
        ]);

        expect($exitCode)->toBe(0);
        expect($commandTester->getStatusCode())->toBe(0);

        $path = $this->projectDir . '/src/DTO/TestUserDTO.php';
        expect($this->filesystem->exists($path))->toBeTrue();

        $content = file_get_contents($path);
        expect($content)->toContain('#[Required]');
        expect($content)->toContain('#[Email]');
        expect($content)->toContain('#[Min(3)]');
        expect($content)->toContain('#[Max(255)]');
        expect($content)->toContain('DataCollection');
        expect($content)->toContain('#[DataCollectionOf(ItemDTO::class)]');
        expect($content)->toContain('public readonly int $id');
        expect($content)->toContain('protected function casts(): array');

        // Verify output
        $output = $commandTester->getDisplay();
        expect($output)->toContain('DTO [TestUserDTO] created successfully');
    });

    it('automatically adds DTO suffix', function(): void {
        $command = $this->application->find('make:dto');
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            'name' => 'TestUser',
        ]);

        expect($exitCode)->toBe(0);
        expect($commandTester->getStatusCode())->toBe(0);

        $path = $this->projectDir . '/src/DTO/TestUserDTO.php';
        expect($this->filesystem->exists($path))->toBeTrue();

        $content = file_get_contents($path);
        expect($content)->toContain('class TestUserDTO extends SimpleDTO');

        // Verify output
        $output = $commandTester->getDisplay();
        expect($output)->toContain('DTO [TestUserDTO] created successfully');
    });

    it('fails if file exists without force', function(): void {
        $command = $this->application->find('make:dto');
        $commandTester = new CommandTester($command);

        // Create file first
        $exitCode1 = $commandTester->execute(['name' => 'TestUser']);
        expect($exitCode1)->toBe(0);

        // Try to create again without force
        $exitCode2 = $commandTester->execute(['name' => 'TestUser']);
        expect($exitCode2)->toBe(1);
        expect($commandTester->getStatusCode())->toBe(1);

        // Verify error message
        $output = $commandTester->getDisplay();
        expect($output)->toContain('DTO [TestUserDTO] already exists!');
        expect($output)->toContain('Use --force to overwrite');
    });

    it('overwrites file with force option', function(): void {
        $command = $this->application->find('make:dto');
        $commandTester = new CommandTester($command);

        // Create file first
        $exitCode1 = $commandTester->execute(['name' => 'TestUser']);
        expect($exitCode1)->toBe(0);

        // Overwrite with force
        $exitCode2 = $commandTester->execute([
            'name' => 'TestUser',
            '--force' => true,
        ]);

        expect($exitCode2)->toBe(0);
        expect($commandTester->getStatusCode())->toBe(0);

        // Verify file still exists
        $path = $this->projectDir . '/src/DTO/TestUserDTO.php';
        expect($this->filesystem->exists($path))->toBeTrue();

        // Verify output
        $output = $commandTester->getDisplay();
        expect($output)->toContain('DTO [TestUserDTO] created successfully');
    });

    it('can generate TypeScript interfaces with dto:typescript', function(): void {
        // Create a DTO first
        $makeCommand = $this->application->find('make:dto');
        $makeTester = new CommandTester($makeCommand);
        $makeExitCode = $makeTester->execute([
            'name' => 'TestProduct',
            '--resource' => true,
        ]);

        expect($makeExitCode)->toBe(0);

        // Dump autoload to make class available
        exec('cd ' . $this->projectDir . ' && composer dump-autoload 2>&1');

        // Generate TypeScript
        $tsCommand = $this->application->find('dto:typescript');
        $tsTester = new CommandTester($tsCommand);
        $tsExitCode = $tsTester->execute([
            '--path' => 'src/DTO',
            '--output' => 'storage/test-types.ts',
        ]);

        expect($tsExitCode)->toBe(0);
        expect($tsTester->getStatusCode())->toBe(0);

        $path = $this->projectDir . '/storage/test-types.ts';
        expect($this->filesystem->exists($path))->toBeTrue();

        $content = file_get_contents($path);
        expect($content)->toContain('export interface TestProductDTO');
        expect($content)->toContain('id: number');
        expect($content)->toContain('name: string');
        expect($content)->toContain('email: string');
        expect($content)->toContain('description: string | null');
        expect($content)->toContain('createdAt: string');
        expect($content)->toContain('updatedAt: string');

        // Verify output
        $output = $tsTester->getDisplay();
        expect($output)->toContain('TypeScript interfaces generated successfully');
        expect($output)->toContain('Found 1 DTO classes');
    });
})->group('symfony');

