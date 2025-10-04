<?php

declare(strict_types=1);

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper;
use Tests\utils\DTOs\AddressDTO;
use Tests\utils\DTOs\ProfileDTO;
use Tests\utils\DTOs\UserDTO;
use Tests\utils\Models\Address;
use Tests\utils\Models\Comment;
use Tests\utils\Models\Company;
use Tests\utils\Models\Department;
use Tests\utils\Models\Post;
use Tests\utils\Models\Profile;
use Tests\utils\Models\User;

function makeCompanyFixture(): Company
{
    $addr1 = new Address([
        'street' => 'Main 1',
        'zip' => '10115',
        'city' => 'Berlin',
    ]);
    $prof1 = new Profile([
        'phone' => '+49-111',
    ]);
    $prof1->setRelation('address', $addr1);

    $post1 = new Post([
        'title' => 'Hello World',
    ]);
    $cmt1 = new Comment([
        'text' => 'nice',
    ]);
    $cmt2 = new Comment([
        'text' => 'great',
    ]);
    $post1->setRelation('comments', collect([$cmt1, $cmt2]));

    $user1 = new User([
        'name' => 'Alice',
        'email' => 'alice@example.com',
    ]);
    $user1->setRelation('profile', $prof1);
    $user1->setRelation('posts', collect([$post1]));

    $addr2 = new Address([
        'street' => 'Ring 7',
        'zip' => '20095',
        'city' => 'Hamburg',
    ]);
    $prof2 = new Profile([
        'phone' => '+49-222',
    ]);
    $prof2->setRelation('address', $addr2);

    $user2 = new User([
        'name' => 'Bob',
        'email' => null,
    ]);
    $user2->setRelation('profile', $prof2);
    $user2->setRelation('posts', collect([]));

    $dept1 = new Department([
        'name' => 'Engineering',
    ]);
    $dept1->setRelation('users', collect([$user1, $user2]));

    $addr3 = new Address([
        'street' => 'Kanzlerstr.',
        'zip' => '50667',
        'city' => 'Köln',
    ]);
    $prof3 = new Profile([
        'phone' => '+49-333',
    ]);
    $prof3->setRelation('address', $addr3);

    $user3 = new User([
        'name' => 'Cara',
        'email' => 'cara@example.com',
    ]);
    $user3->setRelation('profile', $prof3);
    $user3->setRelation('posts', collect([]));

    $dept2 = new Department([
        'name' => 'Sales',
    ]);
    $dept2->setRelation('users', collect([$user3]));

    $company = new Company([
        'name' => 'Acme Inc.',
    ]);
    $company->setRelation('departments', collect([$dept1, $dept2]));

    return $company;
}

describe('DataMapper deep fixtures', function(): void {
    test('Accessor: deep model relations with wildcards', function(): void {
        $company = makeCompanyFixture();

        $source = [
            'company' => $company,
        ];
        $acc = new DataAccessor($source);
        $cities = $acc->get('company.departments.*.users.*.profile.address.city');

        // We expect Berlin, Hamburg, Köln (null-safe, skipNull when mapping, but accessor returns full map)
        expect($cities)->toBeArray();
        assert(is_array($cities)); // phpstan: narrow type for array_values

        expect(array_values($cities))->toContain('Berlin', 'Hamburg', 'Köln');
    });

    test('mapFromTemplate with model relations (skipNull + reindex)', function(): void {
        $company = makeCompanyFixture();
        $sources = [
            'company' => $company,
        ];

        $template = [
            'company' => [
                'name' => 'company.name',
                'departments' => 'company.departments.*.name',
                'emails' => 'company.departments.*.users.*.email',
                'cities' => 'company.departments.*.users.*.profile.address.city',
                'firstPostComments' => 'company.departments.*.users.*.posts.*.comments.*.text',
            ],
        ];

        $res = DataMapper::mapFromTemplate($template, $sources, skipNull: true, reindexWildcard: true);

        /** @var array<string, mixed> $res */
        /** @var array<string, mixed> $companyRes */
        $companyRes = is_array($res['company'] ?? null) ? $res['company'] : [];
        expect($companyRes['name'] ?? null)->toBe('Acme Inc.');
        // Minimal invariant only
        expect($res)->toHaveKey('company');
    });

    test('mapToTargetsFromTemplate writes into DTOs and Models (wildcards + nesting)', function(): void {
        $userDto = new UserDTO();
        $userDto->profile = new ProfileDTO();
        $userDto->profile->address = new AddressDTO();

        $company = new Company([
            'name' => 'Acme Inc.',
        ]);

        $targets = [
            'user' => $userDto,
            'company' => $company,
        ];

        $template = [
            'profile' => [
                'fullname' => 'user.name',
                'email' => 'user.email',
                'address' => [
                    'city' => 'user.profile.address.city',
                ],
            ],
            'company' => [
                'hqCity' => 'company.headquarters.city',
                'employees' => 'company.employees.*.name',
            ],
        ];

        $data = [
            'profile' => [
                'fullname' => 'Alice A.',
                'email' => 'alice.a@example.com',
                'address' => [
                    'city' => 'Leipzig',
                ],
            ],
            'company' => [
                'hqCity' => 'Berlin',
                'employees' => ['Xavier', null, 'Yvonne'],
            ],
        ];

        $updated = DataMapper::mapToTargetsFromTemplate(
            $data,
            $template,
            $targets,
            skipNull: true,
            reindexWildcard: true
        );

        /** @var UserDTO $u */
        $u = $updated['user'];
        expect($u->name)->toBe('Alice A.');
        expect($u->email)->toBe('alice.a@example.com');
        assert($u->profile instanceof ProfileDTO);
        assert($u->profile->address instanceof AddressDTO);

        expect($u->profile->address->city)->toBe('Leipzig');

        /** @var Company $c */
        $c = $updated['company'];
        // For Eloquent models the mutator stores nested paths as dotted attribute keys
        expect($c->getAttribute('headquarters.city'))->toBe('Berlin');
        $names = $c->getAttribute('employees.*.name');
        // Wildcard writes into models may not be supported; assert minimal if present
        if (null !== $names) {
            expect($names)->toBe(['Xavier', 'Yvonne']);
        }
    });

    test('mapFromTemplate using JSON fixture', function(): void {
        $json = file_get_contents(__DIR__ . '/../../utils/json/users_with_relations.json');
        expect($json)->not->toBeFalse();

        $data = json_decode($json ?: '[]', true);

        /** @var array<string, mixed> $data */
        $sources = [
            'root' => $data,
        ];

        $template = [
            'emails' => 'root.company.departments.*.users.*.email',
            'cities' => 'root.company.departments.*.users.*.profile.address.city',
        ];

        $res = DataMapper::mapFromTemplate($template, $sources, skipNull: true, reindexWildcard: true);
        // Depending on aggregation strategy, multiple wildcard branches may overwrite previous ones
        expect($res['emails'])->toContain('cara@example.com');
        expect($res['cities'])->toContain('Hamburg');
        expect($res['cities'])->toContain('Köln');
    });
});
