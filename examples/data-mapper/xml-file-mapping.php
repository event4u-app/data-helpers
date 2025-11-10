<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use event4u\DataHelpers\DataMapper;

echo "================================================================================\n";
echo "DataMapper - XML File Mapping Examples\n";
echo "================================================================================\n\n";

// Example 1: Loading XML File with Root Element
echo "Example 1: Loading XML File with Root Element\n";
echo "----------------------------------------------\n";

// Create a temporary XML file for demonstration
$xmlContent = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<company>
    <name>TechCorp Solutions</name>
    <email>info@techcorp.example</email>
    <founded_year>2015</founded_year>
    <departments>
        <department>
            <name>Engineering</name>
            <code>ENG</code>
            <budget>500000</budget>
        </department>
        <department>
            <name>Marketing</name>
            <code>MKT</code>
            <budget>300000</budget>
        </department>
    </departments>
</company>
XML;

$xmlFile = sys_get_temp_dir() . '/company_example.xml';
file_put_contents($xmlFile, $xmlContent);

// ⚠️ Important: When loading XML files, the root element name is preserved!
// You must include the root element in your mapping paths.

$mapping = [
    'company_name' => '{{ company.name }}',           // ✅ Correct: includes root element
    'company_email' => '{{ company.email }}',         // ✅ Correct: includes root element
    'founded' => '{{ company.founded_year }}',        // ✅ Correct: includes root element
    'departments' => [
        '*' => [
            'name' => '{{ company.departments.department.*.name }}',   // ✅ Correct
            'code' => '{{ company.departments.department.*.code }}',   // ✅ Correct
            'budget' => '{{ company.departments.department.*.budget }}', // ✅ Correct
        ],
    ],
];

$result = DataMapper::sourceFile($xmlFile)
    ->template($mapping)
    ->map()
    ->getTarget();

echo "Mapped Result:\n";
print_r($result);
echo "\n";

// Example 2: Common Mistake - Forgetting Root Element
echo "Example 2: Common Mistake - Forgetting Root Element\n";
echo "----------------------------------------------------\n";

$wrongMapping = [
    'company_name' => '{{ name }}',  // ❌ Wrong: missing root element 'company.'
    'company_email' => '{{ email }}', // ❌ Wrong: missing root element 'company.'
];

$wrongResult = DataMapper::sourceFile($xmlFile)
    ->template($wrongMapping)
    ->map()
    ->getTarget();

echo "Result with wrong mapping (values will be null):\n";
print_r($wrongResult);
echo "\n";

// Example 3: Different XML Root Elements
echo "Example 3: Different XML Root Elements\n";
echo "---------------------------------------\n";

// Create XML with different root element
$vitaCostXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<VitaCost>
    <ConstructionSite>
        <nr_lv>12345</nr_lv>
        <name>Building Project Alpha</name>
        <address>
            <street>Main Street 123</street>
            <city>Berlin</city>
        </address>
    </ConstructionSite>
</VitaCost>
XML;

$vitaCostFile = sys_get_temp_dir() . '/vitacost_example.xml';
file_put_contents($vitaCostFile, $vitaCostXml);

// Note: Root element is 'VitaCost', so all paths must start with 'VitaCost.'
$vitaCostMapping = [
    'number' => '{{ VitaCost.ConstructionSite.nr_lv }}',        // ✅ Starts with VitaCost
    'project_name' => '{{ VitaCost.ConstructionSite.name }}',   // ✅ Starts with VitaCost
    'street' => '{{ VitaCost.ConstructionSite.address.street }}', // ✅ Starts with VitaCost
    'city' => '{{ VitaCost.ConstructionSite.address.city }}',   // ✅ Starts with VitaCost
];

$vitaCostResult = DataMapper::sourceFile($vitaCostFile)
    ->template($vitaCostMapping)
    ->map()
    ->getTarget();

echo "VitaCost XML Result:\n";
print_r($vitaCostResult);
echo "\n";

// Example 4: Nested XML with Arrays
echo "Example 4: Nested XML with Arrays\n";
echo "----------------------------------\n";

$datafieldsXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Datafields>
    <project>
        <name>Project Alpha</name>
        <budget>1000000</budget>
    </project>
    <contact_persons>
        <contact_person>
            <salutation>Mr.</salutation>
            <surname>Smith</surname>
            <email>smith@example.com</email>
        </contact_person>
        <contact_person>
            <salutation>Ms.</salutation>
            <surname>Johnson</surname>
            <email>johnson@example.com</email>
        </contact_person>
    </contact_persons>
</Datafields>
XML;

$datafieldsFile = sys_get_temp_dir() . '/datafields_example.xml';
file_put_contents($datafieldsFile, $datafieldsXml);

// Root element is 'Datafields'
$datafieldsMapping = [
    'project_name' => '{{ Datafields.project.name }}',
    'project_budget' => '{{ Datafields.project.budget }}',
    'contacts' => [
        '*' => [
            'salutation' => '{{ Datafields.contact_persons.contact_person.*.salutation }}',
            'surname' => '{{ Datafields.contact_persons.contact_person.*.surname }}',
            'email' => '{{ Datafields.contact_persons.contact_person.*.email }}',
        ],
    ],
];

$datafieldsResult = DataMapper::sourceFile($datafieldsFile)
    ->template($datafieldsMapping)
    ->map()
    ->getTarget();

echo "Datafields XML Result:\n";
print_r($datafieldsResult);
echo "\n";

// Clean up temporary files
unlink($xmlFile);
unlink($vitaCostFile);
unlink($datafieldsFile);

echo "================================================================================\n";
echo "💡 Key Takeaways:\n";
echo "================================================================================\n";
echo "1. XML root element names are ALWAYS preserved when loading XML files\n";
echo "2. All mapping paths MUST start with the root element name\n";
echo "3. Example: For <company>...</company>, use '{{ company.field }}'\n";
echo "4. Example: For <VitaCost>...</VitaCost>, use '{{ VitaCost.field }}'\n";
echo "5. Forgetting the root element will result in null values\n";
echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";

