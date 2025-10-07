<?php

declare(strict_types=1);

namespace Tests\utils\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     *
     * @return BelongsTo<Company, Department>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employees for the department.
     *
     * @return HasMany<Employee>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
