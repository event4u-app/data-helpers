<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\MapperExceptions;
use Tests\Utils\XMLs\Enums\PositionType;
use Tests\Utils\XMLs\Enums\ProjectStatus;

// Helper function for test setup
// Needed because Pest 2.x doesn't inherit beforeEach from outer describe blocks
function setupXmlToModelMapping(): void
{
    MapperExceptions::reset();
}

/**
 * Helper function to normalize data for snapshot comparison
 * Converts Enums to their string values
 */
function normalizeForSnapshot(mixed $data): mixed
{
    if ($data instanceof BackedEnum) {
        return $data->value;
    }

    if (is_array($data)) {
        return array_map(normalizeForSnapshot(...), $data);
    }

    return $data;
}

/**
 * Helper function to save or compare snapshot
 * @param array<string, mixed> $data
 */
function snapshotTest(string $snapshotDir, string $name, array $data): void
{
    $snapshotFile = $snapshotDir . '/' . $name . '.json';

    // Normalize data (convert Enums to strings)
    $normalizedData = normalizeForSnapshot($data);

    if (!file_exists($snapshotFile)) {
        // Create snapshot
        file_put_contents($snapshotFile, json_encode($normalizedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        expect(true)->toBeTrue(); // Pass test when creating snapshot
    } else {
        // Compare with snapshot
        $snapshotContent = file_get_contents($snapshotFile);
        if (false === $snapshotContent) {
            throw new RuntimeException('Failed to read snapshot file: ' . $snapshotFile);
        }
        /** @var array<string, mixed> $snapshot */
        $snapshot = json_decode($snapshotContent, true);
        expect($normalizedData)->toEqual($snapshot);
    }
}

describe('XML to Model Mapping', function(): void {
    beforeEach(function(): void {
        MapperExceptions::reset();
    });
    afterEach(function(): void {
        MapperExceptions::reset();
    });

    $snapshotDir = __DIR__ . '/snapshots';

    describe('Version 1 (DataFields)', function() use ($snapshotDir): void {
        beforeEach(function(): void {
            setupXmlToModelMapping();
        });

        it('maps complete project with all relations from version1 XML', function() use ($snapshotDir): void {
            // Load XML file
            $xmlFile = __DIR__ . '/../../Utils/XMLs/version1.xml';

            // Complete mapping in one call
            $mapping = [
                'project' => [
                    'number' => '{{ Datafields.number }}',
                    'title' => '{{ Datafields.title }}',
                    'cost_center' => '{{ Datafields.cost_center }}',
                    'address' => '{{ Datafields.address }}',
                    'total_value' => '{{ Datafields.order_value ?? 0.0 }}',
                    'calculated_hours' => '{{ Datafields.calculated_time ?? 0.0 }}',
                    'status_raw' => '{{ Datafields.status ?? 2 }}',
                ],
                'customer' => [
                    'name1' => '{{ Datafields.client.name }}',
                    'street' => '{{ Datafields.client.street }}',
                    'zipcode' => '{{ Datafields.client.zipcode }}',
                    'city' => '{{ Datafields.client.city }}',
                ],
                'contact_persons' => [
                    'salutation' => '{{ Datafields.contact_persons.contact_person.salutation }}',
                    'surname' => '{{ Datafields.contact_persons.contact_person.surname }}',
                    'email' => '{{ Datafields.contact_persons.contact_person.email }}',
                    'phone' => '{{ Datafields.contact_persons.contact_person.phone }}',
                ],
                'positions' => [
                    '*' => [
                        'external_id' => '{{ Datafields.positions.position.*.external_id }}',
                        'number' => '{{ Datafields.positions.position.*.pos_number }}',
                        'parent_id' => '{{ Datafields.positions.position.*.parent_id }}',
                        'short_text' => '{{ Datafields.positions.position.*.short_text }}',
                        'long_text' => '{{ Datafields.positions.position.*.long_text | trim }}',
                        'quantity' => '{{ Datafields.positions.position.*.amount ?? 0.0 }}',
                        'estimated_amount' => '{{ Datafields.positions.position.*.estimated_amount ?? 0.0 }}',
                        'measured_amount' => '{{ Datafields.positions.position.*.measured_amount ?? 0.0 }}',
                        'unit' => '{{ Datafields.positions.position.*.unit }}',
                        'unit_price' => '{{ Datafields.positions.position.*.unit_price ?? 0.0 }}',
                        'minutes' => '{{ Datafields.positions.position.*.minutes ?? 0.0 }}',
                        'type_raw' => '{{ Datafields.positions.position.*.type ?? Standard }}',
                    ],
                ],
            ];

            $completeData = DataMapper::sourceFile($xmlFile)->target([])->template($mapping)->skipNull(
                false
            )->map()->getTarget();
            /** @var array<string, mixed> $completeData */

            // Normalize contact_persons (XML single element issue)
            if (isset($completeData['contact_persons']) && !isset($completeData['contact_persons'][0])) {
                $completeData['contact_persons'] = [$completeData['contact_persons']];
            }

            // Convert Enums
            /** @var array<string, mixed> $project */
            $project = $completeData['project'] ?? [];
            /** @var string $statusRaw */
            $statusRaw = $project['status_raw'] ?? '2';
            $project['status'] = ProjectStatus::fromVersion1($statusRaw) ?? ProjectStatus::ORDER;
            unset($project['status_raw']);
            $completeData['project'] = $project;

            /** @var array<int, array<string, mixed>> $positions */
            $positions = $completeData['positions'] ?? [];
            foreach ($positions as $key => $position) {
                /** @var int|string $typeRaw */
                $typeRaw = $position['type_raw'] ?? 'Standard';
                $position['type'] = PositionType::tryFrom($typeRaw) ?? PositionType::STANDARD;
                unset($position['type_raw']);
                $positions[$key] = $position;
            }
            $completeData['positions'] = $positions;

            // Snapshot test
            snapshotTest($snapshotDir, 'version1_complete', $completeData);

            // Validations
            expect($project['number'])->toBe('98765432');
            expect($project['status'])->toBe(ProjectStatus::ORDER_CALCULATION);
            /** @var array<string, mixed> $customer */
            $customer = $completeData['customer'] ?? [];
            expect($customer['name1'])->toBeString();
            expect($completeData['contact_persons'])->toBeArray();
            /** @var array<mixed> $contactPersons */
            $contactPersons = $completeData['contact_persons'];
            expect(count($contactPersons))->toBeGreaterThan(0);
            expect($completeData['positions'])->toBeArray();
            expect(count($completeData['positions']))->toBe(4);
        });
    });

    describe('Version 2 (VitaCost/ConstructionSite)', function() use ($snapshotDir): void {
        beforeEach(function(): void {
            setupXmlToModelMapping();
        });

        it('maps complete project with all relations from version2 XML', function() use ($snapshotDir): void {
            // Load XML file
            $xmlFile = __DIR__ . '/../../Utils/XMLs/version2.xml';

            // Complete mapping in one call
            $mapping = [
                'project' => [
                    'number' => '{{ VitaCost.ConstructionSite.nr_lv }}',
                    'title' => '{{ VitaCost.ConstructionSite.description }}',
                    'client_id' => '{{ VitaCost.ConstructionSite.client_id }}',
                    'total_value' => '{{ VitaCost.ConstructionSite.lv_sum ?? 0.0 }}',
                    'calculated_hours' => '{{ VitaCost.ConstructionSite.construction_hours ?? 0.0 }}',
                    'actual_hours' => '{{ VitaCost.ConstructionSite.actual_hours ?? 0.0 }}',
                    'revenue' => '{{ VitaCost.ConstructionSite.revenue ?? 0.0 }}',
                    'costs' => '{{ VitaCost.ConstructionSite.costs ?? 0.0 }}',
                    'contribution_margin' => '{{ VitaCost.ConstructionSite.contribution_margin ?? 0.0 }}',
                    'construction_start' => '{{ VitaCost.ConstructionSite.construction_start }}',
                    'construction_end' => '{{ VitaCost.ConstructionSite.construction_end }}',
                    'status_raw' => '{{ VitaCost.ConstructionSite.lv_Status ?? BB }}',
                ],
                'customer' => [
                    'description' => '{{ VitaCost.ConstructionSite.customer_description }}',
                    'name1' => '{{ VitaCost.ConstructionSite.customer_name }}',
                    'name2' => '{{ VitaCost.ConstructionSite.customer_name2 }}',
                    'name3' => '{{ VitaCost.ConstructionSite.customer_name3 }}',
                    'street' => '{{ VitaCost.ConstructionSite.customer_street }}',
                    'zipcode' => '{{ VitaCost.ConstructionSite.customer_zipcode }}',
                    'city' => '{{ VitaCost.ConstructionSite.customer_city }}',
                ],
                'address' => [
                    'street' => '{{ VitaCost.ConstructionSite.construction_street }}',
                    'zipcode' => '{{ VitaCost.ConstructionSite.construction_zipcode }}',
                    'city' => '{{ VitaCost.ConstructionSite.construction_city }}',
                ],
                'architect' => [
                    'external_id' => '{{ VitaCost.ConstructionSite.architect_id }}',
                    'description' => '{{ VitaCost.ConstructionSite.architect_description }}',
                    'name1' => '{{ VitaCost.ConstructionSite.architect_name }}',
                    'name2' => '{{ VitaCost.ConstructionSite.architect_name2 }}',
                    'name3' => '{{ VitaCost.ConstructionSite.architect_name3 }}',
                    'street' => '{{ VitaCost.ConstructionSite.architect_street }}',
                    'zipcode' => '{{ VitaCost.ConstructionSite.architect_zipcode }}',
                    'city' => '{{ VitaCost.ConstructionSite.architect_city }}',
                ],
                'positions' => [
                    '*' => [
                        'number' => '{{ VitaCost.ConstructionSite.Positions.Position.*.pos_number }}',
                        'parent_id' => '{{ VitaCost.ConstructionSite.Positions.Position.*.parent_id }}',
                        'type_description' => '{{ VitaCost.ConstructionSite.Positions.Position.*.type_description }}',
                        'short_text' => '{{ VitaCost.ConstructionSite.Positions.Position.*.short_text }}',
                        'long_text' => '{{ VitaCost.ConstructionSite.Positions.Position.*.long_text | trim }}',
                        'quantity' => '{{ VitaCost.ConstructionSite.Positions.Position.*.quantity ?? 0.0 }}',
                        'unit' => '{{ VitaCost.ConstructionSite.Positions.Position.*.unit }}',
                        'unit_price' => '{{ VitaCost.ConstructionSite.Positions.Position.*.unit_price ?? 0.0 }}',
                        'total_amount' => '{{ VitaCost.ConstructionSite.Positions.Position.*.total_amount ?? 0.0 }}',
                        'minutes' => '{{ VitaCost.ConstructionSite.Positions.Position.*.minutes ?? 0.0 }}',
                        'type_raw' => '{{ VitaCost.ConstructionSite.Positions.Position.*.type ?? N }}',
                    ],
                ],
            ];

            $completeData = DataMapper::sourceFile($xmlFile)->target([])->template($mapping)->trimValues(
                true
            )->map()->getTarget();
            /** @var array<string, mixed> $completeData */

            // Convert Enums
            /** @var array<string, mixed> $project */
            $project = $completeData['project'] ?? [];
            /** @var string $statusRaw */
            $statusRaw = $project['status_raw'] ?? 'BB';
            $project['status'] = ProjectStatus::fromVersion2($statusRaw) ?? ProjectStatus::ORDER;
            unset($project['status_raw']);
            $completeData['project'] = $project;

            /** @var array<int, array<string, mixed>> $positions */
            $positions = $completeData['positions'] ?? [];
            foreach ($positions as $key => $position) {
                /** @var int|string $typeRaw */
                $typeRaw = $position['type_raw'] ?? 'N';
                $position['type'] = PositionType::tryFrom($typeRaw) ?? PositionType::NORMAL;
                unset($position['type_raw']);
                $positions[$key] = $position;
            }
            $completeData['positions'] = $positions;

            // Snapshot test
            snapshotTest($snapshotDir, 'version2_complete', $completeData);

            // Validations
            expect($project['number'])->toBe('2608');
            expect($project['status'])->toBe(ProjectStatus::ORDER);
            /** @var array<string, mixed> $customer */
            $customer = $completeData['customer'] ?? [];
            expect($customer['name1'])->toBe('City of Sample City');
            expect($customer['name2'])->toBe('Department of Green Spaces');
            expect($completeData['customer']['name3'])->toBe('Dept. 42 City Park');
            expect($completeData['address']['city'])->toBe('Sample City');
            expect($completeData['positions'])->toBeArray();
            expect(count($completeData['positions']))->toBe(5);
        });
    });

    describe('Version 3 (lv_nesting/lvdata)', function() use ($snapshotDir): void {
        beforeEach(function(): void {
            setupXmlToModelMapping();
        });

        it('maps complete project with all relations from version3 XML', function() use ($snapshotDir): void {
            // Load XML file
            $xmlFile = __DIR__ . '/../../Utils/XMLs/version3.xml';

            // Complete mapping in one call
            $mapping = [
                'project' => [
                    'number' => '{{ lv_nesting.lvdata.lv_number | decode_html }}',
                    'title' => '{{ lv_nesting.lvdata.lv_description | decode_html | trim:" -" }}',
                    'description' => '{{ lv_nesting.lvdata.project_description | decode_html | trim:" -" }}',
                    'total_value' => '{{ lv_nesting.lvdata.lv_sum ?? 0.0 }}',
                    'calculated_hours' => '{{ lv_nesting.lvdata.lv_hours ?? 0.0 }}',
                    'actual_hours' => '{{ lv_nesting.lvdata.lv_actual_hours ?? 0.0 }}',
                    'revenue' => '{{ lv_nesting.lvdata.lv_revenue ?? 0.0 }}',
                    'costs' => '{{ lv_nesting.lvdata.lv_costs ?? 0.0 }}',
                    'contribution_margin' => '{{ lv_nesting.lvdata.lv_margin ?? 0.0 }}',
                    'result' => '{{ lv_nesting.lvdata.lv_result ?? 0.0 }}',
                    'status_raw' => '{{ lv_nesting.lvdata.lv_status ?? Order }}',
                ],
                'customer' => [
                    'name1' => '{{ lv_nesting.lvdata.customer_name | default:"" | decode_html }}',
                    'name2' => '{{ lv_nesting.lvdata.customer_name2 | default:"" | decode_html }}',
                    'name3' => '{{ lv_nesting.lvdata.customer_name3 | default:"" | decode_html | empty_to_null }}',
                    'street' => '{{ lv_nesting.lvdata.customer_address | default:"" | decode_html }}',
                    'zipcode' => '{{ lv_nesting.lvdata.customer_zipcode }}',
                    'city' => '{{ lv_nesting.lvdata.customer_city | default:"" | decode_html }}',
                ],
                'positions' => [
                    '*' => [
                        'number' => '{{ lv_nesting.lvdata.posdata.*.pos_number }}',
                        'parent_id' => '{{ lv_nesting.lvdata.posdata.*.parent_id }}',
                        'short_text' => '{{ lv_nesting.lvdata.posdata.*.pos_text | decode_html }}',
                        'long_text' => '{{ lv_nesting.lvdata.posdata.*.pos_long_text | decode_html | trim }}',
                        'quantity' => '{{ lv_nesting.lvdata.posdata.*.pos_quantity ?? 0.0 }}',
                        'unit' => '{{ lv_nesting.lvdata.posdata.*.pos_unit | decode_html }}',
                        'unit_price' => '{{ lv_nesting.lvdata.posdata.*.pos_unit_price ?? 0.0 }}',
                        'total_amount' => '{{ lv_nesting.lvdata.posdata.*.pos_sum ?? 0.0 }}',
                        'minutes' => '{{ lv_nesting.lvdata.posdata.*.pos_minutes ?? 0.0 }}',
                        'factor' => '{{ lv_nesting.lvdata.posdata.*.pos_factor ?? 1.0 }}',
                        'address' => '{{ lv_nesting.lvdata.posdata.*.pos_address | decode_html }}',
                        'zipcode' => '{{ lv_nesting.lvdata.posdata.*.pos_zipcode }}',
                        'city' => '{{ lv_nesting.lvdata.posdata.*.pos_city | decode_html }}',
                        'type_raw' => '{{ lv_nesting.lvdata.posdata.*.pos_type ?? N }}',
                    ],
                ],
            ];

            $completeData = DataMapper::sourceFile($xmlFile)->target([])->template($mapping)->map()->getTarget();
            /** @var array<string, mixed> $completeData */

            // Convert Enums
            /** @var array<string, mixed> $project */
            $project = $completeData['project'] ?? [];
            /** @var string $statusRaw */
            $statusRaw = $project['status_raw'] ?? 'Order';
            $project['status'] = ProjectStatus::fromVersion3($statusRaw) ?? ProjectStatus::ORDER;
            unset($project['status_raw']);
            $completeData['project'] = $project;

            /** @var array<int, array<string, mixed>> $positions */
            $positions = $completeData['positions'] ?? [];
            foreach ($positions as $key => $position) {
                /** @var int|string $typeRaw */
                $typeRaw = $position['type_raw'] ?? 'N';
                $position['type'] = PositionType::tryFrom($typeRaw) ?? PositionType::NORMAL;
                unset($position['type_raw']);
                $positions[$key] = $position;
            }
            $completeData['positions'] = $positions;

            // Snapshot test
            snapshotTest($snapshotDir, 'version3_complete', $completeData);

            // Validations
            expect($project['number'])->toBeString();
            expect($project['status'])->toBe(ProjectStatus::ORDER);
            /** @var array<string, mixed> $customer */
            $customer = $completeData['customer'] ?? [];
            expect($customer['name1'])->toBeString();
            expect($customer['name2'])->toBeString();
            expect($completeData['positions'])->toBeArray();
            expect(count($completeData['positions']))->toBe(5);
        });
    });
});
