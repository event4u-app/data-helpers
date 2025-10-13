<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Path to the Symfony project root
$symfonyRoot = dirname(__DIR__);

// Load .env if present
$dotenvFile = $symfonyRoot . '/.env';
if (file_exists($dotenvFile)) {
    (new Dotenv())->bootEnv($dotenvFile);
} else {
    fwrite(STDERR, "[bootstrap] ⚠️ No .env found at {$dotenvFile}\n");
}

// Optional: Boot the Symfony kernel if you need service container access
$kernelBootstrap = $symfonyRoot . '/config/bootstrap.php';
if (file_exists($kernelBootstrap)) {
    require_once $kernelBootstrap;
}
