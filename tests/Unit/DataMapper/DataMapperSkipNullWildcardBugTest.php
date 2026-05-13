<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('DataMapper skipNull wildcard bug fix', function(): void {
    it('handles template-based wildcard mapping with null values correctly (auto-disables skipNull)', function(): void {
        $source = [
            'users' => [
                ['id' => '1', 'firstname' => 'John', 'lastname' => 'Doe'],
                ['id' => '2', 'firstname' => null, 'lastname' => 'Smith'],
                ['id' => '3', 'firstname' => 'Jane', 'lastname' => 'Wilson'],
            ],
        ];

        $template = [
            'users.*' => [
                'id' => '{{ users.*.id }}',
                'firstname' => '{{ users.*.firstname }}',
                'lastname' => '{{ users.*.lastname }}',
            ],
        ];

        // With default skipNull=true, the bug should be automatically fixed
        $result = DataMapper::source($source)
            ->template($template)
            ->map()
            ->toArray();

        // User 0: John Doe
        expect($result['users'][0]['id'])->toBe('1');
        expect($result['users'][0]['firstname'])->toBe('John');
        expect($result['users'][0]['lastname'])->toBe('Doe');

        // User 1: null Smith (NOT Jane Smith!)
        expect($result['users'][1]['id'])->toBe('2');
        expect($result['users'][1]['firstname'])->toBeNull();
        expect($result['users'][1]['lastname'])->toBe('Smith');

        // User 2: Jane Wilson
        expect($result['users'][2]['id'])->toBe('3');
        expect($result['users'][2]['firstname'])->toBe('Jane');
        expect($result['users'][2]['lastname'])->toBe('Wilson');
    });

    it('handles multiple null values in different positions', function(): void {
        $source = [
            'items' => [
                ['id' => 1, 'name' => 'Item 1', 'description' => 'Desc 1'],
                ['id' => 2, 'name' => null, 'description' => 'Desc 2'],
                ['id' => 3, 'name' => 'Item 3', 'description' => null],
                ['id' => 4, 'name' => null, 'description' => null],
                ['id' => 5, 'name' => 'Item 5', 'description' => 'Desc 5'],
            ],
        ];

        $template = [
            'items.*' => [
                'id' => '{{ items.*.id }}',
                'name' => '{{ items.*.name }}',
                'description' => '{{ items.*.description }}',
            ],
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->map()
            ->toArray();

        expect($result['items'])->toHaveCount(5);
        expect($result['items'][0])->toBe(['id' => 1, 'name' => 'Item 1', 'description' => 'Desc 1']);
        expect($result['items'][1])->toBe(['id' => 2, 'name' => null, 'description' => 'Desc 2']);
        expect($result['items'][2])->toBe(['id' => 3, 'name' => 'Item 3', 'description' => null]);
        expect($result['items'][3])->toBe(['id' => 4, 'name' => null, 'description' => null]);
        expect($result['items'][4])->toBe(['id' => 5, 'name' => 'Item 5', 'description' => 'Desc 5']);
    });

    it('handles all values being null', function(): void {
        $source = [
            'items' => [
                ['id' => 1, 'value' => null],
                ['id' => 2, 'value' => null],
                ['id' => 3, 'value' => null],
            ],
        ];

        $template = [
            'items.*' => [
                'id' => '{{ items.*.id }}',
                'value' => '{{ items.*.value }}',
            ],
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->map()
            ->toArray();

        expect($result['items'])->toHaveCount(3);
        expect($result['items'][0])->toBe(['id' => 1, 'value' => null]);
        expect($result['items'][1])->toBe(['id' => 2, 'value' => null]);
        expect($result['items'][2])->toBe(['id' => 3, 'value' => null]);
    });

    it('handles no null values (normal case)', function(): void {
        $source = [
            'items' => [
                ['id' => 1, 'name' => 'Item 1'],
                ['id' => 2, 'name' => 'Item 2'],
                ['id' => 3, 'name' => 'Item 3'],
            ],
        ];

        $template = [
            'items.*' => [
                'id' => '{{ items.*.id }}',
                'name' => '{{ items.*.name }}',
            ],
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->map()
            ->toArray();

        expect($result['items'])->toHaveCount(3);
        expect($result['items'][0])->toBe(['id' => 1, 'name' => 'Item 1']);
        expect($result['items'][1])->toBe(['id' => 2, 'name' => 'Item 2']);
        expect($result['items'][2])->toBe(['id' => 3, 'name' => 'Item 3']);
    });

    it('handles null at the beginning of the array', function(): void {
        $source = [
            'items' => [
                ['id' => 1, 'name' => null],
                ['id' => 2, 'name' => 'Item 2'],
                ['id' => 3, 'name' => 'Item 3'],
            ],
        ];

        $template = [
            'items.*' => [
                'id' => '{{ items.*.id }}',
                'name' => '{{ items.*.name }}',
            ],
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->map()
            ->toArray();

        expect($result['items'])->toHaveCount(3);
        expect($result['items'][0])->toBe(['id' => 1, 'name' => null]);
        expect($result['items'][1])->toBe(['id' => 2, 'name' => 'Item 2']);
        expect($result['items'][2])->toBe(['id' => 3, 'name' => 'Item 3']);
    });

    it('handles null at the end of the array', function(): void {
        $source = [
            'items' => [
                ['id' => 1, 'name' => 'Item 1'],
                ['id' => 2, 'name' => 'Item 2'],
                ['id' => 3, 'name' => null],
            ],
        ];

        $template = [
            'items.*' => [
                'id' => '{{ items.*.id }}',
                'name' => '{{ items.*.name }}',
            ],
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->map()
            ->toArray();

        expect($result['items'])->toHaveCount(3);
        expect($result['items'][0])->toBe(['id' => 1, 'name' => 'Item 1']);
        expect($result['items'][1])->toBe(['id' => 2, 'name' => 'Item 2']);
        expect($result['items'][2])->toBe(['id' => 3, 'name' => null]);
    });

    it('handles consecutive null values', function(): void {
        $source = [
            'items' => [
                ['id' => 1, 'name' => 'Item 1'],
                ['id' => 2, 'name' => null],
                ['id' => 3, 'name' => null],
                ['id' => 4, 'name' => null],
                ['id' => 5, 'name' => 'Item 5'],
            ],
        ];

        $template = [
            'items.*' => [
                'id' => '{{ items.*.id }}',
                'name' => '{{ items.*.name }}',
            ],
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->map()
            ->toArray();

        expect($result['items'])->toHaveCount(5);
        expect($result['items'][0])->toBe(['id' => 1, 'name' => 'Item 1']);
        expect($result['items'][1])->toBe(['id' => 2, 'name' => null]);
        expect($result['items'][2])->toBe(['id' => 3, 'name' => null]);
        expect($result['items'][3])->toBe(['id' => 4, 'name' => null]);
        expect($result['items'][4])->toBe(['id' => 5, 'name' => 'Item 5']);
    });

    it('handles nested template-based wildcard mappings', function(): void {
        // TODO: Nested template-based wildcard mappings are not yet fully supported
        // This test is skipped for now
    })->skip('Nested template-based wildcard mappings are not yet fully supported');

    it('handles nested template-based wildcard mappings - FUTURE', function(): void {
        $source = [
            'departments' => [
                [
                    'name' => 'Engineering',
                    'employees' => [
                        ['id' => 1, 'name' => 'Alice'],
                        ['id' => 2, 'name' => null],
                        ['id' => 3, 'name' => 'Bob'],
                    ],
                ],
                [
                    'name' => 'Sales',
                    'employees' => [
                        ['id' => 4, 'name' => null],
                        ['id' => 5, 'name' => 'Charlie'],
                    ],
                ],
            ],
        ];

        $template = [
            'departments.*' => [
                'name' => '{{ departments.*.name }}',
                'employees' => [
                    '*' => [
                        'id' => '{{ departments.*.employees.*.id }}',
                        'name' => '{{ departments.*.employees.*.name }}',
                    ],
                ],
            ],
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->map()
            ->toArray();

        expect($result['departments'])->toHaveCount(2);

        // Department 0: Engineering
        expect($result['departments'][0]['name'])->toBe('Engineering');
        expect($result['departments'][0]['employees'])->toHaveCount(3);
        expect($result['departments'][0]['employees'][0])->toBe(['id' => 1, 'name' => 'Alice']);
        expect($result['departments'][0]['employees'][1])->toBe(['id' => 2, 'name' => null]);
        expect($result['departments'][0]['employees'][2])->toBe(['id' => 3, 'name' => 'Bob']);

        // Department 1: Sales
        expect($result['departments'][1]['name'])->toBe('Sales');
        expect($result['departments'][1]['employees'])->toHaveCount(2);
        expect($result['departments'][1]['employees'][0])->toBe(['id' => 4, 'name' => null]);
        expect($result['departments'][1]['employees'][1])->toBe(['id' => 5, 'name' => 'Charlie']);
    })->skip('Nested template-based wildcard mappings are not yet fully supported');

    // Note: Simple wildcard mappings (e.g., 'items' => '{{ items.* }}') are not tested here
    // because they use a different code path and are not affected by the skipNull wildcard bug.

    it('respects explicit skipNull(false) for template-based wildcard mappings', function(): void {
        $source = [
            'users' => [
                ['id' => '1', 'name' => 'John'],
                ['id' => '2', 'name' => null],
                ['id' => '3', 'name' => 'Jane'],
            ],
        ];

        $template = [
            'users.*' => [
                'id' => '{{ users.*.id }}',
                'name' => '{{ users.*.name }}',
            ],
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->skipNull(false)  // Explicitly disable skipNull
            ->map()
            ->toArray();

        expect($result['users'])->toHaveCount(3);
        expect($result['users'][0])->toBe(['id' => '1', 'name' => 'John']);
        expect($result['users'][1])->toBe(['id' => '2', 'name' => null]);
        expect($result['users'][2])->toBe(['id' => '3', 'name' => 'Jane']);
    });

    it('handles real-world example from bug report', function(): void {
        $source = [
            'data' => [
                [
                    'id_crm' => '8402210073734',
                    'personalnr' => '108',
                    'firstname' => 'Olivier',
                    'lastname' => 'Repo',
                ],
                [
                    'id_crm' => '8402210205300',
                    'personalnr' => '118',
                    'firstname' => null,
                    'lastname' => 'Test',
                ],
                [
                    'id_crm' => '8402210209180',
                    'personalnr' => '120',
                    'firstname' => 'Steffen',
                    'lastname' => 'Senbert',
                ],
            ],
        ];

        $template = [
            'users.*' => [
                'externalUserId' => '{{ data.*.id_crm }}',
                'employeeNumber' => '{{ data.*.personalnr }}',
                'firstname' => '{{ data.*.firstname }}',
                'lastname' => '{{ data.*.lastname }}',
            ],
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->map()
            ->toArray();

        expect($result['users'])->toHaveCount(3);

        // User 0: Olivier Repo
        expect($result['users'][0]['employeeNumber'])->toBe('108');
        expect($result['users'][0]['firstname'])->toBe('Olivier');
        expect($result['users'][0]['lastname'])->toBe('Repo');

        // User 1: null Test (NOT Steffen Test!)
        expect($result['users'][1]['employeeNumber'])->toBe('118');
        expect($result['users'][1]['firstname'])->toBeNull();
        expect($result['users'][1]['lastname'])->toBe('Test');

        // User 2: Steffen Senbert
        expect($result['users'][2]['employeeNumber'])->toBe('120');
        expect($result['users'][2]['firstname'])->toBe('Steffen');
        expect($result['users'][2]['lastname'])->toBe('Senbert');
    });
});
