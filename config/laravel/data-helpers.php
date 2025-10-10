<?php

declare(strict_types=1);

use event4u\DataHelpers\Helpers\EnvHelper;

/*
|--------------------------------------------------------------------------
| Laravel Configuration
|--------------------------------------------------------------------------
|
| This file loads the shared configuration and sets Laravel-specific defaults.
|
*/

$config = require __DIR__ . '/../data-helpers.php';

// Override default cache driver for Laravel
$config['cache']['driver'] = EnvHelper::string('DATA_HELPERS_CACHE_DRIVER', 'laravel');

return $config;
