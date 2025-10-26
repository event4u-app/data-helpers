<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseEmails;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;
use Tests\utils\Entities\Company as EntityCompany;
use Tests\utils\Entities\Department as EntityDepartment;
use Tests\utils\Models\Company;
use Tests\utils\Models\Department;

/**
 * Tests for DataMapper::pipeQuery() with file loading.
 *
 * @internal
 */
describe('DataMapper pipeQuery() with file loading', function(): void {
    describe('Pipeline with file loading - Array targets', function(): void {
        it('loads JSON file and applies transformers to array target', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            $target = [];
            $mapping = [
                'company_name' => '{{ company.name }}',
                'company_email' => '{{ company.email }}',
                'dept_names' => '{{ company.departments.*.name }}',
            ];

            $result = DataMapper::pipeline([
                new TrimStrings(),
                new LowercaseEmails(),
            ])->sourceFile($jsonFile)->target($target)->template($mapping)->map()->getTarget();

            expect($result)->toBeArray();
            expect($result['company_name'])->toBe('TechCorp Solutions');
            expect($result['company_email'])->toBe('info@techcorp.example'); // Lowercased by transformer
            expect($result['dept_names'])->toBeArray();
            expect($result['dept_names'])->toHaveCount(3);
        });

        it('loads XML file and applies transformers to array target', function(): void {
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            $target = [];
            $mapping = [
                'company_name' => '{{ name }}',
                'company_email' => '{{ email }}',
                'dept_codes' => '{{ departments.department.*.code }}',
            ];

            $result = DataMapper::pipeline([
                new TrimStrings(),
                new UppercaseStrings(),
            ])->sourceFile($xmlFile)->target($target)->template($mapping)->map()->getTarget();

            expect($result)->toBeArray();
            expect($result['company_name'])->toBe('TECHCORP SOLUTIONS'); // Uppercased by transformer
            expect($result['company_email'])->toBe('INFO@TECHCORP.EXAMPLE'); // Uppercased by transformer
            expect($result['dept_codes'])->toBeArray();
            expect($result['dept_codes'])->toHaveCount(3);
            expect($result['dept_codes'][0])->toBe('ENG');
        });

        it('applies multiple transformers in sequence', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            $target = [];
            $mapping = [
                'email' => '{{ company.email }}',
                'phone' => '{{ company.phone }}',
            ];

            $result = DataMapper::pipeline([
                new TrimStrings(),
                new LowercaseEmails(),
            ])->sourceFile($jsonFile)->target($target)->template($mapping)->map()->getTarget();

            expect($result['email'])->toBe('info@techcorp.example'); // Trimmed and lowercased
            expect($result['phone'])->toBe('+1-555-0123'); // Only trimmed
        });
    });

    describe('Pipeline with file loading - Model targets', function(): void {
        it('loads JSON file and maps to Eloquent Model with transformers', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            $company = new Company();

            $mapping = [
                'name' => '{{ company.name }}',
                'email' => '{{ company.email }}',
                'founded_year' => '{{ company.founded_year }}',
                'departments' => [
                    '*' => [
                        'name' => '{{ company.departments.*.name }}',
                        'code' => '{{ company.departments.*.code }}',
                        'budget' => '{{ company.departments.*.budget }}',
                        'employee_count' => '{{ company.departments.*.employee_count }}',
                    ],
                ],
            ];

            $result = DataMapper::pipeline([
                new TrimStrings(),
                new LowercaseEmails(),
            ])->sourceFile($jsonFile)->target($company)->template($mapping)->map()->getTarget();

            expect($result)->toBeInstanceOf(Company::class);
            /** @var Company $company */
            $company = $result;
            expect($company->name)->toBe('TechCorp Solutions');
            expect($company->email)->toBe('info@techcorp.example'); // Lowercased
            expect($company->founded_year)->toBe(2015);

            $dept0 = $company->departments[0] ?? null;
            expect($dept0)->toBeInstanceOf(Department::class);
            /** @var Department $dept0Model */
            $dept0Model = $dept0;
            expect($dept0Model->name)->toBe('Engineering');
            expect($dept0Model->code)->toBe('ENG');
        })->group('laravel');

        it('loads XML file and maps to Eloquent Model with transformers', function(): void {
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            $company = new Company();

            $mapping = [
                'name' => '{{ name }}',
                'email' => '{{ email }}',
                'founded_year' => '{{ founded_year }}',
                'departments' => [
                    '*' => [
                        'name' => '{{ departments.department.*.name }}',
                        'code' => '{{ departments.department.*.code }}',
                        'budget' => '{{ departments.department.*.budget }}',
                        'employee_count' => '{{ departments.department.*.employee_count }}',
                    ],
                ],
            ];

            $result = DataMapper::pipeline([
                new TrimStrings(),
                new LowercaseEmails(),
            ])->sourceFile($xmlFile)->target($company)->template($mapping)->map()->getTarget();

            expect($result)->toBeInstanceOf(Company::class);
            /** @var Company $company */
            $company = $result;
            expect($company->name)->toBe('TechCorp Solutions');
            expect($company->email)->toBe('info@techcorp.example'); // Lowercased
        })->group('laravel');
    })->group('laravel');

    describe('Pipeline with file loading - Entity targets', function(): void {
        it('loads JSON file and maps to Doctrine Entity with transformers', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            $company = new EntityCompany();

            $mapping = [
                'name' => '{{ company.name }}',
                'email' => '{{ company.email }}',
                'founded_year' => '{{ company.founded_year }}',
                'departments' => [
                    '*' => [
                        'name' => '{{ company.departments.*.name }}',
                        'code' => '{{ company.departments.*.code }}',
                        'budget' => '{{ company.departments.*.budget }}',
                        'employee_count' => '{{ company.departments.*.employee_count }}',
                    ],
                ],
            ];

            $result = DataMapper::pipeline([
                new TrimStrings(),
                new LowercaseEmails(),
            ])->sourceFile($jsonFile)->target($company)->template($mapping)->map()->getTarget();

            expect($result)->toBeInstanceOf(EntityCompany::class);
            /** @var EntityCompany $company */
            $company = $result;
            expect($company->getName())->toBe('TechCorp Solutions');
            expect($company->getEmail())->toBe('info@techcorp.example'); // Lowercased
            expect($company->getFoundedYear())->toBe(2015);
            expect($company->getDepartments()->count())->toBe(3);

            $dept0 = $company->getDepartments()[0] ?? null;
            expect($dept0)->toBeInstanceOf(EntityDepartment::class);
            /** @var EntityDepartment $dept0Entity */
            $dept0Entity = $dept0;
            expect($dept0Entity->getName())->toBe('Engineering');
            expect($dept0Entity->getCode())->toBe('ENG');
        })->group('doctrine');

        it('loads XML file and maps to Doctrine Entity with transformers', function(): void {
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            $company = new EntityCompany();

            $mapping = [
                'name' => '{{ name }}',
                'email' => '{{ email }}',
                'founded_year' => '{{ founded_year }}',
                'departments' => [
                    '*' => [
                        'name' => '{{ departments.department.*.name }}',
                        'code' => '{{ departments.department.*.code }}',
                        'budget' => '{{ departments.department.*.budget }}',
                        'employee_count' => '{{ departments.department.*.employee_count }}',
                    ],
                ],
            ];

            $result = DataMapper::pipeline([
                new TrimStrings(),
                new LowercaseEmails(),
            ])->sourceFile($xmlFile)->target($company)->template($mapping)->map()->getTarget();

            expect($result)->toBeInstanceOf(EntityCompany::class);
            /** @var EntityCompany $company */
            $company = $result;
            expect($company->getName())->toBe('TechCorp Solutions');
            expect($company->getEmail())->toBe('info@techcorp.example'); // Lowercased
            expect($company->getDepartments()->count())->toBe(3);
        })->group('doctrine');
    })->group('doctrine');

    describe('Error handling', function(): void {
        it('throws exception for non-existent file', function(): void {
            $target = [];
            $mapping = ['name' => '{{ name }}'];

            $result = DataMapper::pipeline([new TrimStrings()])
                ->sourceFile('/non/existent/file.json')->target($target)->template($mapping)->map()->getTarget();
            expect($result)->toBeArray();
        })->throws(InvalidArgumentException::class, 'File not found');

        it('throws exception for unsupported file format', function(): void {
            $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.txt';
            file_put_contents($tempFile, 'test content');

            $target = [];
            $mapping = ['name' => '{{ name }}'];

            try {
                $result = DataMapper::pipeline([new TrimStrings()])
                    ->sourceFile($tempFile)->target($target)->template($mapping)->map()->getTarget();
                expect($result)->toBeArray();
            } finally {
                unlink($tempFile);
            }
        })->throws(InvalidArgumentException::class, 'Unsupported file format');
    })->group('doctrine');
});
