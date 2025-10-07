<?php

declare(strict_types=1);

namespace Tests\utils\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'departments')]
class Department
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

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $employee_count = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $manager_name = null;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'departments')]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id')]
    private ?Company $company = null;

    /** @var Collection<int, Employee> */
    #[ORM\OneToMany(targetEntity: Employee::class, mappedBy: 'department', cascade: ['persist', 'remove'])]
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

    public function getEmployeeCount(): ?int
    {
        return $this->employee_count;
    }

    public function setEmployeeCount(?int $employee_count): self
    {
        $this->employee_count = $employee_count;

        return $this;
    }

    public function getManagerName(): ?string
    {
        return $this->manager_name;
    }

    public function setManagerName(?string $manager_name): self
    {
        $this->manager_name = $manager_name;

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
            $employee->setDepartment($this);
        }

        return $this;
    }

    public function removeEmployee(Employee $employee): self
    {
        if ($this->employees->removeElement($employee) && $employee->getDepartment() === $this) {
            $employee->setDepartment(null);
        }

        return $this;
    }
}

