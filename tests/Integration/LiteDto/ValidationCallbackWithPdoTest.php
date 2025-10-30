<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\Validation\Email;
use event4u\DataHelpers\LiteDto\Attributes\Validation\ExistsCallback;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Required;
use event4u\DataHelpers\LiteDto\Attributes\Validation\UniqueCallback;
use event4u\DataHelpers\LiteDto\LiteDto;

// Create in-memory SQLite database (shared across all tests)
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create users table
$pdo->exec('
    CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        name TEXT NOT NULL
    )
');

// Create products table
$pdo->exec('
    CREATE TABLE products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sku TEXT UNIQUE NOT NULL,
        name TEXT NOT NULL,
        active INTEGER DEFAULT 1
    )
');

// Define DTO classes with static PDO reference
class UserValidationDto extends LiteDto
{
    private static ?PDO $pdo = null;

    public function __construct(
        #[Required]
        #[Email]
        #[UniqueCallback([self::class, 'validateUniqueEmail'])]
        public readonly string $email,

        #[Required]
        public readonly string $name,

        public readonly ?int $id = null,
    ) {}

    public static function setPdo(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function validateUniqueEmail(mixed $value, array $data): bool
    {
        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND (:id IS NULL OR id != :id)');
        $stmt->execute([
            'email' => $value,
            'id' => $data['id'] ?? null,
        ]);

        return $stmt->fetchColumn() === 0;
    }
}

class ProductValidationDto extends LiteDto
{
    private static ?PDO $pdo = null;

    public function __construct(
        #[Required]
        public readonly string $name,

        #[ExistsCallback([self::class, 'validateProductExists'])]
        public readonly ?int $relatedProductId = null,
    ) {}

    public static function setPdo(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function validateProductExists(mixed $value): bool
    {
        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM products WHERE id = :id AND active = 1');
        $stmt->execute(['id' => $value]);

        return $stmt->fetchColumn() > 0;
    }
}

describe('LiteDto Validation Callbacks with PDO/SQLite', function() use ($pdo): void {
    beforeEach(function() use ($pdo): void {
        // Clean tables before each test
        $pdo->exec('DELETE FROM users');
        $pdo->exec('DELETE FROM products');
        $this->pdo = $pdo;

        // Set PDO for DTOs
        UserValidationDto::setPdo($pdo);
        ProductValidationDto::setPdo($pdo);
    });

    describe('UniqueCallback with PDO', function(): void {
        it('validates unique email for new user', function(): void {
            $result = UserValidationDto::validate([
                'email' => 'new@example.com',
                'name' => 'New User',
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails validation for duplicate email', function(): void {
            // Insert existing user
            $stmt = $this->pdo->prepare('INSERT INTO users (email, name) VALUES (:email, :name)');
            $stmt->execute(['email' => 'existing@example.com', 'name' => 'Existing User']);

            $result = UserValidationDto::validate([
                'email' => 'existing@example.com',
                'name' => 'Another User',
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('email'))->toBeTrue();
        });

        it('allows same email when updating own record', function(): void {
            // Insert existing user
            $stmt = $this->pdo->prepare('INSERT INTO users (email, name) VALUES (:email, :name)');
            $stmt->execute(['email' => 'update@example.com', 'name' => 'Update User']);

            $userId = (int)$this->pdo->lastInsertId();

            $result = UserValidationDto::validate([
                'id' => $userId,
                'email' => 'update@example.com',
                'name' => 'Updated Name',
            ]);

            expect($result->isValid())->toBeTrue();
        });
    });

    describe('ExistsCallback with PDO', function(): void {
        it('validates existing active product', function(): void {
            // Insert active product
            $stmt = $this->pdo->prepare('INSERT INTO products (sku, name, active) VALUES (:sku, :name, :active)');
            $stmt->execute(['sku' => 'PROD-001', 'name' => 'Active Product', 'active' => 1]);

            $productId = (int)$this->pdo->lastInsertId();

            $result = ProductValidationDto::validate([
                'name' => 'New Product',
                'relatedProductId' => $productId,
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails for non-existing product', function(): void {
            $result = ProductValidationDto::validate([
                'name' => 'New Product',
                'relatedProductId' => 99999,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('relatedProductId'))->toBeTrue();
        });

        it('fails for inactive product', function(): void {
            // Insert inactive product
            $stmt = $this->pdo->prepare('INSERT INTO products (sku, name, active) VALUES (:sku, :name, :active)');
            $stmt->execute(['sku' => 'PROD-002', 'name' => 'Inactive Product', 'active' => 0]);

            $productId = (int)$this->pdo->lastInsertId();

            $result = ProductValidationDto::validate([
                'name' => 'New Product',
                'relatedProductId' => $productId,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('relatedProductId'))->toBeTrue();
        });

        it('allows null related product', function(): void {
            $result = ProductValidationDto::validate([
                'name' => 'New Product',
            ]);

            expect($result->isValid())->toBeTrue();
        });
    });
});
