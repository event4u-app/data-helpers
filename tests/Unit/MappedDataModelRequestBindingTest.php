<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use event4u\DataHelpers\DataMapper\MapperExceptions;
use event4u\DataHelpers\MappedDataModel;
use SimpleXMLElement;

/**
 * Mock Request class that simulates Laravel/Symfony Request
 */
class MockRequest
{
    /** @param array<string, mixed> $data */
    public function __construct(private readonly array $data, private readonly string $contentType = 'application/json') {}

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->data;
    }

    public function getContent(): string
    {
        if ('application/json' === $this->contentType) {
            /** @var non-empty-string|false $encoded */
            $encoded = json_encode($this->data);
            return false !== $encoded ? $encoded : '';
        }

        if ('application/xml' === $this->contentType || 'text/xml' === $this->contentType) {
            return $this->arrayToXml($this->data);
        }

        return '';
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    /** @param array<string, mixed> $data */
    private function arrayToXml(array $data, string $rootElement = 'root'): string
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><' . $rootElement . '></' . $rootElement . '>'
        );
        $this->arrayToXmlRecursive($data, $xml);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        /** @var string|false $xmlString */
        $xmlString = $xml->asXML();
        $dom->loadXML(false !== $xmlString ? $xmlString : '');

        /** @var string|false $result */
        $result = $dom->saveXML();
        return false !== $result ? $result : '';
    }

    /** @param array<string, mixed> $array */
    private function arrayToXmlRecursive(array $array, SimpleXMLElement $xml): void
    {
        foreach ($array as $key => $value) {
            if (is_numeric($key)) {
                $key = 'item';
            }

            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                /** @var array<string, mixed> $valueArray */
                $valueArray = $value;
                $this->arrayToXmlRecursive($valueArray, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8'));
            }
        }
    }
}

/**
 * Test model for Request Binding with JSON
 *
 * @property string $company_name
 * @property string $company_email
 * @property array<int, array{name: string, code: string}> $departments
 */
class CompanyRequestModel extends MappedDataModel
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
 * Test model for Request Binding with XML
 *
 * @property string $company_name
 * @property string $company_email
 * @property array<int, array{name: string, code: string}> $departments
 */
class CompanyRequestModelXml extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'company_name' => '{{ request.name }}',
            'company_email' => '{{ request.email }}',
            'departments' => [
                '*' => [
                    'name' => '{{ request.departments.item.*.name }}',
                    'code' => '{{ request.departments.item.*.code }}',
                ],
            ],
        ];
    }
}

