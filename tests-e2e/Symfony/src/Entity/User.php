<?php

declare(strict_types=1);

namespace App\Entity;

use App\Dto\UserDto;
use Doctrine\ORM\Mapping as ORM;
use event4u\DataHelpers\SimpleDto\Attributes\HasDto;
use event4u\DataHelpers\Traits\DtoMappingTrait;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[HasDto(UserDto::class)]
class User
{
    use DtoMappingTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}

