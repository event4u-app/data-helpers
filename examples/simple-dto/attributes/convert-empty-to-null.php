<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;

echo "================================================================================\n";
echo "SimpleDto - ConvertEmptyToNull Attribute Examples\n";
echo "================================================================================\n\n";

// Example 1: Property-Level Conversion
echo "Example 1: Property-Level Conversion\n";
echo "-------------------------------------\n";

class ProfileDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        #[ConvertEmptyToNull]
        public readonly ?string $bio = null,
        #[ConvertEmptyToNull]
        public readonly ?array $tags = null,
    ) {}
}

$profile = ProfileDto::fromArray([
    'name' => 'John Doe',
    'bio' => '',      // Empty string
    'tags' => [],     // Empty array
]);

echo "Name: {$profile->name}\n";
echo "Bio: " . ($profile->bio === null ? 'null' : $profile->bio) . "\n";
echo "Tags: " . ($profile->tags === null ? 'null' : json_encode($profile->tags)) . "\n\n";

// Example 2: Class-Level Conversion
echo "Example 2: Class-Level Conversion\n";
echo "----------------------------------\n";

#[ConvertEmptyToNull]
class SettingsDto extends SimpleDto
{
    public function __construct(
        public readonly string $theme,
        public readonly ?string $language = null,
        public readonly ?array $preferences = null,
    ) {}
}

$settings = SettingsDto::fromArray([
    'theme' => 'dark',
    'language' => '',
    'preferences' => [],
]);

echo "Theme: {$settings->theme}\n";
echo "Language: " . ($settings->language === null ? 'null' : $settings->language) . "\n";
echo "Preferences: " . ($settings->preferences === null ? 'null' : json_encode($settings->preferences)) . "\n\n";

// Example 3: API Response with Empty Strings
echo "Example 3: API Response with Empty Strings\n";
echo "-------------------------------------------\n";

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        #[ConvertEmptyToNull]
        public readonly ?string $phone = null,
        #[ConvertEmptyToNull]
        public readonly ?string $address = null,
    ) {}
}

// Simulate API response with empty strings
$apiResponse = [
    'name' => 'Jane Smith',
    'email' => 'jane@example.com',
    'phone' => '',    // API returns empty string
    'address' => '',  // API returns empty string
];

$user = UserDto::fromArray($apiResponse);

echo "Name: {$user->name}\n";
echo "Email: {$user->email}\n";
echo "Phone: " . ($user->phone === null ? 'null (not provided)' : $user->phone) . "\n";
echo "Address: " . ($user->address === null ? 'null (not provided)' : $user->address) . "\n\n";

// Example 4: Boolean Handling
echo "Example 4: Boolean Handling\n";
echo "----------------------------\n";

class NotificationDto extends SimpleDto
{
    public function __construct(
        #[ConvertEmptyToNull]
        public readonly ?bool $emailEnabled = null,
        #[ConvertEmptyToNull]
        public readonly ?bool $smsEnabled = null,
        #[ConvertEmptyToNull]
        public readonly ?string $email = null,
    ) {}
}

$notification = NotificationDto::fromArray([
    'emailEnabled' => false,  // Boolean false stays false
    'smsEnabled' => true,     // Boolean true stays true
    'email' => '',            // Empty string becomes null
]);

echo "Email Enabled: " . ($notification->emailEnabled === false ? 'false' : ($notification->emailEnabled === true ? 'true' : 'null')) . "\n";
echo "SMS Enabled: " . ($notification->smsEnabled === false ? 'false' : ($notification->smsEnabled === true ? 'true' : 'null')) . "\n";
echo "Email: " . ($notification->email === null ? 'null' : $notification->email) . "\n\n";

// Example 5: Zero Values (Default Behavior)
echo "Example 5: Zero Values (Default Behavior)\n";
echo "------------------------------------------\n";

