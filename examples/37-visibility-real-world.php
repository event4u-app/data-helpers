<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Visible;

echo "=== Real-World Beispiel: Blog-System ===\n\n";

// ============================================================================
// BLOG POST DTO
// ============================================================================

class BlogPostDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $content,
        public readonly string $authorId,
        public readonly string $status, // 'draft', 'published', 'archived'
        
        // Nur für den Autor oder Admins sichtbar
        #[Visible(callback: 'canViewDraftContent')]
        public readonly ?string $draftContent,
        
        // Nur für Admins sichtbar
        #[Visible(callback: 'canViewAnalytics')]
        public readonly array $analytics,
        
        // Nur für den Autor oder Admins sichtbar
        #[Visible(callback: 'canViewEditHistory')]
        public readonly array $editHistory,
    ) {}

    /**
     * Draft Content ist sichtbar für:
     * - Den Autor selbst
     * - Admins
     */
    private function canViewDraftContent(mixed $context): bool
    {
        if (!$context) {
            return false;
        }

        // Admin kann alles sehen
        if (($context->role ?? null) === 'admin') {
            return true;
        }

        // Autor kann eigenen Draft sehen
        return ($context->userId ?? null) === $this->authorId;
    }

    /**
     * Analytics sind nur für Admins sichtbar
     */
    private function canViewAnalytics(mixed $context): bool
    {
        return ($context?->role ?? null) === 'admin';
    }

    /**
     * Edit History ist sichtbar für:
     * - Den Autor selbst
     * - Admins
     */
    private function canViewEditHistory(mixed $context): bool
    {
        if (!$context) {
            return false;
        }

        // Admin kann alles sehen
        if (($context->role ?? null) === 'admin') {
            return true;
        }

        // Autor kann eigene History sehen
        return ($context->userId ?? null) === $this->authorId;
    }
}

// ============================================================================
// BEISPIEL-DATEN
// ============================================================================

$blogPost = BlogPostDTO::fromArray([
    'id' => 'post-123',
    'title' => 'Mein erster Blog-Post',
    'content' => 'Dies ist der veröffentlichte Inhalt...',
    'authorId' => 'author-456',
    'status' => 'published',
    'draftContent' => 'Dies ist der Draft-Inhalt, der noch nicht veröffentlicht wurde...',
    'analytics' => [
        'views' => 1234,
        'likes' => 56,
        'shares' => 12,
    ],
    'editHistory' => [
        '2024-01-01 10:00' => 'Erstellt',
        '2024-01-02 14:30' => 'Titel geändert',
        '2024-01-03 09:15' => 'Veröffentlicht',
    ],
]);

// ============================================================================
// SZENARIO 1: Öffentlicher Besucher (kein Context)
// ============================================================================

echo "SZENARIO 1: Öffentlicher Besucher (kein Login)\n";
echo str_repeat('-', 70) . "\n";
echo "Context: null\n\n";

$publicView = $blogPost->toArray();
echo "Sichtbare Felder:\n";
print_r($publicView);
echo "\n";
echo "→ Nur öffentliche Felder sichtbar\n";
echo "→ draftContent, analytics, editHistory sind versteckt\n\n";

// ============================================================================
// SZENARIO 2: Eingeloggter User (nicht der Autor)
// ============================================================================

echo "SZENARIO 2: Eingeloggter User (nicht der Autor)\n";
echo str_repeat('-', 70) . "\n";

$userContext = (object)[
    'userId' => 'user-789',
    'role' => 'user',
];
echo "Context: userId=user-789, role=user\n\n";

$userView = $blogPost->withVisibilityContext($userContext)->toArray();
echo "Sichtbare Felder:\n";
print_r($userView);
echo "\n";
echo "→ Nur öffentliche Felder sichtbar\n";
echo "→ Keine zusätzlichen Rechte, da nicht der Autor\n\n";

// ============================================================================
// SZENARIO 3: Der Autor selbst
// ============================================================================

echo "SZENARIO 3: Der Autor selbst\n";
echo str_repeat('-', 70) . "\n";

$authorContext = (object)[
    'userId' => 'author-456',  // Gleiche ID wie authorId im Post
    'role' => 'author',
];
echo "Context: userId=author-456, role=author\n\n";

