<?php

declare(strict_types=1);

namespace Tests\Utils\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $name
 * @property string $code
 * @property float $budget
 * @property string $start_date
 * @property string $end_date
 * @property string $status
 */
class Project extends Model
{
    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'budget' => 'float',
        'start_date' => 'string',
        'end_date' => 'string',
    ];

    /**
     * Get the company that owns the project.
     */
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employees working on the project.
     */
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class);
    }
}

