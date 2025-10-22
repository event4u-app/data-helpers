<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;

echo "================================================================================\n";
echo "SimpleDTO - Encrypted Cast Examples\n";
echo "================================================================================\n\n";

// Example 1: Encrypting Sensitive Data
echo "Example 1: Encrypting Sensitive Data\n";
echo "-------------------------------------\n";

class PaymentDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $orderId,
        public readonly float $amount,
        public readonly ?string $creditCard = null,
        public readonly ?string $cvv = null,
    ) {}

    protected function casts(): array
    {
        return [
            'creditCard' => 'encrypted',
            'cvv' => 'encrypted',
        ];
    }
}

$payment = PaymentDTO::fromArray([
    'orderId' => 'ORD-12345',
    'amount' => 99.99,
    'creditCard' => '4111111111111111',
    'cvv' => '123',
]);

/** @phpstan-ignore-next-line unknown */
echo sprintf('Order ID: %s%s', $payment->orderId, PHP_EOL);
echo sprintf('Amount: $%s%s', $payment->amount, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('Credit Card: %s%s', $payment->creditCard, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('CVV: %s%s', $payment->cvv, PHP_EOL);
echo "\nNote: Data is encrypted when stored, decrypted when accessed\n\n";

// Example 2: Storing Encrypted Data
echo "Example 2: Storing Encrypted Data\n";
echo "----------------------------------\n";

$storedData = $payment->toArray();
echo "Stored data (encrypted):\n";
echo sprintf('Credit Card: %s%s', $storedData['creditCard'], PHP_EOL);
echo sprintf('CVV: %s%s', $storedData['cvv'], PHP_EOL);
echo "\nNote: Values are encrypted in storage ✅\n\n";

// Example 3: User Personal Information
echo "Example 3: User Personal Information\n";
echo "-------------------------------------\n";

class UserProfileDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $username,
        public readonly ?string $ssn = null,
        public readonly ?string $phoneNumber = null,
    ) {}

    protected function casts(): array
    {
        return [
            'ssn' => 'encrypted',
            'phoneNumber' => 'encrypted',
        ];
    }
}

$profile = UserProfileDTO::fromArray([
    'userId' => 1,
    'username' => 'john_doe',
    'ssn' => '123-45-6789',
    'phoneNumber' => '+1-555-0123',
]);

/** @phpstan-ignore-next-line unknown */
echo sprintf('User ID: %s%s', $profile->userId, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('Username: %s%s', $profile->username, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('SSN: %s%s', $profile->ssn, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "Phone: {$profile->phoneNumber}\n\n";

// Example 4: Null Values
echo "Example 4: Null Values\n";
echo "----------------------\n";

$profileWithoutSensitiveData = UserProfileDTO::fromArray([
    'userId' => 2,
    'username' => 'jane_doe',
    'ssn' => null,
    'phoneNumber' => null,
]);

/** @phpstan-ignore-next-line unknown */
echo sprintf('User ID: %s%s', $profileWithoutSensitiveData->userId, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('Username: %s%s', $profileWithoutSensitiveData->username, PHP_EOL);
echo "SSN: " . ($profileWithoutSensitiveData->ssn ?? 'Not provided') . "\n";
echo "Phone: " . ($profileWithoutSensitiveData->phoneNumber ?? 'Not provided') . "\n\n";

// Example 5: API Tokens
echo "Example 5: API Tokens\n";
echo "---------------------\n";

class ApiCredentialsDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $serviceName,
        public readonly ?string $apiKey = null,
        public readonly ?string $apiSecret = null,
    ) {}

    protected function casts(): array
    {
        return [
            'apiKey' => 'encrypted',
            'apiSecret' => 'encrypted',
        ];
    }
}

$credentials = ApiCredentialsDTO::fromArray([
    'serviceName' => 'Payment Gateway',
    'apiKey' => 'pk_live_1234567890abcdef',
    'apiSecret' => 'sk_live_abcdef1234567890',
]);

echo sprintf('Service: %s%s', $credentials->serviceName, PHP_EOL);
echo sprintf('API Key: %s%s', $credentials->apiKey, PHP_EOL);
echo sprintf('API Secret: %s%s', $credentials->apiSecret, PHP_EOL);
echo "\nStored (encrypted):\n";
$storedCredentials = $credentials->toArray();
echo json_encode($storedCredentials, JSON_PRETTY_PRINT) . "\n\n";

// Example 6: Medical Records
echo "Example 6: Medical Records\n";
echo "--------------------------\n";

class MedicalRecordDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $patientId,
        public readonly string $patientName,
        public readonly ?string $diagnosis = null,
        public readonly ?string $prescription = null,
    ) {}

    protected function casts(): array
    {
        return [
            'diagnosis' => 'encrypted',
            'prescription' => 'encrypted',
        ];
    }
}

$record = MedicalRecordDTO::fromArray([
    'patientId' => 12345,
    'patientName' => 'John Smith',
    'diagnosis' => 'Hypertension',
    'prescription' => 'Lisinopril 10mg daily',
]);

echo sprintf('Patient ID: %d%s', $record->patientId, PHP_EOL);
echo sprintf('Patient Name: %s%s', $record->patientName, PHP_EOL);
echo sprintf('Diagnosis: %s%s', $record->diagnosis, PHP_EOL);
echo "Prescription: {$record->prescription}\n\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "\n";
echo "IMPORTANT NOTES:\n";
echo "- Encryption requires APP_KEY environment variable\n";
echo "- Supports Laravel, Symfony (sodium), or fallback to base64\n";
echo "- Fallback (base64) is NOT secure - only for development!\n";
echo "- Always use proper encryption in production\n";
echo "================================================================================\n";
