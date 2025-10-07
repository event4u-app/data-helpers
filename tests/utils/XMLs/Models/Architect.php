<?php

declare(strict_types=1);

namespace Tests\utils\XMLs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Architect Model for XML data
 *
 * Maps to:
 * - version2: architect_* fields
 */
class Architect extends Model
{
    /** The table associated with the model. */
    protected $table = 'architects';

    /** The attributes that are mass assignable. */
    protected $fillable = [
        'project_id',
        'external_id',
        'description',
        'firstname',
        'surname',
        'street',
        'zipcode',
        'city',
    ];

    /** The model's default values for attributes. */
    protected $attributes = [
        'external_id' => null,
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

    public function getExternalId(): ?string
    {
        return $this->external_id;
    }

    public function setExternalId(?string $externalId): self
    {
        $this->external_id = $externalId;
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

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): self
    {
        $this->surname = $surname;
        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $street;
        return $this;
    }

    public function getZipcode(): string
    {
        return $this->zipcode;
    }

    public function setZipcode(string $zipcode): self
    {
        $this->zipcode = $zipcode;
        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;
        return $this;
    }

    // Relations

    /** Get the project that owns the architect. */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}

