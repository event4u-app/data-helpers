<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Visible;

echo "=== Static Callbacks & Context Providers ===\n\n";

// ============================================================================
// HELPER CLASSES
// ============================================================================

/**
 * Permission Checker mit statischen Methoden
 */
class PermissionChecker
{
    public static function canViewEmail(mixed $dto, mixed $context): bool
    {
        // Admin kann alles sehen
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        if ('admin' === ($context?->role ?? null)) {
            return true;
        }

        // User kann eigene Email sehen
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        if (isset($dto->userId) && isset($context?->userId)) {
            /** @phpstan-ignore-next-line unknown */
            return $context->userId === $dto->userId;
        }

        return false;
    }

    public static function canViewSalary(mixed $dto, mixed $context): bool
    {
        // Nur Admin und HR können Gehälter sehen
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        return in_array($context?->role ?? null, ['admin', 'hr'], true);
    }

    public static function canViewInternalNotes(mixed $dto, mixed $context): bool
    {
        // Nur interne Mitarbeiter
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        return in_array($context?->role ?? null, ['admin', 'hr', 'support'], true);
    }
}

/**
 * Context Provider - holt automatisch den aktuellen User
 */
class AuthContextProvider
{
    private static ?object $currentUser = null;

    public static function setCurrentUser(?object $user): void
    {
        self::$currentUser = $user;
    }

    public static function getContext(): mixed
    {
        return self::$currentUser;
    }
}

/**
 * Alternative Context Provider für API Requests
 */
class ApiContextProvider
{
    private static ?object $apiContext = null;

    public static function setApiContext(?object $context): void
    {
        self::$apiContext = $context;
    }

    public static function getContext(): mixed
    {
        return self::$apiContext;
    }
}

// ============================================================================
// BEISPIEL 1: Static Callbacks
// ============================================================================

echo "1. STATIC CALLBACKS:\n";
echo str_repeat('=', 70) . "\n\n";

class EmployeeDto extends SimpleDto
{
    public function __construct(
        public readonly string $userId,
        public readonly string $name,
        public readonly string $department,

        // Static callback - keine Instanz-Methode nötig!
        #[Visible(callback: [PermissionChecker::class, 'canViewEmail'])]
        public readonly string $email,

        #[Visible(callback: [PermissionChecker::class, 'canViewSalary'])]
        public readonly float $salary,

        #[Visible(callback: [PermissionChecker::class, 'canViewInternalNotes'])]
        public readonly string $internalNotes,
    ) {}
}

$employee = EmployeeDto::fromArray([
    'userId' => 'emp-123',
    'name' => 'John Doe',
    'department' => 'Engineering',
    'email' => 'john@company.com',
    'salary' => 75000.0,
    'internalNotes' => 'Top performer, consider for promotion',
]);

echo "Szenario A: Employee schaut sein eigenes Profil an\n";
$employeeContext = (object)[
    'userId' => 'emp-123',
    'role' => 'employee',
];
echo json_encode($employee->withVisibilityContext($employeeContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "→ Email sichtbar (eigene Daten)\n";
echo "→ Salary versteckt (kein HR/Admin)\n";
echo "→ Internal Notes versteckt (kein interner Staff)\n\n";

echo "Szenario B: HR schaut das Profil an\n";
$hrContext = (object)[
    'userId' => 'hr-001',
    'role' => 'hr',
];
echo json_encode($employee->withVisibilityContext($hrContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "→ Email versteckt (fremde Daten, kein Admin)\n";
echo "→ Salary sichtbar (HR hat Zugriff)\n";
echo "→ Internal Notes sichtbar (HR ist interner Staff)\n\n";

echo "Szenario C: Admin schaut das Profil an\n";
$adminContext = (object)[
    'userId' => 'admin-001',
    'role' => 'admin',
];
echo json_encode($employee->withVisibilityContext($adminContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "→ Alles sichtbar (Admin hat vollen Zugriff)\n\n";

// ============================================================================
// BEISPIEL 2: Context Provider (Auto-Context)
// ============================================================================

echo "\n2. CONTEXT PROVIDER (AUTO-CONTEXT):\n";
echo str_repeat('=', 70) . "\n\n";

class UserProfileDto extends SimpleDto
{
    public function __construct(
        public readonly string $userId,
        public readonly string $username,
        public readonly string $displayName,

        // Context wird automatisch von AuthContextProvider geholt!
        #[Visible(
            contextProvider: AuthContextProvider::class,
            callback: [PermissionChecker::class, 'canViewEmail']
        )]
        public readonly string $email,

        #[Visible(
            contextProvider: AuthContextProvider::class,
            callback: [PermissionChecker::class, 'canViewInternalNotes']
        )]
        public readonly string $notes,
    ) {}
}

$profile = UserProfileDto::fromArray([
    'userId' => 'user-456',
    'username' => 'janedoe',
    'displayName' => 'Jane Doe',
    'email' => 'jane@company.com',
    'notes' => 'VIP customer',
]);

echo "Schritt 1: Setze aktuellen User (simuliert Login)\n";
AuthContextProvider::setCurrentUser((object)[
    'userId' => 'user-456',
    'role' => 'user',
]);
echo "Current User: user-456, role: user\n\n";

echo "Schritt 2: toArray() OHNE withVisibilityContext()\n";
echo "Context wird automatisch von AuthContextProvider geholt!\n";
echo json_encode($profile->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "→ Email sichtbar (eigene Daten, Context automatisch geholt)\n";
echo "→ Notes versteckt (kein interner Staff)\n\n";

echo "Schritt 3: Wechsel zu Admin User\n";
AuthContextProvider::setCurrentUser((object)[
    'userId' => 'admin-002',
    'role' => 'admin',
]);
echo "Current User: admin-002, role: admin\n\n";

echo "Schritt 4: toArray() - Context wird automatisch aktualisiert\n";
echo json_encode($profile->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "→ Email sichtbar (Admin)\n";
echo "→ Notes sichtbar (Admin ist interner Staff)\n\n";

// ============================================================================
// BEISPIEL 3: Kombination - Static Callback + Context Provider
// ============================================================================

echo "\n3. KOMBINATION: STATIC CALLBACK + CONTEXT PROVIDER:\n";
echo str_repeat('=', 70) . "\n\n";

class OrderDto extends SimpleDto
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $customerId,
        public readonly float $total,

        // Kombiniert: Context Provider + Static Callback
        #[Visible(
            contextProvider: AuthContextProvider::class,
            callback: [PermissionChecker::class, 'canViewEmail']
        )]
        public readonly string $customerEmail,

        #[Visible(
            contextProvider: AuthContextProvider::class,
            callback: [PermissionChecker::class, 'canViewInternalNotes']
        )]
        public readonly string $processingNotes,
    ) {}
}

