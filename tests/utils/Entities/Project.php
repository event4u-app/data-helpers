<?php

declare(strict_types=1);

namespace Tests\utils\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'projects')]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $budget = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $start_date = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $end_date = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $status = null;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'projects')]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id')]
    private ?Company $company = null;

    /** @var Collection<int, Employee> */
    #[ORM\ManyToMany(targetEntity: Employee::class, mappedBy: 'projects')]
    private Collection $employees;

    public function __construct()
    {
        $this->employees = new ArrayCollection();
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getBudget(): ?float
    {
        return $this->budget;
    }

    public function setBudget(?float $budget): self
    {
        $this->budget = $budget;

        return $this;
    }

    public function getStartDate(): ?string
    {
        return $this->start_date;
    }

    public function setStartDate(?string $start_date): self
    {
        $this->start_date = $start_date;

        return $this;
    }

    public function getEndDate(): ?string
    {
        return $this->end_date;
    }

    public function setEndDate(?string $end_date): self
    {
        $this->end_date = $end_date;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    /** @return Collection<int, Employee> */
    public function getEmployees(): Collection
    {
        return $this->employees;
    }

    public function addEmployee(Employee $employee): self
    {
        if (!$this->employees->contains($employee)) {
            $this->employees->add($employee);
        }

        return $this;
    }

    public function removeEmployee(Employee $employee): self
    {
        $this->employees->removeElement($employee);

        return $this;
    }
}

