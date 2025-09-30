<?php

declare(strict_types=1);

use App\Helpers\DataMapper;

test('case-insensitive replaces work when enabled via map parameter', function(): void {
    $source = [
        'order' => [
            'status' => 'bezahlt',
        ],
    ];

    $result = DataMapper::map(null, [], [[
        'source' => $source,
        'target' => [],
        'mapping' => [
            'order.status' => 'dto.paymentStatus',
        ],
        'replaces' => [
            'order.status' => [
                'BEZAHLT' => 'PAID',
            ],
        ],
    ]], true, false, [], true, true);

    expect($result)->toBe([
        'dto' => [
            'paymentStatus' => 'PAID',
        ],
    ]);
});

test('trim-before-replace is applied globally (default true)', function(): void {
    $source = [
        'order' => [
            'status' => ' bezahlt ',
        ],
    ];

    $result = DataMapper::map(null, [], [[
        'source' => $source,
        'target' => [],
        'mapping' => [
            'order.status' => 'dto.paymentStatus',
        ],
        'replaces' => [
            'order.status' => [
                'bezahlt' => 'PAID',
            ],
        ],
    ]]);

    expect($result)->toBe([
        'dto' => [
            'paymentStatus' => 'PAID',
        ],
    ]);
});

describe('Replace', function(): void {
    test('structured associative mapping supports replaces keyed by source path', function(): void {
        $source = [
            'order' => [
                'status' => 'bezahlt',
                'state' => 'offen',
            ],
        ];

        $result = DataMapper::map(null, [], [[
            'source' => $source,
            'target' => [],
            'mapping' => [
                'order.status' => 'dto.paymentStatus',
                'order.state' => 'dto.orderState',
            ],
            'replaces' => [
                'order.status' => [
                    'bezahlt' => 'PAID',
                ],
                'order.state' => [
                    'offen' => 'OPEN',
                ],
            ],
        ]]);

        expect($result)->toBe([
            'dto' => [
                'paymentStatus' => 'PAID',
                'orderState' => 'OPEN',
            ],
        ]);
    });

    test('structured list-of-pairs supports replaces aligned by index', function(): void {
        $source = [
            'order' => [
                'status' => 'bezahlt',
                'state' => 'offen',
            ],
        ];

        $result = DataMapper::map(null, [], [[
            'source' => $source,
            'target' => [],
            'mapping' => [
                ['order.status', 'dto.paymentStatus'],
                ['order.state', 'dto.orderState'],
            ],
            'replaces' => [
                [
                    'bezahlt' => 'PAID',
                ],
                [
                    'offen' => 'OPEN',
                ],
            ],
        ]]);

        expect($result)->toBe([
            'dto' => [
                'paymentStatus' => 'PAID',
                'orderState' => 'OPEN',
            ],
        ]);
    });

    test('replaces apply to each wildcard element and respect skipNull/reindex', function(): void {
        $source = [
            'items' => [
                [
                    'state' => 'bezahlt',
                ],
                [
                    'state' => null,
                ],
                [
                    'state' => 'offen',
                ],
            ],
        ];

        $result = DataMapper::map(null, [], [[
            'source' => $source,
            'target' => [],
            'sourceMapping' => ['items.*.state'],
            'targetMapping' => ['result.*'],
            'skipNull' => true,
            'reindexWildcard' => true,
            'replaces' => [
                [
                    'bezahlt' => 'PAID',
                    'offen' => 'OPEN',
                ],
            ],
        ]]);

        expect($result)->toBe([
            'result' => ['PAID', 'OPEN'],
        ]);
    });
});
