<?php

declare(strict_types=1);

/**
 * Bootstrap file for polyfills.
 *
 * This file is automatically loaded by Composer and loads polyfills
 * for Laravel and Doctrine classes when they are not available.
 */

// Load Laravel polyfills if Laravel is not installed
if (!interface_exists('\Illuminate\Contracts\Support\Arrayable')) {
    require_once __DIR__ . '/Arrayable.php';
}

if (!class_exists('\Illuminate\Support\Collection')) {
    require_once __DIR__ . '/Collection.php';
}

if (!class_exists('\Illuminate\Database\Eloquent\Model')) {
    require_once __DIR__ . '/Model.php';
}

// Load Doctrine polyfills if Doctrine is not installed
if (!interface_exists('\Doctrine\Common\Collections\Collection')) {
    require_once __DIR__ . '/DoctrineCollection.php';
}
