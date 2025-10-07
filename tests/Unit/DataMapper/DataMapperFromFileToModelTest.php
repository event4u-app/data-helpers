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
            expect($result->name)->toBe('TechCorp Solutions');
            expect($result->registration_number)->toBe('REG-2024-001');
            expect($result->email)->toBe('info@techcorp.example');
            expect($result->phone)->toBe('+1-555-0123');
            expect($result->founded_year)->toBe(2015);
            expect($result->employee_count)->toBe(250);
            expect($result->annual_revenue)->toBe(15750000.50);
            expect($result->is_active)->toBe(true);

            // Verify departments relation (HasMany)
            expect($result->getRelation('departments'))->toBeInstanceOf(Collection::class);
            expect($result->getRelation('departments'))->toHaveCount(3);

            $dept0 = $result->getRelation('departments')[0];
            expect($dept0)->toBeInstanceOf(Department::class);
            expect($dept0->name)->toBe('Engineering');
            expect($dept0->code)->toBe('ENG');
            expect($dept0->budget)->toBe(5000000.00);
            expect($dept0->employee_count)->toBe(120);
            expect($dept0->manager_name)->toBe('Alice Johnson');

            $dept1 = $result->getRelation('departments')[1];
            expect($dept1)->toBeInstanceOf(Department::class);
            expect($dept1->name)->toBe('Sales');
            expect($dept1->code)->toBe('SAL');
            expect($dept1->budget)->toBe(3000000.00);

            $dept2 = $result->getRelation('departments')[2];
            expect($dept2)->toBeInstanceOf(Department::class);
            expect($dept2->name)->toBe('Human Resources');
            expect($dept2->code)->toBe('HR');
            expect($dept2->budget)->toBe(1500000.00);

            // Verify projects relation (HasMany)
            expect($result->getRelation('projects'))->toBeInstanceOf(Collection::class);
            expect($result->getRelation('projects'))->toHaveCount(2);

            $proj0 = $result->getRelation('projects')[0];
            expect($proj0)->toBeInstanceOf(\Tests\Utils\Models\Project::class);
            expect($proj0->name)->toBe('Cloud Migration');
            expect($proj0->code)->toBe('PROJ-001');
            expect($proj0->budget)->toBe(2500000.00);
            expect($proj0->start_date)->toBe('2024-01-01');
            expect($proj0->end_date)->toBe('2024-12-31');
            expect($proj0->status)->toBe('active');

            $proj1 = $result->getRelation('projects')[1];
            expect($proj1)->toBeInstanceOf(\Tests\Utils\Models\Project::class);
            expect($proj1->name)->toBe('Mobile App Development');
            expect($proj1->budget)->toBe(1800000.00);

            // Additional assertions: Verify casts worked correctly
            expect($dept0->budget)->toBeFloat();
            expect($dept0->employee_count)->toBeInt();
            expect($result->founded_year)->toBeInt();
            expect($result->annual_revenue)->toBeFloat();
            expect($result->is_active)->toBeBool();
            expect($proj0->budget)->toBeFloat();
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
            expect($result->name)->toBe('TechCorp Solutions');
            expect($result->registration_number)->toBe('REG-2024-001');
            expect($result->email)->toBe('info@techcorp.example');
            expect($result->phone)->toBe('+1-555-0123');
            expect($result->founded_year)->toBe(2015); // Cast to int
            expect($result->employee_count)->toBe(250); // Cast to int
            expect($result->annual_revenue)->toBe(15750000.50); // Cast to float
            expect($result->is_active)->toBe(true); // Cast to bool

            // Verify departments relation (HasMany)
            expect($result->getRelation('departments'))->toBeInstanceOf(Collection::class);
            expect($result->getRelation('departments'))->toHaveCount(3);

            $dept0 = $result->getRelation('departments')[0];
            expect($dept0)->toBeInstanceOf(Department::class);
            expect($dept0->name)->toBe('Engineering');
            expect($dept0->code)->toBe('ENG');
            expect($dept0->budget)->toBe(5000000.00); // Cast to float
            expect($dept0->employee_count)->toBe(120); // Cast to int
            expect($dept0->manager_name)->toBe('Alice Johnson');

            $dept1 = $result->getRelation('departments')[1];
            expect($dept1)->toBeInstanceOf(Department::class);
            expect($dept1->name)->toBe('Sales');
            expect($dept1->code)->toBe('SAL');
            expect($dept1->budget)->toBe(3000000.00);

            $dept2 = $result->getRelation('departments')[2];
            expect($dept2)->toBeInstanceOf(Department::class);
            expect($dept2->name)->toBe('Human Resources');
            expect($dept2->code)->toBe('HR');
            expect($dept2->budget)->toBe(1500000.00);

            // Verify projects relation (HasMany)
            expect($result->getRelation('projects'))->toBeInstanceOf(Collection::class);
            expect($result->getRelation('projects'))->toHaveCount(2);

            $proj0 = $result->getRelation('projects')[0];
            expect($proj0)->toBeInstanceOf(\Tests\Utils\Models\Project::class);
            expect($proj0->name)->toBe('Cloud Migration');
            expect($proj0->code)->toBe('PROJ-001');
            expect($proj0->budget)->toBe(2500000.00);
            expect($proj0->start_date)->toBe('2024-01-01');
            expect($proj0->end_date)->toBe('2024-12-31');
            expect($proj0->status)->toBe('active');

            $proj1 = $result->getRelation('projects')[1];
            expect($proj1)->toBeInstanceOf(\Tests\Utils\Models\Project::class);
            expect($proj1->name)->toBe('Mobile App Development');
            expect($proj1->budget)->toBe(1800000.00);

            // Additional assertions: Verify casts worked correctly
            expect($dept0->budget)->toBeFloat();
            expect($dept0->employee_count)->toBeInt();
            expect($result->founded_year)->toBeInt();
            expect($result->annual_revenue)->toBeFloat();
            expect($result->is_active)->toBeBool();
            expect($proj0->budget)->toBeFloat();
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

            // Compare all fields - should be identical thanks to casts
            expect($jsonResult->name)->toBe($xmlResult->name);
            expect($jsonResult->registration_number)->toBe($xmlResult->registration_number);
            expect($jsonResult->email)->toBe($xmlResult->email);
            expect($jsonResult->phone)->toBe($xmlResult->phone);

            // These should be identical types and values thanks to casts
            expect($jsonResult->founded_year)->toBe($xmlResult->founded_year);
            expect($jsonResult->founded_year)->toBeInt();
            expect($xmlResult->founded_year)->toBeInt();

            expect($jsonResult->employee_count)->toBe($xmlResult->employee_count);
            expect($jsonResult->employee_count)->toBeInt();
            expect($xmlResult->employee_count)->toBeInt();

            expect($jsonResult->annual_revenue)->toBe($xmlResult->annual_revenue);
            expect($jsonResult->annual_revenue)->toBeFloat();
            expect($xmlResult->annual_revenue)->toBeFloat();

            expect($jsonResult->is_active)->toBe($xmlResult->is_active);
            expect($jsonResult->is_active)->toBeBool();
            expect($xmlResult->is_active)->toBeBool();

            expect($jsonResult->toArray())->toBe($xmlResult->toArray());
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

            // Compare - should be identical thanks to casts
            expect($jsonResult->name)->toBe($xmlResult->name);
            expect($jsonResult->code)->toBe($xmlResult->code);
            expect($jsonResult->manager_name)->toBe($xmlResult->manager_name);

            // These should be identical types thanks to casts
            expect($jsonResult->budget)->toBe($xmlResult->budget);
            expect($jsonResult->budget)->toBeFloat();
            expect($xmlResult->budget)->toBeFloat();
            expect($jsonResult->budget)->toBe(5000000.00);

            expect($jsonResult->employee_count)->toBe($xmlResult->employee_count);
            expect($jsonResult->employee_count)->toBeInt();
            expect($xmlResult->employee_count)->toBeInt();
            expect($jsonResult->employee_count)->toBe(120);

            expect($jsonResult->toArray())->toBe($xmlResult->toArray());
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
            expect($result->number)->toBe('98765432');
            expect($result->title)->toBe('Sample Company Ltd - Paving Works Test Project');
            expect($result->cost_center)->toBe('98765432');
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
            expect($result->number)->toBe('2608');
            expect($result->title)->toBe('Construction of Paving Area South Park');
        });
    });
});

