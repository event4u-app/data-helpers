<?php

declare(strict_types=1);

namespace Tests\utils\XMLs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tests\utils\XMLs\Enums\ProjectStatus;

/**
 * Unified Project Model for XML data
 *
 * Maps to:
 * - version1: <DataFields>
 * - version2: <VitaCost><ConstructionSite>
 * - version3: <lv_nesting><lvdata>
 *
 * @property int $id
 * @property string|null $number
 * @property ProjectStatus|null $status
 * @property string|null $title
 * @property string|null $description
 * @property string|null $cost_center
 * @property string|null $address
 * @property float $total_value
 * @property float $calculated_hours
 * @property float $actual_hours
 * @property string|null $site_manager
 * @property string|null $foreman
 * @property float $travel_time
 * @property string|null $construction_start
 * @property string|null $construction_end
 * @property float $revenue
 * @property float $costs
 * @property float $contribution_margin
 * @property float $target_margin_sum
 * @property float $result
 * @property int|null $client_id
 * @property float|null $latitude
 * @property float|null $longitude
 */
class Project extends Model
{
    /** The table associated with the model. */
    protected $table = 'projects';

    /** The attributes that are mass assignable. */
    protected $fillable = [
        'number',
        'status',
        'title',
        'description',
        'cost_center',
        'address',
        'total_value',
        'calculated_hours',
        'actual_hours',
        'site_manager',
        'foreman',
        'travel_time',
        'construction_start',
        'construction_end',
        'revenue',
        'costs',
        'contribution_margin',
        'target_margin_sum',
        'result',
        'client_id',
        'latitude',
        'longitude',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'number' => 'string',
        'status' => ProjectStatus::class,
        'total_value' => 'float',
        'calculated_hours' => 'float',
        'actual_hours' => 'float',
        'travel_time' => 'float',
        'revenue' => 'float',
        'costs' => 'float',
        'contribution_margin' => 'float',
        'target_margin_sum' => 'float',
        'result' => 'float',
        'invoices_total' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     * @phpstan-ignore-next-line assign.propertyType
     */
    protected $attributes = [
        'number' => null,
        'title' => null,
        'description' => null,
        'cost_center' => null,
        'total_value' => 0.0,
        'calculated_hours' => 0.0,
        'actual_hours' => 0.0,
        'site_manager' => null,
        'foreman' => null,
        'travel_time' => 0.0,
        'construction_start',
        'construction_end',
        'revenue' => 0.0,
        'costs' => 0.0,
        'contribution_margin' => 0.0,
        'target_margin_sum' => 0.0,
        'result' => 0.0,
        'latitude' => 0.0,
        'longitude' => 0.0,
    ];

    // Getters & Setters

    public function getId(): int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->number = $number;
        return $this;
    }

    public function getStatus(): ProjectStatus
    {
        return $this->status; // @phpstan-ignore-line return.type
    }

    public function setStatus(ProjectStatus $status): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->status = $status;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCostCenter(): ?string
    {
        return $this->cost_center;
    }

    public function setCostCenter(?string $costCenter): self
    {
        $this->cost_center = $costCenter;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getTotalValue(): float
    {
        return $this->total_value;
    }

    public function setTotalValue(float $totalValue): self
    {
        $this->total_value = $totalValue;
        return $this;
    }

    public function getCalculatedHours(): float
    {
        return $this->calculated_hours;
    }

    public function setCalculatedHours(float $calculatedHours): self
    {
        $this->calculated_hours = $calculatedHours;
        return $this;
    }

    public function getActualHours(): float
    {
        return $this->actual_hours;
    }

    public function setActualHours(float $actualHours): self
    {
        $this->actual_hours = $actualHours;
        return $this;
    }

    public function getSiteManager(): ?string
    {
        return $this->site_manager;
    }

    public function setSiteManager(?string $siteManager): self
    {
        $this->site_manager = $siteManager;
        return $this;
    }

    public function getForeman(): ?string
    {
        return $this->foreman;
    }

    public function setForeman(?string $foreman): self
    {
        $this->foreman = $foreman;
        return $this;
    }

    public function getTravelTime(): float
    {
        return $this->travel_time;
    }

    public function setTravelTime(float $travelTime): self
    {
        $this->travel_time = $travelTime;
        return $this;
    }

    public function getConstructionStart(): ?string
    {
        return $this->construction_start;
    }

    public function setConstructionStart(?string $constructionStart): self
    {
        $this->construction_start = $constructionStart;
        return $this;
    }

    public function getConstructionEnd(): ?string
    {
        return $this->construction_end;
    }

    public function setConstructionEnd(?string $constructionEnd): self
    {
        $this->construction_end = $constructionEnd;
        return $this;
    }

    public function getRevenue(): float
    {
        return $this->revenue;
    }

    public function setRevenue(float $revenue): self
    {
        $this->revenue = $revenue;
        return $this;
    }

    public function getCosts(): float
    {
        return $this->costs;
    }

    public function setCosts(float $costs): self
    {
        $this->costs = $costs;
        return $this;
    }

    public function getContributionMargin(): float
    {
        return $this->contribution_margin;
    }

    public function setContributionMargin(float $contributionMargin): self
    {
        $this->contribution_margin = $contributionMargin;
        return $this;
    }

    public function getTargetMarginSum(): float
    {
        return $this->target_margin_sum;
    }

    public function setTargetMarginSum(float $targetMarginSum): self
    {
        $this->target_margin_sum = $targetMarginSum;
        return $this;
    }

    public function getResult(): float
    {
        return $this->result;
    }

    public function setResult(float $result): self
    {
        $this->result = $result;
        return $this;
    }

    public function getClientId(): ?string
    {
        return $this->client_id; // @phpstan-ignore-line return.type
    }

    public function setClientId(?string $clientId): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->client_id = $clientId;
        return $this;
    }

    public function getLatitude(): float
    {
        return $this->latitude; // @phpstan-ignore-line return.type
    }

    public function setLatitude(float $latitude): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): float
    {
        return $this->longitude; // @phpstan-ignore-line return.type
    }

    public function setLongitude(float $longitude): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->longitude = $longitude;
        return $this;
    }

    // Relations

    /**
     * Get the customer associated with the project.
     * @phpstan-ignore-next-line missingType.generics
     */
    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class, 'project_id'); // @phpstan-ignore-line
    }

    /**
     * Get the construction address associated with the project.
     * @phpstan-ignore-next-line missingType.generics
     */
    public function constructionAddress(): HasOne
    {
        return $this->hasOne(Address::class, 'project_id'); // @phpstan-ignore-line
    }

    /**
     * Get the architect associated with the project.
     * @phpstan-ignore-next-line missingType.generics
     */
    public function architect(): HasOne
    {
        return $this->hasOne(Architect::class, 'project_id'); // @phpstan-ignore-line
    }

    /**
     * Get the contact persons for the project.
     * @phpstan-ignore-next-line missingType.generics
     */
    public function contactPersons(): HasMany
    {
        return $this->hasMany(ContactPerson::class, 'project_id'); // @phpstan-ignore-line
    }

    /**
     * Get the positions for the project.
     * @phpstan-ignore-next-line missingType.generics
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class, 'project_id'); // @phpstan-ignore-line
    }
}

