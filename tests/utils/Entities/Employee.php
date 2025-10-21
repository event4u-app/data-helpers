<?php

declare(strict_types=1);

namespace Tests\utils\Entities;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'employees')]
class Employee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $position = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $salary = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $hire_date = null;

    #[ORM\ManyToOne(targetEntity: Department::class, inversedBy: 'employees')]
    #[ORM\JoinColumn(name: 'department_id', referencedColumnName: 'id')]
    private ?Department $department = null;

    /** @var Collection<int, Project> */
    #[ORM\ManyToMany(targetEntity: Project::class, inversedBy: 'employees')]
    #[ORM\JoinTable(name: 'employee_project')]
    #[ORM\JoinColumn(name: 'employee_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'project_id', referencedColumnName: 'id')]
    private Collection $projects;

    public function __construct()
    {
        $this->id ??= null; // Initialize to satisfy PHPStan
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getSalary(): ?float
    {
        return $this->salary;
    }

    public function setSalary(?float $salary): self
    {
        $this->salary = $salary;

        return $this;
    }

    public function getHireDate(): ?DateTimeInterface
    {
        return $this->hire_date;
    }

    public function setHireDate(?DateTimeInterface $hire_date): self
    {
        $this->hire_date = $hire_date;

        return $this;
    }

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function setDepartment(?Department $department): self
    {
        $this->department = $department;

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
            $project->addEmployee($this);
        }

        return $this;
    }

    public function removeProject(Project $project): self
    {
        if ($this->projects->removeElement($project)) {
            $project->removeEmployee($this);
        }

        return $this;
    }
}

