<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Visible;

echo "=== Context-Based Visibility - Schritt für Schritt erklärt ===\n\n";

// ============================================================================
// BEISPIEL 1: Einfaches Role-Based Access
// ============================================================================
echo "1. EINFACHES ROLE-BASED ACCESS:\n";
echo str_repeat('=', 70) . "\n\n";

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $username,

        // Diese Property ist nur sichtbar, wenn canViewEmail() true zurückgibt
        #[Visible(callback: 'canViewEmail')]
        public readonly string $email,
    ) {}

    /**
     * Diese Methode wird automatisch aufgerufen, wenn toArray() oder jsonSerialize()
     * ausgeführt wird. Sie bekommt den Context übergeben, den Du mit
     * withVisibilityContext() gesetzt hast.
     *
     * @param mixed $context Der Context, den Du übergeben hast
     * @return bool true = Property ist sichtbar, false = Property wird versteckt
     */
    private function canViewEmail(mixed $context): bool
    {
        // Prüfe, ob der Context eine 'role' Property hat und ob diese 'admin' ist
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        return 'admin' === ($context?->role ?? null);
    }
}

$user = UserDto::fromArray([
    'name' => 'John Doe',
    'username' => 'johndoe',
    'email' => 'john@example.com',
]);

echo "Schritt 1: Dto erstellt\n";
echo sprintf('  Name: %s%s', $user->name, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('  Username: %s%s', $user->username, PHP_EOL);
echo "  Email: {$user->email}\n\n";

echo "Schritt 2: toArray() OHNE Context (email wird versteckt):\n";
echo json_encode($user->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

echo "Schritt 3: Context erstellen (Admin-Rolle):\n";
$adminContext = (object)[
    'role' => 'admin',
];
echo "  Context: role = admin\n\n";

echo "Schritt 4: toArray() MIT Admin-Context (email wird angezeigt):\n";
echo json_encode($user->withVisibilityContext($adminContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

echo "Schritt 5: Context erstellen (User-Rolle):\n";
$userContext = (object)[
    'role' => 'user',
];
echo "  Context: role = user\n\n";

echo "Schritt 6: toArray() MIT User-Context (email wird versteckt):\n";
echo json_encode($user->withVisibilityContext($userContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// ============================================================================
// BEISPIEL 2: Zugriff auf eigene Daten
// ============================================================================
echo "\n2. ZUGRIFF AUF EIGENE DATEN:\n";
echo str_repeat('=', 70) . "\n\n";

class ProfileDto extends SimpleDto
{
    public function __construct(
        public readonly string $userId,
        public readonly string $name,

        #[Visible(callback: 'canViewEmail')]
        public readonly string $email,

        #[Visible(callback: 'canViewPhone')]
        public readonly string $phone,
    ) {}

    /**
     * Email ist sichtbar für:
     * - Den User selbst (context->userId === $this->userId)
     * - Admins (context->role === 'admin')
     */
    private function canViewEmail(mixed $context): bool
    {
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        return ($context?->userId ?? null) === $this->userId
            /** @phpstan-ignore-next-line unknown */
            /** @phpstan-ignore-next-line unknown */
            || 'admin' === ($context?->role ?? null);
    }

    /** Phone ist nur für Admins sichtbar */
    private function canViewPhone(mixed $context): bool
    {
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        return 'admin' === ($context?->role ?? null);
    }
}

$profile = ProfileDto::fromArray([
    'userId' => 'user-123',
    'name' => 'Jane Smith',
    'email' => 'jane@example.com',
    'phone' => '+1-555-0123',
]);

echo "Szenario A: User schaut sein eigenes Profil an\n";
$ownContext = (object)[
    'userId' => 'user-123',  // Gleiche ID wie im Profil
    'role' => 'user',
];
echo "Context: userId=user-123, role=user\n";
echo "Ergebnis:\n";
echo json_encode($profile->withVisibilityContext($ownContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "→ Email sichtbar (eigene Daten), Phone versteckt (kein Admin)\n\n";

echo "Szenario B: Anderer User schaut das Profil an\n";
$otherContext = (object)[
    'userId' => 'user-456',  // Andere ID
    'role' => 'user',
];
echo "Context: userId=user-456, role=user\n";
echo "Ergebnis:\n";
echo json_encode($profile->withVisibilityContext($otherContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "→ Email versteckt (fremde Daten), Phone versteckt (kein Admin)\n\n";

echo "Szenario C: Admin schaut das Profil an\n";
$adminContext = (object)[
    'userId' => 'admin-001',
    'role' => 'admin',
];
echo "Context: userId=admin-001, role=admin\n";
echo "Ergebnis:\n";
echo json_encode($profile->withVisibilityContext($adminContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "→ Email sichtbar (Admin), Phone sichtbar (Admin)\n\n";

// ============================================================================
// BEISPIEL 3: Komplexe Business-Logik
// ============================================================================
echo "\n3. KOMPLEXE BUSINESS-LOGIK:\n";
echo str_repeat('=', 70) . "\n\n";

class OrderDto extends SimpleDto
{
    /** @param array<mixed> $paymentDetails */
    public function __construct(
        public readonly string $orderId,
        public readonly string $customerId,
        public readonly float $total,
        public readonly string $status,

        #[Visible(callback: 'canViewPaymentDetails')]
        public readonly array $paymentDetails,

        #[Visible(callback: 'canViewInternalNotes')]
        public readonly string $internalNotes,
    ) {}

    /**
     * Payment Details sind sichtbar für:
     * - Den Kunden selbst
     * - Finance Team
     * - Admins
     */
    private function canViewPaymentDetails(mixed $context): bool
    {
        // Kunde kann eigene Payment Details sehen
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        if (($context?->userId ?? null) === $this->customerId) {
            return true;
        }

        // Finance und Admin können alle Payment Details sehen
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        return in_array($context?->role ?? null, ['finance', 'admin'], true);
    }

    /** Internal Notes sind nur für interne Mitarbeiter sichtbar */
    private function canViewInternalNotes(mixed $context): bool
    {
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        return in_array($context?->role ?? null, ['support', 'finance', 'admin'], true);
    }
}

$order = OrderDto::fromArray([
    'orderId' => 'ORD-12345',
    'customerId' => 'CUST-001',
    'total' => 299.99,
    'status' => 'shipped',
    'paymentDetails' => ['method' => 'credit_card', 'last4' => '4242'],
    'internalNotes' => 'Customer requested gift wrapping',
]);

echo "Szenario A: Kunde schaut seine eigene Bestellung an\n";
$customerContext = (object)[
    'userId' => 'CUST-001',
    'role' => 'customer',
];
echo json_encode($order->withVisibilityContext($customerContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "→ Payment Details sichtbar (eigene Bestellung)\n";
echo "→ Internal Notes versteckt (kein interner Mitarbeiter)\n\n";

echo "Szenario B: Support-Mitarbeiter schaut die Bestellung an\n";
$supportContext = (object)[
    'role' => 'support',
];
echo json_encode($order->withVisibilityContext($supportContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "→ Payment Details versteckt (Support hat keinen Zugriff)\n";
echo "→ Internal Notes sichtbar (interner Mitarbeiter)\n\n";

echo "Szenario C: Finance-Mitarbeiter schaut die Bestellung an\n";
$financeContext = (object)[
    'role' => 'finance',
];
echo json_encode($order->withVisibilityContext($financeContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "→ Payment Details sichtbar (Finance hat Zugriff)\n";
echo "→ Internal Notes sichtbar (interner Mitarbeiter)\n\n";

// ============================================================================
// BEISPIEL 4: Chaining mit only() und except()
// ============================================================================
echo "\n4. CHAINING MIT only() UND except():\n";
echo str_repeat('=', 70) . "\n\n";

echo "Du kannst withVisibilityContext() mit only() und except() kombinieren:\n\n";

echo "A) Context + only():\n";
$result = $profile
    ->withVisibilityContext($adminContext)
    ->only(['name', 'email'])
    ->toArray();
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
echo "→ Nur name und email, aber email nur weil Admin-Context\n\n";

echo "B) Context + except():\n";
$result = $profile
    ->withVisibilityContext($adminContext)
    ->except(['phone'])
    ->toArray();
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
echo "→ Alles außer phone, email sichtbar weil Admin-Context\n\n";

// ============================================================================
// BEISPIEL 5: Context-Objekt kann beliebig sein
// ============================================================================
echo "\n5. CONTEXT-OBJEKT KANN BELIEBIG SEIN:\n";
echo str_repeat('=', 70) . "\n\n";

class DocumentDto extends SimpleDto
{
    public function __construct(
        public readonly string $title,

        #[Visible(callback: 'canViewContent')]
        public readonly string $content,
    ) {}

    private function canViewContent(mixed $context): bool
    {
        // Context kann ein Array sein
        if (is_array($context)) {
            return true === ($context['hasAccess'] ?? false);
        }

        // Context kann ein Objekt sein
        if (is_object($context)) {
            return true === ($context->hasAccess ?? false);
        }

        // Context kann auch ein String sein
        if (is_string($context)) {
            return 'granted' === $context;
        }

        return false;
    }
}

$doc = DocumentDto::fromArray([
    'title' => 'Secret Document',
    'content' => 'Top secret content...',
]);

echo "Context als Array:\n";
echo json_encode($doc->withVisibilityContext(['hasAccess' => true])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

echo "Context als Object:\n";
echo json_encode($doc->withVisibilityContext((object)['hasAccess' => true])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

echo "Context als String:\n";
echo json_encode($doc->withVisibilityContext('granted')->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

echo "Context als String (denied):\n";
echo json_encode($doc->withVisibilityContext('denied')->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// ============================================================================
// ZUSAMMENFASSUNG
// ============================================================================
echo "\n" . str_repeat('=', 70) . "\n";
echo "ZUSAMMENFASSUNG:\n";
echo str_repeat('=', 70) . "\n\n";

echo "1. #[Visible(callback: 'methodName')] markiert eine Property als bedingt sichtbar\n";
echo "2. Die Callback-Methode muss bool zurückgeben (true = sichtbar, false = versteckt)\n";
echo "3. withVisibilityContext(\$context) setzt den Context für die Visibility-Checks\n";
echo "4. Der Context wird an alle Callback-Methoden übergeben\n";
echo "5. Der Context kann beliebig sein (Object, Array, String, etc.)\n";
echo "6. Callback-Methoden können auf Dto-Properties zugreifen (\$this->userId)\n";
echo "7. Callback-Methoden können private/protected sein\n";
echo "8. withVisibilityContext() ist chainable mit only() und except()\n";
echo "9. Ohne Context werden #[Visible] Properties standardmäßig versteckt\n\n";

echo "✅  Alle Beispiele abgeschlossen!\n";