$authorView = $blogPost->withVisibilityContext($authorContext)->toArray();
echo "Sichtbare Felder:\n";
print_r($authorView);
echo "\n";
echo "→ Öffentliche Felder + draftContent + editHistory\n";
echo "→ Analytics versteckt (nur für Admins)\n\n";

// ============================================================================
// SZENARIO 4: Admin
// ============================================================================

echo "SZENARIO 4: Admin\n";
echo str_repeat('-', 70) . "\n";

$adminContext = (object)[
    'userId' => 'admin-001',
    'role' => 'admin',
];
echo "Context: userId=admin-001, role=admin\n\n";

$adminView = $blogPost->withVisibilityContext($adminContext)->toArray();
echo "Sichtbare Felder:\n";
print_r($adminView);
echo "\n";
echo "→ ALLE Felder sichtbar\n";
echo "→ Admin hat vollen Zugriff\n\n";

// ============================================================================
// SZENARIO 5: API Endpoint - Nur bestimmte Felder
// ============================================================================

echo "SZENARIO 5: API Endpoint - Nur bestimmte Felder\n";
echo str_repeat('-', 70) . "\n";

echo "A) Public API - Nur title und content:\n";
$publicApiResponse = $blogPost->only(['title', 'content'])->toArray();
echo json_encode($publicApiResponse, JSON_PRETTY_PRINT) . "\n\n";

echo "B) Author API - title, content, draftContent:\n";
$authorApiResponse = $blogPost
    ->withVisibilityContext($authorContext)
    ->only(['title', 'content', 'draftContent'])
    ->toArray();
echo json_encode($authorApiResponse, JSON_PRETTY_PRINT) . "\n\n";

echo "C) Admin API - Alles außer editHistory:\n";
$adminApiResponse = $blogPost
    ->withVisibilityContext($adminContext)
    ->except(['editHistory'])
    ->toArray();
echo json_encode($adminApiResponse, JSON_PRETTY_PRINT) . "\n\n";

// ============================================================================
// PRAKTISCHER USE-CASE: Laravel Controller
// ============================================================================

echo "\n" . str_repeat('=', 70) . "\n";
echo "PRAKTISCHER USE-CASE: Laravel Controller\n";
echo str_repeat('=', 70) . "\n\n";

echo "```php\n";
echo "// In einem Laravel Controller:\n\n";

echo "public function show(Request \$request, string \$postId): JsonResponse\n";
echo "{\n";
echo "    // Blog Post aus DB laden\n";
echo "    \$post = BlogPost::findOrFail(\$postId);\n\n";

echo "    // DTO erstellen\n";
echo "    \$dto = BlogPostDTO::fromArray(\$post->toArray());\n\n";

echo "    // Context aus aktuellem User erstellen\n";
echo "    \$context = (object)[\n";
echo "        'userId' => auth()->id(),\n";
echo "        'role' => auth()->user()->role,\n";
echo "    ];\n\n";

echo "    // DTO mit Context zurückgeben\n";
echo "    return response()->json(\n";
echo "        \$dto->withVisibilityContext(\$context)->toArray()\n";
echo "    );\n";
echo "}\n";
echo "```\n\n";

echo "→ Der gleiche Endpoint gibt unterschiedliche Daten zurück,\n";
echo "  je nachdem wer eingeloggt ist!\n\n";

// ============================================================================
// ZUSAMMENFASSUNG
// ============================================================================

echo str_repeat('=', 70) . "\n";
echo "ZUSAMMENFASSUNG:\n";
echo str_repeat('=', 70) . "\n\n";

echo "✅  Context-Based Visibility ermöglicht:\n\n";
echo "  1. Unterschiedliche Ansichten für unterschiedliche User\n";
echo "  2. Sichere API-Endpoints ohne separate DTOs\n";
echo "  3. Flexible Permissions-Logik\n";
echo "  4. Zugriff auf eigene Daten vs. fremde Daten\n";
echo "  5. Role-Based Access Control (RBAC)\n";
echo "  6. Kombinierbar mit only() und except()\n\n";

echo "✅  Vorteile:\n\n";
echo "  - Ein DTO für alle Use-Cases\n";
echo "  - Keine Duplikation von Code\n";
echo "  - Klare Permissions-Logik in einer Methode\n";
echo "  - Testbar und wartbar\n";
echo "  - Type-Safe durch PHP 8.2+\n\n";

echo "✅  Beispiel abgeschlossen!\n";

