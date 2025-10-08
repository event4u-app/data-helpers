<?php

declare(strict_types=1);

namespace Tests\utils\XMLs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Unified Customer Model for XML data
 *
 * Maps to:
 * - version1: <client>
 * - version2: customer_* fields
 * - version3: customer_* fields
 *
 * @property int $id
 * @property int $project_id
 * @property string $description
 * @property string $firstname
 * @property string $surname
 * @property string $street
 * @property string $zipcode
 * @property string $city
 */
class Customer extends Model
{
    /** The table associated with the model. */
    protected $table = 'customers';

    /** The attributes that are mass assignable. */
    protected $fillable = [
        'project_id',
        'description',
        'firstname',
        'surname',
        'street',
        'zipcode',
        'city',
    ];

    /**
     * The model's default values for attributes.
     * @phpstan-ignore-next-line assign.propertyType
     */
    protected $attributes = [
        'description' => null,
        'street' => null,
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->description = $description;
        return $this;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->firstname = $firstname;
        return $this;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->surname = $surname;
        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->street = $street;
        return $this;
    }

    public function getZipcode(): string
    {
        return $this->zipcode;
    }

    public function setZipcode(string $zipcode): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->zipcode = $zipcode;
        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        $this->city = $city;
        return $this;
    }

    // Relations

    /**
     * Get the project that owns the customer.
     * @return BelongsTo<Project, Customer>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id'); // @phpstan-ignore-line return.type
    }
}

