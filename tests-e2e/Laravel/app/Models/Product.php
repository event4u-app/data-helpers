<?php

declare(strict_types=1);

namespace E2E\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['sku', 'name', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];
}

