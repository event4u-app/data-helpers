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

    /** The model's default values for attributes. */
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
        $this->external_id = $externalId;
        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): self
    {
        $this->number = $number;
        return $this;
    }

    public function getParentId(): ?string
    {
        return $this->parent_id;
    }

    public function setParentId(?string $parentId): self
    {
        $this->parent_id = $parentId;
        return $this;
    }

    public function getType(): PositionType
    {
        return $this->type;
    }

    public function setType(PositionType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getTypeDescription(): ?string
    {
        return $this->type_description;
    }

    public function setTypeDescription(?string $typeDescription): self
    {
        $this->type_description = $typeDescription;
        return $this;
    }

    public function getShortText(): ?string
    {
        return $this->short_text;
    }

    public function setShortText(?string $shortText): self
    {
        $this->short_text = $shortText;
        return $this;
    }

    public function getLongText(): ?string
    {
        return $this->long_text;
    }

    public function setLongText(?string $longText): self
    {
        $this->long_text = $longText;
        return $this;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getEstimatedAmount(): float
    {
        return $this->estimated_amount;
    }

    public function setEstimatedAmount(float $estimatedAmount): self
    {
        $this->estimated_amount = $estimatedAmount;
        return $this;
    }

    public function getMeasuredAmount(): float
    {
        return $this->measured_amount;
    }

    public function setMeasuredAmount(float $measuredAmount): self
    {
        $this->measured_amount = $measuredAmount;
        return $this;
    }

    public function getTotalAmount(): float
    {
        return $this->total_amount;
    }

    public function setTotalAmount(float $totalAmount): self
    {
        $this->total_amount = $totalAmount;
        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;
        return $this;
    }

    public function getUnitPrice(): float
    {
        return $this->unit_price;
    }

    public function setUnitPrice(float $unitPrice): self
    {
        $this->unit_price = $unitPrice;
        return $this;
    }

    public function getMinutes(): float
    {
        return $this->minutes;
    }

    public function setMinutes(float $minutes): self
    {
        $this->minutes = $minutes;
        return $this;
    }

    public function getFactor(): float
    {
        return $this->factor;
    }

    public function setFactor(float $factor): self
    {
        $this->factor = $factor;
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

    public function getZipcode(): ?string
    {
        return $this->zipcode;
    }

    public function setZipcode(?string $zipcode): self
    {
        $this->zipcode = $zipcode;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;
        return $this;
    }

    // Relations

    /** Get the project that owns the position. */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /** Get the parent position (for hierarchical structures). */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'parent_id');
    }

    /** Get the child positions (for hierarchical structures). */
    public function children()
    {
        return $this->hasMany(Position::class, 'parent_id');
    }
}

