<?php

declare(strict_types=1);

namespace E2E\Laravel\Models;

use E2E\Laravel\Dtos\UserDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasDto;
use event4u\DataHelpers\Traits\DtoMappingTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $email
 * @property string|null $role
 * @property string|null $password
 * @property bool $exists
 */
#[HasDto(UserDto::class)]
class User extends Model
{
    use DtoMappingTrait;

    protected $fillable = ['email', 'name'];
}

