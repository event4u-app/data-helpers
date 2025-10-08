<?php

declare(strict_types=1);

namespace Tests\utils\XMLs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\utils\XMLs\Enums\PositionType;

/**
 * Unified Position Model for XML data
 *
 * Maps to:
 * - version1: <position>
 * - version2: <Position>
 * - version3: <posdata>
 *
 * @property int $project_id
 * @property string $external_id
 * @property string $number
 * @property int|null $parent_id
 * @property string $type
 * @property string $type_description
 * @property string $short_text
 * @property string $long_text
 * @property float $quantity
 * @property float $estimated_amount
 * @property float $measured_amount
 * @property float $total_amount
 * @property string $unit
 * @property float $unit_price
 * @property int $minutes
 * @property float $factor
 * @property string $address
 * @property string $zipcode
 * @property string $city
 */
class Position extends Model
{
    /** The table associated with the model. */
    protected $table = 'positions';

    /** The attributes that are mass assignable. */
    protected $fillable = [
        'project_id',
        'external_id',
        'number',
        'parent_id',
        'type',
        'type_description',
        'short_text',
        'long_text',
        'quantity',
        'estimated_amount',
        'measured_amount',
        'total_amount',
        'unit',
        'unit_price',
        'minutes',
        'factor',
        'address',
        'zipcode',
        'city',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'external_id' => 'string',
        'number' => 'string',
        'type' => PositionType::class,
        'order_number' => 'integer',
        'quantity' => 'float',
        'estimated_amount' => 'float',
        'measured_amount' => 'float',
        'unit_price' => 'float',
        'total_amount' => 'float',
        'minutes' => 'float',
        'labor_hours' => 'float',
        'factor' => 'float',
    ];

    /**
     * The model's default values for attributes.
     * @phpstan-ignore-next-line assign.propertyType
     */
    protected $attributes = [
        'external_id' => null,
        'number' => null,
        'parent_id' => null,
        'short_text' => null,
        'long_text' => null,
        'quantity' => 0.0,
        'estimated_amount' => 0.0,
        'measured_amount' => 0.0,
        'total_amount' => 0.0,
        'unit_price' => 0.0,
        'minutes' => 0.0,
        'factor' => 1.0,
    ];

    // Getters & Setters

    public function getId(): int
    {
        return $this->id;
    }

    public function getProjectId(): int
    {
        return $this->project_id;
    }

    public function setProjectId(int $projectId): self
    {
        $this->project_id = $projectId;
        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->external_id;
    }

    public function setExternalId(?string $externalId): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->external_id = $externalId;
        return $this;
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

    public function getParentId(): ?string
    {
        return $this->parent_id; // @phpstan-ignore-line return.type
    }

    public function setParentId(?string $parentId): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->parent_id = $parentId;
        return $this;
    }

    public function getType(): PositionType
    {
        return $this->type; // @phpstan-ignore-line return.type
    }

    public function setType(PositionType $type): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->type = $type;
        return $this;
    }

    public function getTypeDescription(): ?string
    {
        return $this->type_description;
    }

    public function setTypeDescription(?string $typeDescription): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->type_description = $typeDescription;
        return $this;
    }

    public function getShortText(): ?string
    {
        return $this->short_text;
    }

    public function setShortText(?string $shortText): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->short_text = $shortText;
        return $this;
    }

    public function getLongText(): ?string
    {
        return $this->long_text;
    }

    public function setLongText(?string $longText): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->long_text = $longText;
        return $this;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->quantity = $quantity;
        return $this;
    }

    public function getEstimatedAmount(): float
    {
        return $this->estimated_amount;
    }

    public function setEstimatedAmount(float $estimatedAmount): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->estimated_amount = $estimatedAmount;
        return $this;
    }

    public function getMeasuredAmount(): float
    {
        return $this->measured_amount;
    }

    public function setMeasuredAmount(float $measuredAmount): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->measured_amount = $measuredAmount;
        return $this;
    }

    public function getTotalAmount(): float
    {
        return $this->total_amount;
    }

    public function setTotalAmount(float $totalAmount): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->total_amount = $totalAmount;
        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->unit = $unit;
        return $this;
    }

    public function getUnitPrice(): float
    {
        return $this->unit_price;
    }

    public function setUnitPrice(float $unitPrice): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->unit_price = $unitPrice;
        return $this;
    }

    public function getMinutes(): float
    {
        return $this->minutes;
    }

    public function setMinutes(float $minutes): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->minutes = $minutes;
        return $this;
    }

    public function getFactor(): float
    {
        return $this->factor;
    }

    public function setFactor(float $factor): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->factor = $factor;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->address = $address;
        return $this;
    }

    public function getZipcode(): ?string
    {
        return $this->zipcode;
    }

    public function setZipcode(?string $zipcode): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->zipcode = $zipcode;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->city = $city;
        return $this;
    }

    // Relations

    /**
     * Get the project that owns the position.
     * @return BelongsTo<Project, Position>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id'); // @phpstan-ignore-line return.type
    }

    /**
     * Get the parent position (for hierarchical structures).
     * @return BelongsTo<Position, Position>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'parent_id'); // @phpstan-ignore-line return.type
    }

    /**
     * Get the child positions (for hierarchical structures).
     * @phpstan-ignore-next-line missingType.generics, missingType.return
     */
    public function children()
    {
        return $this->hasMany(Position::class, 'parent_id'); // @phpstan-ignore-line
    }
}

