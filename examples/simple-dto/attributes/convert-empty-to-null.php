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

echo sprintf('Name: %s%s', $profile->name, PHP_EOL);
echo "Bio: " . ($profile->bio ?? 'null') . "\n";
echo "Tags: " . (null === $profile->tags ? 'null' : json_encode($profile->tags)) . "\n\n";

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

echo sprintf('Theme: %s%s', $settings->theme, PHP_EOL);
echo "Language: " . ($settings->language ?? 'null') . "\n";
echo "Preferences: " . (null === $settings->preferences ? 'null' : json_encode($settings->preferences)) . "\n\n";

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

echo sprintf('Name: %s%s', $user->name, PHP_EOL);
echo sprintf('Email: %s%s', $user->email, PHP_EOL);
echo "Phone: " . ($user->phone ?? 'null (not provided)') . "\n";
echo "Address: " . ($user->address ?? 'null (not provided)') . "\n\n";

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

echo "Email Enabled: " . (false === $notification->emailEnabled ? 'false' : (true === $notification->emailEnabled ? 'true' : 'null')) . "\n";
echo "SMS Enabled: " . (false === $notification->smsEnabled ? 'false' : (true === $notification->smsEnabled ? 'true' : 'null')) . "\n";
echo "Email: " . ($notification->email ?? 'null') . "\n\n";

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

echo "Count: " . ($stats->count ?? 'null') . "\n";
echo "Value: " . ($stats->value ?? 'null') . "\n";
echo "Total: " . ($stats->total ?? 'null') . "\n\n";

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

echo "Count: " . ($statsWithConversion->count ?? 'null') . "\n";
echo "Value: " . ($statsWithConversion->value ?? 'null') . "\n";
echo "Mixed: " . ($statsWithConversion->mixed ?? 'null') . "\n\n";

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

echo sprintf('Name: %s%s', $form->name, PHP_EOL);
echo sprintf('Email: %s%s', $form->email, PHP_EOL);
echo sprintf('Message: %s%s', $form->message, PHP_EOL);
echo "Phone: " . ($form->phone ?? 'not provided') . "\n";
echo "Company: " . ($form->company ?? 'not provided') . "\n\n";

// Example 7: Non-Empty Values
echo "Example 7: Non-Empty Values\n";
echo "---------------------------\n";

$profileWithData = ProfileDto::fromArray([
    'name' => 'Alice Cooper',
    'bio' => 'Software developer and open source enthusiast.',
    'tags' => ['php', 'laravel', 'symfony'],
]);

echo sprintf('Name: %s%s', $profileWithData->name, PHP_EOL);
echo sprintf('Bio: %s%s', $profileWithData->bio, PHP_EOL);
echo "Tags: " . implode(', ', $profileWithData->tags) . "\n\n";

// Example 8: Mixed Empty and Non-Empty
echo "Example 8: Mixed Empty and Non-Empty\n";
echo "-------------------------------------\n";

$mixedProfile = ProfileDto::fromArray([
    'name' => 'Charlie Brown',
    'bio' => 'Developer',  // Non-empty
    'tags' => [],          // Empty array
]);

echo sprintf('Name: %s%s', $mixedProfile->name, PHP_EOL);
echo sprintf('Bio: %s%s', $mixedProfile->bio, PHP_EOL);
echo "Tags: " . (null === $mixedProfile->tags ? 'null' : json_encode($mixedProfile->tags)) . "\n\n";

// Example 9: Convert False to Null
echo "Example 9: Convert False to Null\n";
echo "---------------------------------\n";

class FeatureFlagsDto extends SimpleDto
{
    public function __construct(
        // Default: false stays false
        #[ConvertEmptyToNull]
        public readonly ?bool $notifications = null,

        // Convert false to null
        #[ConvertEmptyToNull(convertFalse: true)]
        public readonly ?bool $newsletter = null,
    ) {}
}

$flags = FeatureFlagsDto::fromArray([
    'notifications' => false,
    'newsletter' => false,
]);

echo "Notifications: " . (null === $flags->notifications ? 'null' : ($flags->notifications ? 'true' : 'false')) . "\n";
echo "Newsletter: " . (null === $flags->newsletter ? 'null' : ($flags->newsletter ? 'true' : 'false')) . "\n\n";

// Example 10: Combine All Options
echo "Example 10: Combine All Options\n";
echo "--------------------------------\n";

class FlexibleDto extends SimpleDto
{
    public function __construct(
        #[ConvertEmptyToNull(convertZero: true, convertStringZero: true, convertFalse: true)]
        public readonly mixed $value = null,
    ) {}
}

$dto1 = FlexibleDto::fromArray(['value' => '']);
$dto2 = FlexibleDto::fromArray(['value' => []]);
$dto3 = FlexibleDto::fromArray(['value' => 0]);
$dto4 = FlexibleDto::fromArray(['value' => '0']);
$dto5 = FlexibleDto::fromArray(['value' => false]);
$dto6 = FlexibleDto::fromArray(['value' => true]);
$dto7 = FlexibleDto::fromArray(['value' => 'hello']);

echo "Empty string: " . ($dto1->value ?? 'null') . "\n";
echo "Empty array: " . (null === $dto2->value ? 'null' : json_encode($dto2->value)) . "\n";
echo "Integer zero: " . ($dto3->value ?? 'null') . "\n";
echo "String zero: " . ($dto4->value ?? 'null') . "\n";
echo "Boolean false: " . (null === $dto5->value ? 'null' : ($dto5->value ? 'true' : 'false')) . "\n";
echo "Boolean true: " . (null === $dto6->value ? 'null' : ($dto6->value ? 'true' : 'false')) . "\n";
echo "String value: " . ($dto7->value ?? 'null') . "\n\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";
