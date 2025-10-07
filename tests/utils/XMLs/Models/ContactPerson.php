<?php

declare(strict_types=1);

namespace Tests\utils\XMLs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contact Person Model for XML data
 *
 * Maps to:
 * - version1: <contact_person>
 */
class ContactPerson extends Model
{
    /** The table associated with the model. */
    protected $table = 'contact_persons';

    /** The attributes that are mass assignable. */
    protected $fillable = [
        'project_id',
        'salutation',
        'surname',
        'email',
        'phone',
    ];

    /** The model's default values for attributes. */
    protected $attributes = [
        'salutation' => null,
        'email' => null,
        'phone' => null,
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

    public function getSalutation(): ?string
    {
        return $this->salutation;
    }

    public function setSalutation(?string $salutation): self
    {
        $this->salutation = $salutation;
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    // Relations

    /** Get the project that owns the contact person. */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}

