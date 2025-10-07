<?php

declare(strict_types=1);

namespace Tests\utils\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'companies')]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $registration_number = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $founded_year = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $employee_count = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $annual_revenue = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $is_active = null;

    /** @var Collection<int, Department> */
    #[ORM\OneToMany(targetEntity: Department::class, mappedBy: 'company', cascade: ['persist', 'remove'])]
    private Collection $departments;

    /** @var Collection<int, Project> */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'company', cascade: ['persist', 'remove'])]
    private Collection $projects;

    public function __construct()
    {
        $this->departments = new ArrayCollection();
        $this->projects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registration_number;
    }

    public function setRegistrationNumber(?string $registration_number): self
    {
        $this->registration_number = $registration_number;

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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

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

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getFoundedYear(): ?int
    {
        return $this->founded_year;
    }

    public function setFoundedYear(?int $founded_year): self
    {
        $this->founded_year = $founded_year;

        return $this;
    }

    public function getEmployeeCount(): ?int
    {
        return $this->employee_count;
    }

    public function setEmployeeCount(?int $employee_count): self
    {
        $this->employee_count = $employee_count;

        return $this;
    }

    public function getAnnualRevenue(): ?float
    {
        return $this->annual_revenue;
    }

    public function setAnnualRevenue(?float $annual_revenue): self
    {
        $this->annual_revenue = $annual_revenue;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(?bool $is_active): self
    {
        $this->is_active = $is_active;

        return $this;
    }

    /** @return Collection<int, Department> */
    public function getDepartments(): Collection
    {
        return $this->departments;
    }

    public function addDepartment(Department $department): self
    {
        if (!$this->departments->contains($department)) {
            $this->departments->add($department);
            $department->setCompany($this);
        }

        return $this;
    }

    public function removeDepartment(Department $department): self
    {
        if ($this->departments->removeElement($department) && $department->getCompany() === $this) {
            $department->setCompany(null);
        }

        return $this;
    }

    /** @return Collection<int, Project> */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): self
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setCompany($this);
        }

        return $this;
    }

    public function removeProject(Project $project): self
    {
        if ($this->projects->removeElement($project) && $project->getCompany() === $this) {
            $project->setCompany(null);
        }

        return $this;
    }
}

