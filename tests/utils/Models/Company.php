<?php

declare(strict_types=1);

namespace Tests\utils\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 * @property string $registration_number
 * @property string $email
 * @property string $phone
 * @property int $founded_year
 * @property int $employee_count
 * @property float $annual_revenue
 * @property bool $is_active
 */
final class Company extends Model
{
    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'founded_year' => 'integer',
        'employee_count' => 'integer',
        'annual_revenue' => 'float',
        'is_active' => 'boolean',
    ];

    /**
     * Get the departments for the company.
     * @phpstan-ignore-next-line missingType.generics
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class); // @phpstan-ignore-line
    }

    /**
     * Get the projects for the company.
     * @phpstan-ignore-next-line missingType.generics
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class); // @phpstan-ignore-line
    }
}
