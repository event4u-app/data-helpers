<?php

declare(strict_types=1);

namespace Tests\Utils\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
     *
     * @return BelongsTo<Department, Employee>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the projects that the employee works on.
     *
     * @return BelongsToMany<Project>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }
}

