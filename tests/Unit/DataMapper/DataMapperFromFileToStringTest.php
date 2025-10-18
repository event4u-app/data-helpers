<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('DataMapper to JSON/XML String', function(): void {
    describe('JSON string as target', function(): void {
        it('maps from JSON file to JSON string', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            // Use JSON string as target
            $target = '{}';
            $mapping = [
                'company_name' => '{{ company.name }}',
                'company_reg' => '{{ company.registration_number }}',
                'company_email' => '{{ company.email }}',
                'company_phone' => '{{ company.phone }}',
                'company_founded_year' => '{{ company.founded_year }}',
                'company_employee_count' => '{{ company.employee_count }}',
            ];

            $result = DataMapper::sourceFile($jsonFile)->target($target)->template($mapping)->map()->getTarget();

            // Result should be a JSON string
            expect($result)->toBeString();
            /** @var string $resultString */
            $resultString = $result;
            $decoded = json_decode($resultString, true);
            expect($decoded)->toBeArray();
            expect($decoded['company_name'])->toBe('TechCorp Solutions');
            expect($decoded['company_reg'])->toBe('REG-2024-001');
            expect($decoded['company_email'])->toBe('info@techcorp.example');
            expect($decoded['company_phone'])->toBe('+1-555-0123');
            expect($decoded['company_founded_year'])->toBe(2015);
            expect($decoded['company_employee_count'])->toBe(250);
        });

        it('maps from XML file to JSON string', function(): void {
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            // Use JSON string as target
            $target = '{}';
            $mapping = [
                'company_name' => '{{ name }}',
                'company_reg' => '{{ registration_number }}',
                'company_email' => '{{ email }}',
                'company_phone' => '{{ phone }}',
                'company_founded_year' => '{{ founded_year }}',
                'company_employee_count' => '{{ employee_count }}',
            ];

            $result = DataMapper::sourceFile($xmlFile)->target($target)->template($mapping)->map()->getTarget();

            // Result should be a JSON string
            expect($result)->toBeString();
            /** @var string $resultString */
            $resultString = $result;
            $decoded = json_decode($resultString, true);
            expect($decoded)->toBeArray();
            expect($decoded['company_name'])->toBe('TechCorp Solutions');
            expect($decoded['company_reg'])->toBe('REG-2024-001');
            expect($decoded['company_email'])->toBe('info@techcorp.example');
            expect($decoded['company_phone'])->toBe('+1-555-0123');
            // XML values are strings
            expect($decoded['company_founded_year'])->toBe('2015');
            expect($decoded['company_employee_count'])->toBe('250');
        });

        it('maps nested departments from JSON to JSON string', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            // Use JSON string as target
            $target = '{}';
            $mapping = [
                'company_name' => '{{ company.name }}',
                'departments' => [
                    '*' => [
                        'name' => '{{ company.departments.*.name }}',
                        'code' => '{{ company.departments.*.code }}',
                        'budget' => '{{ company.departments.*.budget }}',
                    ],
                ],
            ];

            $result = DataMapper::sourceFile($jsonFile)->target($target)->template($mapping)->map()->getTarget();

            expect($result)->toBeString();
            /** @var string $resultString */
            $resultString = $result;
            $decoded = json_decode($resultString, true);
            expect($decoded)->toBeArray();
            expect($decoded['company_name'])->toBe('TechCorp Solutions');
            expect($decoded['departments'])->toBeArray();
            expect($decoded['departments'])->toHaveCount(3);
            expect($decoded['departments'][0]['name'])->toBe('Engineering');
            expect($decoded['departments'][0]['code'])->toBe('ENG');
            expect($decoded['departments'][0]['budget'])->toBe(5000000);
        });

        it('maps wildcard paths from JSON to JSON string', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            $target = '{}';
            $mapping = [
                'dept_names' => '{{ company.departments.*.name }}',
                'dept_codes' => '{{ company.departments.*.code }}',
            ];

            $result = DataMapper::sourceFile($jsonFile)->target($target)->template($mapping)->map()->getTarget();

            expect($result)->toBeString();
            /** @var string $resultString */
            $resultString = $result;
            $decoded = json_decode($resultString, true);
            expect($decoded)->toBeArray();
            expect($decoded['dept_names'])->toBeArray();
            expect($decoded['dept_names'])->toHaveCount(3);
            expect($decoded['dept_names'])->toBe(['Engineering', 'Sales', 'Human Resources']);
            expect($decoded['dept_codes'])->toBeArray();
            expect($decoded['dept_codes'])->toHaveCount(3);
            expect($decoded['dept_codes'])->toBe(['ENG', 'SAL', 'HR']);
        });
    });

    describe('XML string as target', function(): void {
        it('maps from JSON file to XML string', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            // Use XML string as target
            $target = '<?xml version="1.0"?><root></root>';
            $mapping = [
                'company_name' => '{{ company.name }}',
                'company_reg' => '{{ company.registration_number }}',
                'company_email' => '{{ company.email }}',
                'company_phone' => '{{ company.phone }}',
                'company_founded_year' => '{{ company.founded_year }}',
                'company_employee_count' => '{{ company.employee_count }}',
            ];

            $result = DataMapper::sourceFile($jsonFile)->target($target)->template($mapping)->map()->getTarget();

            // Result should be an XML string
            expect($result)->toBeString();
            expect($result)->toContain('<?xml');
            expect($result)->toContain('<company_name>TechCorp Solutions</company_name>');
            expect($result)->toContain('<company_reg>REG-2024-001</company_reg>');
            expect($result)->toContain('<company_email>info@techcorp.example</company_email>');
            expect($result)->toContain('<company_phone>+1-555-0123</company_phone>');
            expect($result)->toContain('<company_founded_year>2015</company_founded_year>');
            expect($result)->toContain('<company_employee_count>250</company_employee_count>');
        });

        it('maps from XML file to XML string', function(): void {
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            // Use XML string as target
            $target = '<?xml version="1.0"?><root></root>';
            $mapping = [
                'company_name' => '{{ name }}',
                'company_reg' => '{{ registration_number }}',
                'company_email' => '{{ email }}',
                'company_phone' => '{{ phone }}',
                'company_founded_year' => '{{ founded_year }}',
                'company_employee_count' => '{{ employee_count }}',
            ];

            $result = DataMapper::sourceFile($xmlFile)->target($target)->template($mapping)->map()->getTarget();

            // Result should be an XML string
            expect($result)->toBeString();
            expect($result)->toContain('<?xml');
            expect($result)->toContain('<company_name>TechCorp Solutions</company_name>');
            expect($result)->toContain('<company_reg>REG-2024-001</company_reg>');
            expect($result)->toContain('<company_email>info@techcorp.example</company_email>');
            expect($result)->toContain('<company_phone>+1-555-0123</company_phone>');
            // XML values are strings
            expect($result)->toContain('<company_founded_year>2015</company_founded_year>');
            expect($result)->toContain('<company_employee_count>250</company_employee_count>');
        });

        it('maps nested departments from XML to XML string', function(): void {
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            // Use XML string as target
            $target = '<?xml version="1.0"?><root></root>';
            $mapping = [
                'company_name' => '{{ name }}',
                'departments' => [
                    '*' => [
                        'name' => '{{ departments.department.*.name }}',
                        'code' => '{{ departments.department.*.code }}',
                        'budget' => '{{ departments.department.*.budget }}',
                    ],
                ],
            ];

            $result = DataMapper::sourceFile($xmlFile)->target($target)->template($mapping)->map()->getTarget();

            expect($result)->toBeString();
            expect($result)->toContain('<?xml');
            expect($result)->toContain('<company_name>TechCorp Solutions</company_name>');
            expect($result)->toContain('<name>Engineering</name>');
            expect($result)->toContain('<code>ENG</code>');
            expect($result)->toContain('<budget>5000000.00</budget>');
        });

        it('maps wildcard paths from XML to XML string', function(): void {
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            $target = '<?xml version="1.0"?><root></root>';
            $mapping = [
                'dept_names' => '{{ departments.department.*.name }}',
                'dept_codes' => '{{ departments.department.*.code }}',
            ];

            $result = DataMapper::sourceFile($xmlFile)->target($target)->template($mapping)->map()->getTarget();

            expect($result)->toBeString();
            expect($result)->toContain('<?xml');
            expect($result)->toContain('<dept_names>');
            expect($result)->toContain('<dept_name>Engineering</dept_name>');
            expect($result)->toContain('<dept_name>Sales</dept_name>');
            expect($result)->toContain('<dept_name>Human Resources</dept_name>');
            expect($result)->toContain('<dept_codes>');
            expect($result)->toContain('<dept_code>ENG</dept_code>');
        });
    });

    describe('Comparison between JSON and XML targets', function(): void {
        it('produces results in correct format for JSON and XML string targets', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            $mapping = [
                'company_name' => '{{ company.name }}',
                'company_email' => '{{ company.email }}',
            ];

            // Map to JSON string target
            $jsonTarget = '{}';
            $jsonResult = DataMapper::sourceFile($jsonFile)->target($jsonTarget)->template(
                $mapping
            )->map()->getTarget();

            // Map to XML string target
            $xmlTarget = '<?xml version="1.0"?><root></root>';
            $xmlResult = DataMapper::sourceFile($jsonFile)->target($xmlTarget)->template($mapping)->map()->getTarget();

            // JSON result should be JSON string
            expect($jsonResult)->toBeString();
            /** @var string $jsonResultString */
            $jsonResultString = $jsonResult;
            $jsonDecoded = json_decode($jsonResultString, true);
            expect($jsonDecoded['company_name'])->toBe('TechCorp Solutions');
            expect($jsonDecoded['company_email'])->toBe('info@techcorp.example');

            // XML result should be XML string
            expect($xmlResult)->toBeString();
            expect($xmlResult)->toContain('<?xml');
            expect($xmlResult)->toContain('<company_name>TechCorp Solutions</company_name>');
            expect($xmlResult)->toContain('<company_email>info@techcorp.example</company_email>');
        });

        it('handles complex nested structures with both target types', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            $mapping = [
                'company' => [
                    'name' => '{{ company.name }}',
                    'email' => '{{ company.email }}',
                ],
                'departments' => [
                    '*' => [
                        'name' => '{{ company.departments.*.name }}',
                        'code' => '{{ company.departments.*.code }}',
                    ],
                ],
            ];

            // Map to JSON string target
            $jsonTarget = '{}';
            $jsonResult = DataMapper::sourceFile($jsonFile)->target($jsonTarget)->template(
                $mapping
            )->map()->getTarget();

            // Map to XML string target
            $xmlTarget = '<?xml version="1.0"?><root></root>';
            $xmlResult = DataMapper::sourceFile($jsonFile)->target($xmlTarget)->template($mapping)->map()->getTarget();

            // JSON result should be JSON string with nested structure
            expect($jsonResult)->toBeString();
            /** @var string $jsonResultString */
            $jsonResultString = $jsonResult;
            $jsonDecoded = json_decode($jsonResultString, true);
            expect($jsonDecoded['company']['name'])->toBe('TechCorp Solutions');
            expect($jsonDecoded['company']['email'])->toBe('info@techcorp.example');
            expect($jsonDecoded['departments'])->toHaveCount(3);
            expect($jsonDecoded['departments'][0]['name'])->toBe('Engineering');

            // XML result should be XML string with nested structure
            expect($xmlResult)->toBeString();
            expect($xmlResult)->toContain('<?xml');
            expect($xmlResult)->toContain('<company>');
            expect($xmlResult)->toContain('<name>TechCorp Solutions</name>');
            expect($xmlResult)->toContain('<departments>');
        });
    });
});

