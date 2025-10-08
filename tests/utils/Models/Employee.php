<?php

declare(strict_types=1);

namespace Tests\Utils\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $name
 * @property string $email
 * @property string $position
 * @property float $salary
 * @property string $hire_date
 */
class Employee extends Model
{
    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'salary' => 'float',
        'hire_date' => 'string',
    ];

    /**
     * Get the department that owns the employee.
     * @phpstan-ignore-next-line missingType.generics
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class); // @phpstan-ignore-line
    }

    /**
     * Get the projects that the employee works on.
     * @phpstan-ignore-next-line missingType.generics
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class); // @phpstan-ignore-line
    }
}

