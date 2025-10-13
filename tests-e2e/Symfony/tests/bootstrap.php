<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$symfonyRoot = dirname(__DIR__);

// Load .env file
if (file_exists($symfonyRoot . '/.env')) {
    (new Dotenv())->bootEnv($symfonyRoot . '/.env');
}

// Load Pest configuration
require_once $symfonyRoot . '/Pest.php';