describe('MappedDataModel with Request Binding', function(): void {
    beforeEach(function(): void {
        MapperExceptions::reset();
    });
    afterEach(function(): void {
        MapperExceptions::reset();
    });

    describe('Laravel/Symfony Request with JSON', function(): void {
        beforeEach(function(): void {
            MapperExceptions::reset();
        });
        afterEach(function(): void {
            MapperExceptions::reset();
        });

        it('binds model from Request object with JSON data', function(): void {
            // Simulate Laravel Request with JSON body
            $request = new MockRequest([
                'company' => [
                    'name' => 'TechCorp Solutions',
                    'email' => 'info@techcorp.example',
                    'departments' => [
                        ['name' => 'Engineering', 'code' => 'ENG'],
                        ['name' => 'Sales', 'code' => 'SAL'],
                        ['name' => 'Human Resources', 'code' => 'HR'],
                    ],
                ],
            ], 'application/json');

            // Simulate Laravel Model Binding
            $model = new CompanyRequestModel($request);

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

        it('binds model using fromRequest with Request object', function(): void {
            $request = new MockRequest([
                'company' => [
                    'name' => 'StartupCo',
                    'email' => 'hello@startup.example',
                    'departments' => [
                        ['name' => 'Development', 'code' => 'DEV'],
                        ['name' => 'Marketing', 'code' => 'MKT'],
                    ],
                ],
            ], 'application/json');

            // Simulate Laravel Controller method signature:
            // public function store(CompanyRequestModel $model)
            $model = CompanyRequestModel::fromRequest($request);

            expect($model->isMapped())->toBeTrue();
            expect($model->company_name)->toBe('StartupCo');
            expect($model->company_email)->toBe('hello@startup.example');
            expect($model->departments)->toHaveCount(2);
        });

        it('binds model from Request with nested data', function(): void {
            $request = new MockRequest([
                'company' => [
                    'name' => 'Enterprise Inc',
                    'email' => 'contact@enterprise.example',
                    'departments' => [
                        [
                            'name' => 'IT',
                            'code' => 'IT',
                        ],
                        [
                            'name' => 'Finance',
                            'code' => 'FIN',
                        ],
                    ],
                ],
            ], 'application/json');

            $model = new CompanyRequestModel($request);

            expect($model->toArray())->toBeArray();
            expect($model->toArray()['company_name'])->toBe('Enterprise Inc');
            expect($model->toArray()['departments'])->toHaveCount(2);
        });

        it('converts bound model to JSON', function(): void {
            $request = new MockRequest([
                'company' => [
                    'name' => 'JsonCorp',
                    'email' => 'json@corp.example',
                    'departments' => [],
                ],
            ], 'application/json');

            $model = new CompanyRequestModel($request);
            $json = (string)$model;

            expect($json)->toBeString();
            $decoded = json_decode($json, true);
            expect($decoded['company_name'])->toBe('JsonCorp');
            expect($decoded['company_email'])->toBe('json@corp.example');
        });
    });

    describe('Laravel/Symfony Request with XML', function(): void {
        beforeEach(function(): void {
            MapperExceptions::reset();
        });
        afterEach(function(): void {
            MapperExceptions::reset();
        });

        it('binds model from Request object with XML data', function(): void {
            // Simulate Laravel Request with XML body
            $request = new MockRequest([
                'name' => 'TechCorp Solutions',
                'email' => 'info@techcorp.example',
                'departments' => [
                    ['name' => 'Engineering', 'code' => 'ENG'],
                    ['name' => 'Sales', 'code' => 'SAL'],
                    ['name' => 'Human Resources', 'code' => 'HR'],
                ],
            ], 'application/xml');

            // Get XML content from request
            $xmlContent = $request->getContent();

            // Simulate Laravel Model Binding with XML
            $model = new CompanyRequestModelXml($xmlContent);

            expect($model->isMapped())->toBeTrue();
            expect($model->company_name)->toBe('TechCorp Solutions');
            expect($model->company_email)->toBe('info@techcorp.example');
            expect($model->departments)->toBeArray();
            expect($model->departments)->toHaveCount(3);
        });

        it('binds model using fromRequest with XML Request object', function(): void {
            $request = new MockRequest([
                'name' => 'StartupCo',
                'email' => 'hello@startup.example',
                'departments' => [
                    ['name' => 'Development', 'code' => 'DEV'],
                    ['name' => 'Marketing', 'code' => 'MKT'],
                ],
            ], 'application/xml');

            $xmlContent = $request->getContent();
            $model = CompanyRequestModelXml::fromRequest($xmlContent);

            expect($model->isMapped())->toBeTrue();
            expect($model->company_name)->toBe('StartupCo');
            expect($model->departments)->toHaveCount(2);
        });
    });

    describe('Real-world Controller simulation', function(): void {
        beforeEach(function(): void {
            MapperExceptions::reset();
        });
        afterEach(function(): void {
            MapperExceptions::reset();
        });

        it('simulates Laravel Controller with JSON request', function(): void {
            // Simulate: POST /api/companies with JSON body
            $request = new MockRequest([
                'company' => [
                    'name' => 'API Corp',
                    'email' => 'api@corp.example',
                    'departments' => [
                        ['name' => 'Backend', 'code' => 'BE'],
                        ['name' => 'Frontend', 'code' => 'FE'],
                    ],
                ],
            ], 'application/json');

            // Simulate Controller method:
            // public function store(CompanyRequestModel $model)
            // {
            //     return response()->json($model->toArray());
            // }
            $model = CompanyRequestModel::fromRequest($request);
            $response = $model->toArray();

            expect($response)->toBeArray();
            expect($response['company_name'])->toBe('API Corp');
            expect($response['departments'])->toHaveCount(2);
        });

        it('simulates Laravel Controller with validation', function(): void {
            $request = new MockRequest([
                'company' => [
                    'name' => 'ValidCorp',
                    'email' => 'valid@corp.example',
                    'departments' => [
                        ['name' => 'QA', 'code' => 'QA'],
                    ],
                ],
            ], 'application/json');

            $model = CompanyRequestModel::fromRequest($request);

            // Simulate validation checks
            expect($model->has('company_name'))->toBeTrue();
            expect($model->has('company_email'))->toBeTrue();
            expect($model->has('departments'))->toBeTrue();
            expect($model->get('company_name'))->not->toBeEmpty();
            expect($model->get('company_email'))->toContain('@');
        });
    });
});

