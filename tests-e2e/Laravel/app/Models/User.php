<?php

declare(strict_types=1);

namespace E2E\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = ['email', 'name'];
}

