<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use Tests\Utils\DTOs\CompanyDto;
use Tests\Utils\DTOs\DepartmentDto;
use Tests\Utils\DTOs\ProjectDto;
use Tests\Utils\SimpleDTOs\CompanySimpleDto;
use Tests\Utils\SimpleDTOs\DepartmentSimpleDto;
use Tests\Utils\SimpleDTOs\ProjectSimpleDto;

describe('DataMapper SimpleDTO Comparison', function(): void {
    describe('Traditional mutable DTOs vs SimpleDTO', function(): void {
        it('compares traditional mutable DTO approach', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            // Traditional approach: Mutable DTO with public properties
            $company = new CompanyDto();
            $mapping = [
                'name' => '{{ company.name }}',
                'registration_number' => '{{ company.registration_number }}',
                'email' => '{{ company.email }}',
                'phone' => '{{ company.phone }}',
                'address' => '{{ company.address }}',
                'city' => '{{ company.city }}',
                'country' => '{{ company.country }}',
                'founded_year' => '{{ company.founded_year }}',
                'employee_count' => '{{ company.employee_count }}',
                'annual_revenue' => '{{ company.annual_revenue }}',
                'is_active' => '{{ company.is_active }}',
                'departments' => [
                    '*' => [
                        'name' => '{{ company.departments.*.name }}',
                        'code' => '{{ company.departments.*.code }}',
                        'budget' => '{{ company.departments.*.budget }}',
                        'employee_count' => '{{ company.departments.*.employee_count }}',
                        'manager_name' => '{{ company.departments.*.manager_name }}',
                    ],
                ],
                'projects' => [
                    '*' => [
                        'name' => '{{ company.projects.*.name }}',
                        'code' => '{{ company.projects.*.code }}',
                        'budget' => '{{ company.projects.*.budget }}',
                        'start_date' => '{{ company.projects.*.start_date }}',
                        'end_date' => '{{ company.projects.*.end_date }}',
                        'status' => '{{ company.projects.*.status }}',
                    ],
                ],
            ];

            $result = DataMapper::sourceFile($jsonFile)->target($company)->template($mapping)->map()->getTarget();

            // Verify traditional DTO
            expect($result)->toBeInstanceOf(CompanyDto::class);
            /** @var CompanyDto $companyDto */
            $companyDto = $result;
            expect($companyDto->name)->toBe('TechCorp Solutions');
            expect($companyDto->registration_number)->toBe('REG-2024-001');
            expect($companyDto->email)->toBe('info@techcorp.example');
            expect($companyDto->founded_year)->toBe(2015);
            expect($companyDto->employee_count)->toBe(250);
            expect($companyDto->annual_revenue)->toBe(15750000.50);
            expect($companyDto->is_active)->toBe(true);

            expect($companyDto->departments)->toBeArray();
            expect($companyDto->departments)->toHaveCount(3);

            $dept0 = $companyDto->departments[0] ?? null;
            expect($dept0)->toBeInstanceOf(DepartmentDto::class);
            /** @var DepartmentDto $dept0Dto */
            $dept0Dto = $dept0;
            expect($dept0Dto->name)->toBe('Engineering');
            expect($dept0Dto->code)->toBe('ENG');
            expect($dept0Dto->budget)->toBe(5000000.00);

            expect($companyDto->projects)->toBeArray();
            expect($companyDto->projects)->toHaveCount(2);

            $proj0 = $companyDto->projects[0] ?? null;
            expect($proj0)->toBeInstanceOf(ProjectDto::class);
            /** @var ProjectDto $proj0Dto */
            $proj0Dto = $proj0;
            expect($proj0Dto->name)->toBe('Cloud Migration');
            expect($proj0Dto->code)->toBe('PROJ-001');
            expect($proj0Dto->budget)->toBe(2500000.00);
        });

        it('compares SimpleDTO immutable approach', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            // SimpleDTO approach: Map to array first, then create immutable DTO
            $mapping = [
                'name' => '{{ company.name }}',
                'registration_number' => '{{ company.registration_number }}',
                'email' => '{{ company.email }}',
                'phone' => '{{ company.phone }}',
                'address' => '{{ company.address }}',
                'city' => '{{ company.city }}',
                'country' => '{{ company.country }}',
                'founded_year' => '{{ company.founded_year }}',
                'employee_count' => '{{ company.employee_count }}',
                'annual_revenue' => '{{ company.annual_revenue }}',
                'is_active' => '{{ company.is_active }}',
                'departments' => [
                    '*' => [
                        'name' => '{{ company.departments.*.name }}',
                        'code' => '{{ company.departments.*.code }}',
                        'budget' => '{{ company.departments.*.budget }}',
                        'employee_count' => '{{ company.departments.*.employee_count }}',
                        'manager_name' => '{{ company.departments.*.manager_name }}',
                    ],
                ],
                'projects' => [
                    '*' => [
                        'name' => '{{ company.projects.*.name }}',
                        'code' => '{{ company.projects.*.code }}',
                        'budget' => '{{ company.projects.*.budget }}',
                        'start_date' => '{{ company.projects.*.start_date }}',
                        'end_date' => '{{ company.projects.*.end_date }}',
                        'status' => '{{ company.projects.*.status }}',
                    ],
                ],
            ];

            // Map to array
            $mappedArray = DataMapper::sourceFile($jsonFile)
                ->target([])
                ->template($mapping)
                ->map()
                ->toArray();

            // Convert nested arrays to DTOs
            /** @var array<int, array<string, mixed>> $departmentsData */
            $departmentsData = $mappedArray['departments'];
            $departments = array_map(
                DepartmentSimpleDto::fromArray(...),
                $departmentsData
            );

            /** @var array<int, array<string, mixed>> $projectsData */
            $projectsData = $mappedArray['projects'];
            $projects = array_map(
                ProjectSimpleDto::fromArray(...),
                $projectsData
            );

            // Create immutable Company DTO
            /** @var array<string, mixed> $companyData */
            $companyData = [
                ...$mappedArray,
                'departments' => $departments,
                'projects' => $projects,
            ];
            $companyDto = CompanySimpleDto::fromArray($companyData);

            // Verify SimpleDTO
            expect($companyDto)->toBeInstanceOf(CompanySimpleDto::class);
            expect($companyDto->name)->toBe('TechCorp Solutions');
            expect($companyDto->registration_number)->toBe('REG-2024-001');
            expect($companyDto->email)->toBe('info@techcorp.example');
            expect($companyDto->founded_year)->toBe(2015);
            expect($companyDto->employee_count)->toBe(250);
            expect($companyDto->annual_revenue)->toBe(15750000.50);
            expect($companyDto->is_active)->toBe(true);

            // Verify immutability
            /** @phpstan-ignore-next-line unknown */
            expect(fn(): string => $companyDto->name = 'New Name')->toThrow(Error::class);

            // Verify departments
            expect($companyDto->departments)->toBeArray();
            expect($companyDto->departments)->toHaveCount(3);

            $dept0 = $companyDto->departments[0];
            expect($dept0)->toBeInstanceOf(DepartmentSimpleDto::class);
            expect($dept0->name)->toBe('Engineering');
            expect($dept0->code)->toBe('ENG');
            expect($dept0->budget)->toBe(5000000.00);
            expect($dept0->employee_count)->toBe(120);
            expect($dept0->manager_name)->toBe('Alice Johnson');

            // Verify immutability of nested DTO
            /** @phpstan-ignore-next-line unknown */
            expect(fn(): string => $dept0->name = 'New Name')->toThrow(Error::class);

            // Verify projects
            expect($companyDto->projects)->toBeArray();
            expect($companyDto->projects)->toHaveCount(2);

            $proj0 = $companyDto->projects[0];
            expect($proj0)->toBeInstanceOf(ProjectSimpleDto::class);
            expect($proj0->name)->toBe('Cloud Migration');
            expect($proj0->code)->toBe('PROJ-001');
            expect($proj0->budget)->toBe(2500000.00);
            expect($proj0->start_date)->toBe('2024-01-01');
            expect($proj0->end_date)->toBe('2024-12-31');
            expect($proj0->status)->toBe('active');

            // Verify JSON serialization
            $json = json_encode($companyDto);
            expect($json)->toBeString();
            assert(is_string($json));

            $decoded = json_decode($json, true);
            expect($decoded['name'])->toBe('TechCorp Solutions');
            expect($decoded['departments'])->toHaveCount(3);
            expect($decoded['projects'])->toHaveCount(2);
        });

        it('demonstrates SimpleDTO benefits: immutability and JSON serialization', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            // Map departments only
            $mapping = [
                '*' => [
                    'name' => '{{ company.departments.*.name }}',
                    'code' => '{{ company.departments.*.code }}',
                    'budget' => '{{ company.departments.*.budget }}',
                    'employee_count' => '{{ company.departments.*.employee_count }}',
                    'manager_name' => '{{ company.departments.*.manager_name }}',
                ],
            ];

            $mappedArray = DataMapper::sourceFile($jsonFile)
                ->target([])
                ->template($mapping)
                ->map()
                ->toArray();

            // Create immutable DTOs
            /** @var array<int, array<string, mixed>> $mappedArrayTyped */
            $mappedArrayTyped = $mappedArray;
            $departments = array_map(
                DepartmentSimpleDto::fromArray(...),
                $mappedArrayTyped
            );

            expect($departments)->toBeArray();
            expect($departments)->toHaveCount(3);

            // Test immutability
            $dept = $departments[0];
            expect($dept)->toBeInstanceOf(DepartmentSimpleDto::class);
            /** @phpstan-ignore-next-line unknown */
            expect(fn(): string => $dept->name = 'Changed')->toThrow(Error::class);

            // Test JSON serialization
            $json = json_encode($departments);
            assert(is_string($json));
            $decoded = json_decode($json, true);

            expect($decoded)->toBeArray();
            expect($decoded)->toHaveCount(3);
            expect($decoded[0]['name'])->toBe('Engineering');
            expect($decoded[1]['name'])->toBe('Sales');
            expect($decoded[2]['name'])->toBe('Human Resources');

            // Test toArray()
            $array = $dept->toArray();
            expect($array)->toBeArray();
            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('code');
            expect($array)->toHaveKey('budget');
        });
    });
});