$order = OrderDto::fromArray([
    'orderId' => 'ORD-789',
    'customerId' => 'cust-123',
    'total' => 299.99,
    'customerEmail' => 'customer@example.com',
    'processingNotes' => 'Rush order, expedited shipping',
]);

echo "Aktueller User: admin-002 (aus vorherigem Beispiel)\n";
echo "toArray() ohne expliziten Context:\n";
echo json_encode($order->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "→ Context wird automatisch geholt\n";
echo "→ Admin sieht alles\n\n";

echo "Wechsel zu Customer:\n";
AuthContextProvider::setCurrentUser((object)[
    'userId' => 'cust-123',
    'role' => 'customer',
]);
echo json_encode($order->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "→ Processing Notes versteckt (kein interner Staff)\n\n";

// ============================================================================
// BEISPIEL 4: Override mit withVisibilityContext()
// ============================================================================

echo "\n4. OVERRIDE MIT withVisibilityContext():\n";
echo str_repeat('=', 70) . "\n\n";

echo "Context Provider ist gesetzt (customer), aber wir überschreiben:\n";
$overrideContext = (object)[
    'userId' => 'support-001',
    'role' => 'support',
];

echo "withVisibilityContext() überschreibt den Provider:\n";
echo json_encode($order->withVisibilityContext($overrideContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "→ Manual context hat Vorrang vor Provider\n";
echo "→ Support sieht Processing Notes\n\n";

// ============================================================================
// BEISPIEL 5: API Context Provider
// ============================================================================

echo "\n5. API CONTEXT PROVIDER:\n";
echo str_repeat('=', 70) . "\n\n";

class ApiResponseDto extends SimpleDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,

        // Verwendet API Context Provider
        #[Visible(
            contextProvider: ApiContextProvider::class,
            callback: [PermissionChecker::class, 'canViewInternalNotes']
        )]
        public readonly string $debugInfo,
    ) {}
}

$apiResponse = ApiResponseDto::fromArray([
    'id' => 'api-001',
    'title' => 'API Response',
    'debugInfo' => 'Query took 0.5s, 3 DB queries',
]);

echo "API Request ohne Auth:\n";
ApiContextProvider::setApiContext((object)[
    'authenticated' => false,
]);
echo json_encode($apiResponse->toArray(), JSON_PRETTY_PRINT) . "\n\n";

echo "API Request mit Admin Auth:\n";
ApiContextProvider::setApiContext((object)[
    'authenticated' => true,
    'role' => 'admin',
]);
echo json_encode($apiResponse->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// ============================================================================
// ZUSAMMENFASSUNG
// ============================================================================

echo str_repeat('=', 70) . "\n";
echo "ZUSAMMENFASSUNG:\n";
echo str_repeat('=', 70) . "\n\n";

echo "✅  STATIC CALLBACKS:\n";
echo "  - Callback: [PermissionChecker::class, 'canViewEmail']\n";
echo "  - Keine Instanz-Methoden im Dto nötig\n";
echo "  - Wiederverwendbar über mehrere Dtos\n";
echo "  - Bekommt Dto und Context als Parameter\n\n";

echo "✅  CONTEXT PROVIDERS:\n";
echo "  - contextProvider: AuthContextProvider::class\n";
echo "  - Context wird automatisch geholt\n";
echo "  - Kein withVisibilityContext() nötig\n";
echo "  - Perfekt für globalen Auth-Context\n\n";

echo "✅  KOMBINATION:\n";
echo "  - Static Callback + Context Provider\n";
echo "  - Maximale Flexibilität\n";
echo "  - Wiederverwendbar und testbar\n\n";

echo "✅  OVERRIDE:\n";
echo "  - withVisibilityContext() überschreibt Provider\n";
echo "  - Nützlich für Tests oder spezielle Cases\n\n";

echo "✅  Beispiele abgeschlossen!\n";
