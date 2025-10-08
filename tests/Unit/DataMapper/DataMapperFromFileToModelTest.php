<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use Illuminate\Support\Collection;
use Tests\utils\Models\Company;
use Tests\utils\Models\Department;
use Tests\utils\XMLs\Models\Project;

describe('DataMapper::mapFromFile() to Model', function(): void {
    describe('Automatic relation mapping', function(): void {
        it('maps JSON file to Company model with automatic relation mapping', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            // Map everything in one go - DataMapper automatically detects and maps the relation!
            $company = new Company();
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

            $result = DataMapper::mapFromFile($jsonFile, $company, $mapping);

            // Verify Company data
            expect($result)->toBeInstanceOf(Company::class);
            /** @var Company $company */
            $company = $result;
            expect($company->name)->toBe('TechCorp Solutions');
            expect($company->registration_number)->toBe('REG-2024-001');
            expect($company->email)->toBe('info@techcorp.example');
            expect($company->phone)->toBe('+1-555-0123');
            expect($company->founded_year)->toBe(2015);
            expect($company->employee_count)->toBe(250);
            expect($company->annual_revenue)->toBe(15750000.50);
            expect($company->is_active)->toBe(true);

            // Verify departments relation (HasMany)
            expect($company->getRelation('departments'))->toBeInstanceOf(Collection::class);
            expect($company->getRelation('departments'))->toHaveCount(3);

            $dept0 = $company->getRelation('departments')[0] ?? null;
            expect($dept0)->toBeInstanceOf(Department::class);
            /** @var Department $dept0Model */
            $dept0Model = $dept0;
            expect($dept0Model->name)->toBe('Engineering');
            expect($dept0Model->code)->toBe('ENG');
            expect($dept0Model->budget)->toBe(5000000.00);
            expect($dept0Model->employee_count)->toBe(120);
            expect($dept0Model->manager_name)->toBe('Alice Johnson');

            $dept1 = $company->getRelation('departments')[1] ?? null;
            expect($dept1)->toBeInstanceOf(Department::class);
            /** @var Department $dept1Model */
            $dept1Model = $dept1;
            expect($dept1Model->name)->toBe('Sales');
            expect($dept1Model->code)->toBe('SAL');
            expect($dept1Model->budget)->toBe(3000000.00);

            $dept2 = $company->getRelation('departments')[2] ?? null;
            expect($dept2)->toBeInstanceOf(Department::class);
            /** @var Department $dept2Model */
            $dept2Model = $dept2;
            expect($dept2Model->name)->toBe('Human Resources');
            expect($dept2Model->code)->toBe('HR');
            expect($dept2Model->budget)->toBe(1500000.00);

            // Verify projects relation (HasMany)
            expect($company->getRelation('projects'))->toBeInstanceOf(Collection::class);
            expect($company->getRelation('projects'))->toHaveCount(2);

            $proj0 = $company->getRelation('projects')[0] ?? null;
            expect($proj0)->toBeInstanceOf(\Tests\Utils\Models\Project::class);
            /** @var \Tests\Utils\Models\Project $proj0Model */
            $proj0Model = $proj0;
            expect($proj0Model->name)->toBe('Cloud Migration');
            expect($proj0Model->code)->toBe('PROJ-001');
            expect($proj0Model->budget)->toBe(2500000.00);
            expect($proj0Model->start_date)->toBe('2024-01-01');
            expect($proj0Model->end_date)->toBe('2024-12-31');
            expect($proj0Model->status)->toBe('active');

            $proj1 = $company->getRelation('projects')[1] ?? null;
            expect($proj1)->toBeInstanceOf(\Tests\Utils\Models\Project::class);
            /** @var \Tests\Utils\Models\Project $proj1Model */
            $proj1Model = $proj1;
            expect($proj1Model->name)->toBe('Mobile App Development');
            expect($proj1Model->budget)->toBe(1800000.00);

            // Additional assertions: Verify casts worked correctly
            expect($dept0Model->budget)->toBeFloat();
            expect($dept0Model->employee_count)->toBeInt();
            expect($company->founded_year)->toBeInt();
            expect($company->annual_revenue)->toBeFloat();
            expect($company->is_active)->toBeBool();
            expect($proj0Model->budget)->toBeFloat();
        });

        it('maps XML file to Company model with automatic relation mapping', function(): void {
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            // Map everything in one go - DataMapper automatically detects and maps the relation!
            $company = new Company();
            $mapping = [
                'name' => '{{ name }}',
                'registration_number' => '{{ registration_number }}',
                'email' => '{{ email }}',
                'phone' => '{{ phone }}',
                'address' => '{{ address }}',
                'city' => '{{ city }}',
                'country' => '{{ country }}',
                'founded_year' => '{{ founded_year }}',
                'employee_count' => '{{ employee_count }}',
                'annual_revenue' => '{{ annual_revenue }}',
                'is_active' => '{{ is_active }}',
                'departments' => [
                    '*' => [
                        'name' => '{{ departments.department.*.name }}',
                        'code' => '{{ departments.department.*.code }}',
                        'budget' => '{{ departments.department.*.budget }}',
                        'employee_count' => '{{ departments.department.*.employee_count }}',
                        'manager_name' => '{{ departments.department.*.manager_name }}',
                    ],
                ],
                'projects' => [
                    '*' => [
                        'name' => '{{ projects.project.*.name }}',
                        'code' => '{{ projects.project.*.code }}',
                        'budget' => '{{ projects.project.*.budget }}',
                        'start_date' => '{{ projects.project.*.start_date }}',
                        'end_date' => '{{ projects.project.*.end_date }}',
                        'status' => '{{ projects.project.*.status }}',
                    ],
                ],
            ];

            $result = DataMapper::mapFromFile($xmlFile, $company, $mapping);

            // Verify Company data (XML values are strings, but casts should convert them)
            expect($result)->toBeInstanceOf(Company::class);
            /** @var Company $company */
            $company = $result;
            expect($company->name)->toBe('TechCorp Solutions');
            expect($company->registration_number)->toBe('REG-2024-001');
            expect($company->email)->toBe('info@techcorp.example');
            expect($company->phone)->toBe('+1-555-0123');
            expect($company->founded_year)->toBe(2015); // Cast to int
            expect($company->employee_count)->toBe(250); // Cast to int
            expect($company->annual_revenue)->toBe(15750000.50); // Cast to float
            expect($company->is_active)->toBe(true); // Cast to bool

            // Verify departments relation (HasMany)
            expect($company->getRelation('departments'))->toBeInstanceOf(Collection::class);
            expect($company->getRelation('departments'))->toHaveCount(3);

            $dept0 = $company->getRelation('departments')[0] ?? null;
            expect($dept0)->toBeInstanceOf(Department::class);
            /** @var Department $dept0Model */
            $dept0Model = $dept0;
            expect($dept0Model->name)->toBe('Engineering');
            expect($dept0Model->code)->toBe('ENG');
            expect($dept0Model->budget)->toBe(5000000.00); // Cast to float
            expect($dept0Model->employee_count)->toBe(120); // Cast to int
            expect($dept0Model->manager_name)->toBe('Alice Johnson');

            $dept1 = $company->getRelation('departments')[1] ?? null;
            expect($dept1)->toBeInstanceOf(Department::class);
            /** @var Department $dept1Model */
            $dept1Model = $dept1;
            expect($dept1Model->name)->toBe('Sales');
            expect($dept1Model->code)->toBe('SAL');
            expect($dept1Model->budget)->toBe(3000000.00);

            $dept2 = $company->getRelation('departments')[2] ?? null;
            expect($dept2)->toBeInstanceOf(Department::class);
            /** @var Department $dept2Model */
            $dept2Model = $dept2;
            expect($dept2Model->name)->toBe('Human Resources');
            expect($dept2Model->code)->toBe('HR');
            expect($dept2Model->budget)->toBe(1500000.00);

            // Verify projects relation (HasMany)
            expect($company->getRelation('projects'))->toBeInstanceOf(Collection::class);
            expect($company->getRelation('projects'))->toHaveCount(2);

            $proj0 = $company->getRelation('projects')[0] ?? null;
            expect($proj0)->toBeInstanceOf(\Tests\Utils\Models\Project::class);
            /** @var \Tests\Utils\Models\Project $proj0Model */
            $proj0Model = $proj0;
            expect($proj0Model->name)->toBe('Cloud Migration');
            expect($proj0Model->code)->toBe('PROJ-001');
            expect($proj0Model->budget)->toBe(2500000.00);
            expect($proj0Model->start_date)->toBe('2024-01-01');
            expect($proj0Model->end_date)->toBe('2024-12-31');
            expect($proj0Model->status)->toBe('active');

            $proj1 = $company->getRelation('projects')[1] ?? null;
            expect($proj1)->toBeInstanceOf(\Tests\Utils\Models\Project::class);
            /** @var \Tests\Utils\Models\Project $proj1Model */
            $proj1Model = $proj1;
            expect($proj1Model->name)->toBe('Mobile App Development');
            expect($proj1Model->budget)->toBe(1800000.00);

            // Additional assertions: Verify casts worked correctly
            expect($dept0Model->budget)->toBeFloat();
            expect($dept0Model->employee_count)->toBeInt();
            expect($company->founded_year)->toBeInt();
            expect($company->annual_revenue)->toBeFloat();
            expect($company->is_active)->toBeBool();
            expect($proj0Model->budget)->toBeFloat();
        });
    });

    describe('Model casting and comparison', function(): void {
        it('compares JSON and XML models with identical casted values', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            // Map JSON to Company model
            $jsonCompany = new Company();
            $jsonMapping = [
                'name' => '{{ company.name }}',
                'registration_number' => '{{ company.registration_number }}',
                'email' => '{{ company.email }}',
                'phone' => '{{ company.phone }}',
                'founded_year' => '{{ company.founded_year }}',
                'employee_count' => '{{ company.employee_count }}',
                'annual_revenue' => '{{ company.annual_revenue }}',
                'is_active' => '{{ company.is_active }}',
            ];
            $jsonResult = DataMapper::mapFromFile($jsonFile, $jsonCompany, $jsonMapping);

            // Map XML to Company model
            $xmlCompany = new Company();
            $xmlMapping = [
                'name' => '{{ name }}',
                'registration_number' => '{{ registration_number }}',
                'email' => '{{ email }}',
                'phone' => '{{ phone }}',
                'founded_year' => '{{ founded_year }}',
                'employee_count' => '{{ employee_count }}',
                'annual_revenue' => '{{ annual_revenue }}',
                'is_active' => '{{ is_active }}',
            ];
            $xmlResult = DataMapper::mapFromFile($xmlFile, $xmlCompany, $xmlMapping);

            /** @var Company $jsonCompany */
            $jsonCompany = $jsonResult;
            /** @var Company $xmlCompany */
            $xmlCompany = $xmlResult;

            // Compare all fields - should be identical thanks to casts
            expect($jsonCompany->name)->toBe($xmlCompany->name);
            expect($jsonCompany->registration_number)->toBe($xmlCompany->registration_number);
            expect($jsonCompany->email)->toBe($xmlCompany->email);
            expect($jsonCompany->phone)->toBe($xmlCompany->phone);

            // These should be identical types and values thanks to casts
            expect($jsonCompany->founded_year)->toBe($xmlCompany->founded_year);
            expect($jsonCompany->founded_year)->toBeInt();
            expect($xmlCompany->founded_year)->toBeInt();

            expect($jsonCompany->employee_count)->toBe($xmlCompany->employee_count);
            expect($jsonCompany->employee_count)->toBeInt();
            expect($xmlCompany->employee_count)->toBeInt();

            expect($jsonCompany->annual_revenue)->toBe($xmlCompany->annual_revenue);
            expect($jsonCompany->annual_revenue)->toBeFloat();
            expect($xmlCompany->annual_revenue)->toBeFloat();

            expect($jsonCompany->is_active)->toBe($xmlCompany->is_active);
            expect($jsonCompany->is_active)->toBeBool();
            expect($xmlCompany->is_active)->toBeBool();

            expect($jsonCompany->toArray())->toBe($xmlCompany->toArray());
        });

        it('maps departments to Department models with casts', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            // Map JSON departments to Department models
            $jsonDept = new Department();
            $jsonMapping = [
                'name' => '{{ company.departments.0.name }}',
                'code' => '{{ company.departments.0.code }}',
                'budget' => '{{ company.departments.0.budget }}',
                'employee_count' => '{{ company.departments.0.employee_count }}',
                'manager_name' => '{{ company.departments.0.manager_name }}',
            ];
            $jsonResult = DataMapper::mapFromFile($jsonFile, $jsonDept, $jsonMapping);

            // Map XML departments to Department models
            $xmlDept = new Department();
            $xmlMapping = [
                'name' => '{{ departments.department.0.name }}',
                'code' => '{{ departments.department.0.code }}',
                'budget' => '{{ departments.department.0.budget }}',
                'employee_count' => '{{ departments.department.0.employee_count }}',
                'manager_name' => '{{ departments.department.0.manager_name }}',
            ];
            $xmlResult = DataMapper::mapFromFile($xmlFile, $xmlDept, $xmlMapping);

            /** @var Department $jsonDept */
            $jsonDept = $jsonResult;
            /** @var Department $xmlDept */
            $xmlDept = $xmlResult;

            // Compare - should be identical thanks to casts
            expect($jsonDept->name)->toBe($xmlDept->name);
            expect($jsonDept->code)->toBe($xmlDept->code);
            expect($jsonDept->manager_name)->toBe($xmlDept->manager_name);

            // These should be identical types thanks to casts
            expect($jsonDept->budget)->toBe($xmlDept->budget);
            expect($jsonDept->budget)->toBeFloat();
            expect($xmlDept->budget)->toBeFloat();
            expect($jsonDept->budget)->toBe(5000000.00);

            expect($jsonDept->employee_count)->toBe($xmlDept->employee_count);
            expect($jsonDept->employee_count)->toBeInt();
            expect($xmlDept->employee_count)->toBeInt();
            expect($jsonDept->employee_count)->toBe(120);

            expect($jsonDept->toArray())->toBe($xmlDept->toArray());
        });
    });

    describe('Legacy XML tests', function(): void {
        it('loads and maps from XML file', function(): void {
            $xmlFile = __DIR__ . '/../../utils/XMLs/version1.xml';

            $project = new Project();

            // Format: 'target_path' => 'source_path' (or 'target_path' => '{{ source_path }}')
            $mapping = [
                'number' => '{{ number }}',
                'title' => '{{ title }}',
                'cost_center' => '{{ cost_center }}',
                'total_value' => '{{ order_value }}',
                'calculated_hours' => '{{ calculated_time }}',
            ];

            $result = DataMapper::mapFromFile($xmlFile, $project, $mapping);

            expect($result)->toBeInstanceOf(Project::class);
            /** @var Project $project */
            $project = $result;
            expect($project->number)->toBe('98765432');
            expect($project->title)->toBe('Sample Company Ltd - Paving Works Test Project');
            expect($project->cost_center)->toBe('98765432');
        });

        it('maps nested XML structure to model', function(): void {
            $xmlFile = __DIR__ . '/../../utils/XMLs/version2.xml';

            $project = new Project();

            $mapping = [
                'number' => '{{ ConstructionSite.nr_lv }}',
                'title' => '{{ ConstructionSite.description }}',
                'total_value' => '{{ ConstructionSite.lv_sum }}',
                'calculated_hours' => '{{ ConstructionSite.construction_hours }}',
            ];

            $result = DataMapper::mapFromFile($xmlFile, $project, $mapping);

            expect($result)->toBeInstanceOf(Project::class);
            /** @var Project $project */
            $project = $result;
            expect($project->number)->toBe('2608');
            expect($project->title)->toBe('Construction of Paving Area South Park');
        });
    });
});

