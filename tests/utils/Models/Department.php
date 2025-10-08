<?php

declare(strict_types=1);

namespace Tests\utils\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 * @property string $code
 * @property float $budget
 * @property int $employee_count
 * @property string $manager_name
 */
final class Department extends Model
{
    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'budget' => 'float',
        'employee_count' => 'integer',
    ];

    /**
     * Get the company that owns the department.
     * @phpstan-ignore-next-line missingType.generics
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class); // @phpstan-ignore-line
    }

    /**
     * Get the employees for the department.
     * @phpstan-ignore-next-line missingType.generics
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class); // @phpstan-ignore-line
    }
}
