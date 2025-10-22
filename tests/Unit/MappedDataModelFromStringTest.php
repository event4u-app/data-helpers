<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\MappedDataModel;
use InvalidArgumentException;

/**
 * Test model for JSON/XML string input
 *
 * @property string $company_name
 * @property string $company_email
 * @property array<int, array{name: string, code: string}> $departments
 */
class CompanyDataModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'company_name' => '{{ request.company.name }}',
            'company_email' => '{{ request.company.email }}',
            'departments' => [
                '*' => [
                    'name' => '{{ request.company.departments.*.name }}',
                    'code' => '{{ request.company.departments.*.code }}',
                ],
            ],
        ];
    }
}

/**
 * Test model for XML string input
 *
 * @property string $company_name
 * @property string $company_email
 * @property array<int, array{name: string, code: string}> $departments
 */
class CompanyDataModelXml extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'company_name' => '{{ request.name }}',
            'company_email' => '{{ request.email }}',
            'departments' => [
                '*' => [
                    'name' => '{{ request.departments.department.*.name }}',
                    'code' => '{{ request.departments.department.*.code }}',
                ],
            ],
        ];
    }
}

describe('MappedDataModel with JSON/XML String Input', function(): void {
    describe('JSON string as input', function(): void {
        it('creates model from JSON string', function(): void {
            /** @var non-empty-string|false $jsonString */
            $jsonString = json_encode([
                'company' => [
                    'name' => 'TechCorp Solutions',
                    'email' => 'info@techcorp.example',
                    'departments' => [
                        ['name' => 'Engineering', 'code' => 'ENG'],
                        ['name' => 'Sales', 'code' => 'SAL'],
                        ['name' => 'Human Resources', 'code' => 'HR'],
                    ],
                ],
            ]);
            $jsonString = false !== $jsonString ? $jsonString : '{}';

            $model = new CompanyDataModel($jsonString);

            expect($model->isMapped())->toBeTrue();
            expect($model->company_name)->toBe('TechCorp Solutions');
            expect($model->company_email)->toBe('info@techcorp.example');
            expect($model->departments)->toBeArray();
            expect($model->departments)->toHaveCount(3);
            expect($model->departments[0]['name'])->toBe('Engineering');
            expect($model->departments[0]['code'])->toBe('ENG');
            expect($model->departments[1]['name'])->toBe('Sales');
            expect($model->departments[2]['name'])->toBe('Human Resources');
        });

        it('fills model with JSON string', function(): void {
            $model = new CompanyDataModel();

            /** @var non-empty-string|false $jsonString */
            $jsonString = json_encode([
                'company' => [
                    'name' => 'StartupCo',
                    'email' => 'hello@startup.example',
                    'departments' => [
                        ['name' => 'Development', 'code' => 'DEV'],
                    ],
                ],
            ]);
            $jsonString = false !== $jsonString ? $jsonString : '{}';

            $model->fill($jsonString);

            expect($model->isMapped())->toBeTrue();
            expect($model->company_name)->toBe('StartupCo');
            expect($model->company_email)->toBe('hello@startup.example');
            expect($model->departments)->toHaveCount(1);
            expect($model->departments[0]['name'])->toBe('Development');
        });

        it('creates model from JSON string using fromRequest', function(): void {
            /** @var non-empty-string|false $jsonString */
            $jsonString = json_encode([
                'company' => [
                    'name' => 'Enterprise Inc',
                    'email' => 'contact@enterprise.example',
                    'departments' => [
                        ['name' => 'IT', 'code' => 'IT'],
                        ['name' => 'Finance', 'code' => 'FIN'],
                    ],
                ],
            ]);
            $jsonString = false !== $jsonString ? $jsonString : '{}';

            $model = CompanyDataModel::fromRequest($jsonString);

            expect($model->isMapped())->toBeTrue();
            expect($model->company_name)->toBe('Enterprise Inc');
            expect($model->departments)->toHaveCount(2);
        });

        it('converts model to array after JSON string input', function(): void {
            /** @var non-empty-string|false $jsonString */
            $jsonString = json_encode([
                'company' => [
                    'name' => 'TestCorp',
                    'email' => 'test@test.example',
                    'departments' => [
                        ['name' => 'QA', 'code' => 'QA'],
                    ],
                ],
            ]);
            $jsonString = false !== $jsonString ? $jsonString : '{}';

            $model = new CompanyDataModel($jsonString);
            $array = $model->toArray();

            expect($array)->toBeArray();
            expect($array['company_name'])->toBe('TestCorp');
            expect($array['company_email'])->toBe('test@test.example');
            expect($array['departments'])->toHaveCount(1);
        });

        it('converts model to JSON after JSON string input', function(): void {
            /** @var non-empty-string|false $jsonString */
            $jsonString = json_encode([
                'company' => [
                    'name' => 'JsonCorp',
                    'email' => 'json@corp.example',
                    'departments' => [],
                ],
            ]);
            $jsonString = false !== $jsonString ? $jsonString : '{}';

            $model = new CompanyDataModel($jsonString);
            $json = (string)$model;

            expect($json)->toBeString();
            $decoded = json_decode($json, true);
            expect($decoded['company_name'])->toBe('JsonCorp');
            expect($decoded['company_email'])->toBe('json@corp.example');
        });
    });

    describe('XML string as input', function(): void {
        it('creates model from XML string', function(): void {
            $xmlString = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<root>
    <name>TechCorp Solutions</name>
    <email>info@techcorp.example</email>
    <departments>
        <department>
            <name>Engineering</name>
            <code>ENG</code>
        </department>
        <department>
            <name>Sales</name>
            <code>SAL</code>
        </department>
        <department>
            <name>Human Resources</name>
            <code>HR</code>
        </department>
    </departments>
</root>
XML;

            $model = new CompanyDataModelXml($xmlString);

            expect($model->isMapped())->toBeTrue();
            expect($model->company_name)->toBe('TechCorp Solutions');
            expect($model->company_email)->toBe('info@techcorp.example');
            expect($model->departments)->toBeArray();
            expect($model->departments)->toHaveCount(3);
            expect($model->departments[0]['name'])->toBe('Engineering');
            expect($model->departments[0]['code'])->toBe('ENG');
        });

        it('fills model with XML string', function(): void {
            $model = new CompanyDataModelXml();

            $xmlString = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<root>
    <name>StartupCo</name>
    <email>hello@startup.example</email>
    <departments>
        <department>
            <name>Development</name>
            <code>DEV</code>
        </department>
        <department>
            <name>Marketing</name>
            <code>MKT</code>
        </department>
    </departments>
</root>
XML;

            $model->fill($xmlString);

            expect($model->isMapped())->toBeTrue();
            expect($model->company_name)->toBe('StartupCo');
            expect($model->company_email)->toBe('hello@startup.example');
            expect($model->departments)->toBeArray();
            expect($model->departments)->toHaveCount(2);
            expect($model->departments[0]['name'])->toBe('Development');
        });

        it('creates model from XML string using fromRequest', function(): void {
            $xmlString = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<root>
    <name>Enterprise Inc</name>
    <email>contact@enterprise.example</email>
    <departments>
        <department>
            <name>IT</name>
            <code>IT</code>
        </department>
        <department>
            <name>Finance</name>
            <code>FIN</code>
        </department>
    </departments>
</root>
XML;

            $model = CompanyDataModelXml::fromRequest($xmlString);

            expect($model->isMapped())->toBeTrue();
            expect($model->company_name)->toBe('Enterprise Inc');
            expect($model->departments)->toBeArray();
            expect($model->departments)->toHaveCount(2);
        });

        it('converts model to array after XML string input', function(): void {
            $xmlString = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<root>
    <name>TestCorp</name>
    <email>test@test.example</email>
    <departments>
        <department>
            <name>QA</name>
            <code>QA</code>
        </department>
        <department>
            <name>Support</name>
            <code>SUP</code>
        </department>
    </departments>
</root>
XML;

            $model = new CompanyDataModelXml($xmlString);
            $array = $model->toArray();

            expect($array)->toBeArray();
            expect($array['company_name'])->toBe('TestCorp');
            expect($array['company_email'])->toBe('test@test.example');
            expect($array['departments'])->toBeArray();
            expect($array['departments'])->toHaveCount(2);
        });
    });

    describe('Error handling', function(): void {
        it('throws exception for invalid JSON string', function(): void {
            expect(fn(): CompanyDataModel => new CompanyDataModel('invalid json {'))
                ->toThrow(InvalidArgumentException::class, 'Input string is neither valid JSON nor valid XML');
        });

        it('throws exception for invalid XML string', function(): void {
            expect(fn(): CompanyDataModelXml => new CompanyDataModelXml('<invalid><xml'))
                ->toThrow(InvalidArgumentException::class, 'Input string is neither valid JSON nor valid XML');
        });

        it('throws exception for empty string', function(): void {
            expect(fn(): CompanyDataModel => new CompanyDataModel(''))
                ->toThrow(InvalidArgumentException::class, 'Input string is neither valid JSON nor valid XML');
        });
    });
});
