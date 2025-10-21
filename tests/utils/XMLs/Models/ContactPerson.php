<?php

declare(strict_types=1);

namespace Tests\utils\XMLs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\utils\XMLs\Enums\Salutation;

/**
 * Contact Person Model for XML data
 *
 * Maps to:
 * - version1: <contact_person>
 *
 * @property int $id
 * @property int $project_id
 * @property Salutation|null $salutation
 * @property string $surname
 * @property string $email
 * @property string $phone
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

    /**
     * The attributes that should be cast.
     * @var array<string, string>
     */
    protected $casts = [
        'salutation' => Salutation::class,
    ];

    /**
     * The model's default values for attributes.
     */
    /** @phpstan-ignore-next-line unknown */
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

    public function getSalutation(): ?Salutation
    {
        return $this->salutation;
    }

    public function setSalutation(Salutation|string|null $salutation): self
    {
        if (is_string($salutation)) {
            $salutation = Salutation::tryFromAny($salutation);
        }
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
        /** @phpstan-ignore-next-line unknown */
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        /** @phpstan-ignore-next-line unknown */
        $this->phone = $phone;
        return $this;
    }

    // Relations

    /**
     * Get the project that owns the contact person.
     * @return BelongsTo<Project, ContactPerson>
     */
    /** @phpstan-ignore-next-line unknown */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

