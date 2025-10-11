<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use Tests\utils\XMLs\Enums\PositionType;
use Tests\utils\XMLs\Enums\ProjectStatus;

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
        return array_map(fn($item): mixed => normalizeForSnapshot($item), $data);
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
        DataMapper::reset();
    });
    afterEach(function(): void {
        DataMapper::reset();
    });

    $snapshotDir = __DIR__ . '/snapshots';

    describe('Version 1 (DataFields)', function() use ($snapshotDir): void {
        it('maps complete project with all relations from version1 XML', function() use ($snapshotDir): void {
            // Load XML file
            $xmlFile = __DIR__ . '/../../utils/XMLs/version1.xml';

            // Complete mapping in one call
            $mapping = [
                'project' => [
                    'number' => '{{ number }}',
                    'title' => '{{ title }}',
                    'cost_center' => '{{ cost_center }}',
                    'address' => '{{ address }}',
                    'total_value' => '{{ order_value ?? 0.0 }}',
                    'calculated_hours' => '{{ calculated_time ?? 0.0 }}',
                    'status_raw' => '{{ status ?? 2 }}',
                ],
                'customer' => [
                    'name1' => '{{ client.name }}',
                    'street' => '{{ client.street }}',
                    'zipcode' => '{{ client.zipcode }}',
                    'city' => '{{ client.city }}',
                ],
                'contact_persons' => [
                    'salutation' => '{{ contact_persons.contact_person.salutation }}',
                    'surname' => '{{ contact_persons.contact_person.surname }}',
                    'email' => '{{ contact_persons.contact_person.email }}',
                    'phone' => '{{ contact_persons.contact_person.phone }}',
                ],
                'positions' => [
                    '*' => [
                        'external_id' => '{{ positions.position.*.external_id }}',
                        'number' => '{{ positions.position.*.pos_number }}',
                        'parent_id' => '{{ positions.position.*.parent_id }}',
                        'short_text' => '{{ positions.position.*.short_text }}',
                        'long_text' => '{{ positions.position.*.long_text | trim }}',
                        'quantity' => '{{ positions.position.*.amount ?? 0.0 }}',
                        'estimated_amount' => '{{ positions.position.*.estimated_amount ?? 0.0 }}',
                        'measured_amount' => '{{ positions.position.*.measured_amount ?? 0.0 }}',
                        'unit' => '{{ positions.position.*.unit }}',
                        'unit_price' => '{{ positions.position.*.unit_price ?? 0.0 }}',
                        'minutes' => '{{ positions.position.*.minutes ?? 0.0 }}',
                        'type_raw' => '{{ positions.position.*.type ?? Standard }}',
                    ],
                ],
            ];

            $completeData = DataMapper::mapFromFile($xmlFile, [], $mapping, false);
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
        it('maps complete project with all relations from version2 XML', function() use ($snapshotDir): void {
            // Load XML file
            $xmlFile = __DIR__ . '/../../utils/XMLs/version2.xml';

            // Complete mapping in one call
            $mapping = [
                'project' => [
                    'number' => '{{ ConstructionSite.nr_lv }}',
                    'title' => '{{ ConstructionSite.description }}',
                    'client_id' => '{{ ConstructionSite.client_id }}',
                    'total_value' => '{{ ConstructionSite.lv_sum ?? 0.0 }}',
                    'calculated_hours' => '{{ ConstructionSite.construction_hours ?? 0.0 }}',
                    'actual_hours' => '{{ ConstructionSite.actual_hours ?? 0.0 }}',
                    'revenue' => '{{ ConstructionSite.revenue ?? 0.0 }}',
                    'costs' => '{{ ConstructionSite.costs ?? 0.0 }}',
                    'contribution_margin' => '{{ ConstructionSite.contribution_margin ?? 0.0 }}',
                    'construction_start' => '{{ ConstructionSite.construction_start }}',
                    'construction_end' => '{{ ConstructionSite.construction_end }}',
                    'status_raw' => '{{ ConstructionSite.lv_Status ?? BB }}',
                ],
                'customer' => [
                    'description' => '{{ ConstructionSite.customer_description }}',
                    'name1' => '{{ ConstructionSite.customer_name }}',
                    'name2' => '{{ ConstructionSite.customer_name2 }}',
                    'name3' => '{{ ConstructionSite.customer_name3 }}',
                    'street' => '{{ ConstructionSite.customer_street }}',
                    'zipcode' => '{{ ConstructionSite.customer_zipcode }}',
                    'city' => '{{ ConstructionSite.customer_city }}',
                ],
                'address' => [
                    'street' => '{{ ConstructionSite.construction_street }}',
                    'zipcode' => '{{ ConstructionSite.construction_zipcode }}',
                    'city' => '{{ ConstructionSite.construction_city }}',
                ],
                'architect' => [
                    'external_id' => '{{ ConstructionSite.architect_id }}',
                    'description' => '{{ ConstructionSite.architect_description }}',
                    'name1' => '{{ ConstructionSite.architect_name }}',
                    'name2' => '{{ ConstructionSite.architect_name2 }}',
                    'name3' => '{{ ConstructionSite.architect_name3 }}',
                    'street' => '{{ ConstructionSite.architect_street }}',
                    'zipcode' => '{{ ConstructionSite.architect_zipcode }}',
                    'city' => '{{ ConstructionSite.architect_city }}',
                ],
                'positions' => [
                    '*' => [
                        'number' => '{{ ConstructionSite.Positions.Position.*.pos_number }}',
                        'parent_id' => '{{ ConstructionSite.Positions.Position.*.parent_id }}',
                        'type_description' => '{{ ConstructionSite.Positions.Position.*.type_description }}',
                        'short_text' => '{{ ConstructionSite.Positions.Position.*.short_text }}',
                        'long_text' => '{{ ConstructionSite.Positions.Position.*.long_text | trim }}',
                        'quantity' => '{{ ConstructionSite.Positions.Position.*.quantity ?? 0.0 }}',
                        'unit' => '{{ ConstructionSite.Positions.Position.*.unit }}',
                        'unit_price' => '{{ ConstructionSite.Positions.Position.*.unit_price ?? 0.0 }}',
                        'total_amount' => '{{ ConstructionSite.Positions.Position.*.total_amount ?? 0.0 }}',
                        'minutes' => '{{ ConstructionSite.Positions.Position.*.minutes ?? 0.0 }}',
                        'type_raw' => '{{ ConstructionSite.Positions.Position.*.type ?? N }}',
                    ],
                ],
            ];

            $completeData = DataMapper::mapFromFile($xmlFile, [], $mapping);
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
        it('maps complete project with all relations from version3 XML', function() use ($snapshotDir): void {
            // Load XML file
            $xmlFile = __DIR__ . '/../../utils/XMLs/version3.xml';

            // Complete mapping in one call
            $mapping = [
                'project' => [
                    'number' => '{{ lvdata.lv_number | decode_html }}',
                    'title' => '{{ lvdata.lv_description | decode_html | trim:" -" }}',
                    'description' => '{{ lvdata.project_description | decode_html | trim:" -" }}',
                    'total_value' => '{{ lvdata.lv_sum ?? 0.0 }}',
                    'calculated_hours' => '{{ lvdata.lv_hours ?? 0.0 }}',
                    'actual_hours' => '{{ lvdata.lv_actual_hours ?? 0.0 }}',
                    'revenue' => '{{ lvdata.lv_revenue ?? 0.0 }}',
                    'costs' => '{{ lvdata.lv_costs ?? 0.0 }}',
                    'contribution_margin' => '{{ lvdata.lv_margin ?? 0.0 }}',
                    'result' => '{{ lvdata.lv_result ?? 0.0 }}',
                    'status_raw' => '{{ lvdata.lv_status ?? Order }}',
                ],
                'customer' => [
                    'name1' => '{{ lvdata.customer_name | default:"" | decode_html }}',
                    'name2' => '{{ lvdata.customer_name2 | default:"" | decode_html }}',
                    'name3' => '{{ lvdata.customer_name3 | default:"" | decode_html | empty_to_null }}',
                    'street' => '{{ lvdata.customer_address | default:"" | decode_html }}',
                    'zipcode' => '{{ lvdata.customer_zipcode }}',
                    'city' => '{{ lvdata.customer_city | default:"" | decode_html }}',
                ],
                'positions' => [
                    '*' => [
                        'number' => '{{ lvdata.posdata.*.pos_number }}',
                        'parent_id' => '{{ lvdata.posdata.*.parent_id }}',
                        'short_text' => '{{ lvdata.posdata.*.pos_text | decode_html }}',
                        'long_text' => '{{ lvdata.posdata.*.pos_long_text | decode_html | trim }}',
                        'quantity' => '{{ lvdata.posdata.*.pos_quantity ?? 0.0 }}',
                        'unit' => '{{ lvdata.posdata.*.pos_unit | decode_html }}',
                        'unit_price' => '{{ lvdata.posdata.*.pos_unit_price ?? 0.0 }}',
                        'total_amount' => '{{ lvdata.posdata.*.pos_sum ?? 0.0 }}',
                        'minutes' => '{{ lvdata.posdata.*.pos_minutes ?? 0.0 }}',
                        'factor' => '{{ lvdata.posdata.*.pos_factor ?? 1.0 }}',
                        'address' => '{{ lvdata.posdata.*.pos_address | decode_html }}',
                        'zipcode' => '{{ lvdata.posdata.*.pos_zipcode }}',
                        'city' => '{{ lvdata.posdata.*.pos_city | decode_html }}',
                        'type_raw' => '{{ lvdata.posdata.*.pos_type ?? N }}',
                    ],
                ],
            ];

            $completeData = DataMapper::mapFromFile($xmlFile, [], $mapping);
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