class StatsDto extends SimpleDto
{
    public function __construct(
        #[ConvertEmptyToNull]
        public readonly ?int $count = null,
        #[ConvertEmptyToNull]
        public readonly ?string $value = null,
        public readonly ?int $total = null,
    ) {}
}

$stats = StatsDto::fromArray([
    'count' => 0,    // Integer zero stays 0 (default)
    'value' => '0',  // String zero stays '0' (default)
    'total' => 100,  // Non-zero stays as is
]);

echo "Count: " . ($stats->count === null ? 'null' : $stats->count) . "\n";
echo "Value: " . ($stats->value === null ? 'null' : $stats->value) . "\n";
echo "Total: " . ($stats->total === null ? 'null' : $stats->total) . "\n\n";

// Example 5b: Zero Values (With Conversion Enabled)
echo "Example 5b: Zero Values (With Conversion Enabled)\n";
echo "--------------------------------------------------\n";

class StatsWithZeroConversionDto extends SimpleDto
{
    public function __construct(
        #[ConvertEmptyToNull(convertZero: true)]
        public readonly ?int $count = null,
        #[ConvertEmptyToNull(convertStringZero: true)]
        public readonly ?string $value = null,
        #[ConvertEmptyToNull(convertZero: true, convertStringZero: true)]
        public readonly mixed $mixed = null,
    ) {}
}

$statsWithConversion = StatsWithZeroConversionDto::fromArray([
    'count' => 0,    // Integer zero becomes null (convertZero: true)
    'value' => '0',  // String zero becomes null (convertStringZero: true)
    'mixed' => 0,    // Both enabled, becomes null
]);

echo "Count: " . ($statsWithConversion->count === null ? 'null' : $statsWithConversion->count) . "\n";
echo "Value: " . ($statsWithConversion->value === null ? 'null' : $statsWithConversion->value) . "\n";
echo "Mixed: " . ($statsWithConversion->mixed === null ? 'null' : $statsWithConversion->mixed) . "\n\n";

// Example 6: Form Data
echo "Example 6: Form Data\n";
echo "--------------------\n";

class ContactFormDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $message,
        #[ConvertEmptyToNull]
        public readonly ?string $phone = null,
        #[ConvertEmptyToNull]
        public readonly ?string $company = null,
    ) {}
}

// Simulate form submission
$formData = [
    'name' => 'Bob Johnson',
    'email' => 'bob@example.com',
    'message' => 'I would like to know more about your services.',
    'phone' => '',     // Empty form field
    'company' => '',   // Empty form field
];

$form = ContactFormDto::fromArray($formData);

echo "Name: {$form->name}\n";
echo "Email: {$form->email}\n";
echo "Message: {$form->message}\n";
echo "Phone: " . ($form->phone === null ? 'not provided' : $form->phone) . "\n";
echo "Company: " . ($form->company === null ? 'not provided' : $form->company) . "\n\n";

// Example 7: Non-Empty Values
echo "Example 7: Non-Empty Values\n";
echo "---------------------------\n";

$profileWithData = ProfileDto::fromArray([
    'name' => 'Alice Cooper',
    'bio' => 'Software developer and open source enthusiast.',
    'tags' => ['php', 'laravel', 'symfony'],
]);

echo "Name: {$profileWithData->name}\n";
echo "Bio: {$profileWithData->bio}\n";
echo "Tags: " . implode(', ', $profileWithData->tags) . "\n\n";

// Example 8: Mixed Empty and Non-Empty
echo "Example 8: Mixed Empty and Non-Empty\n";
echo "-------------------------------------\n";

$mixedProfile = ProfileDto::fromArray([
    'name' => 'Charlie Brown',
    'bio' => 'Developer',  // Non-empty
    'tags' => [],          // Empty array
]);

echo "Name: {$mixedProfile->name}\n";
echo "Bio: {$mixedProfile->bio}\n";
echo "Tags: " . ($mixedProfile->tags === null ? 'null' : json_encode($mixedProfile->tags)) . "\n\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";

