<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Max;
use event4u\DataHelpers\SimpleDTO\Attributes\Min;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\ValidateRequest;
use event4u\DataHelpers\Validation\HtmlErrorFormatter;
use event4u\DataHelpers\Validation\ValidationException;

echo "=================================================================\n";
echo "HTML ERROR RESPONSES FOR FORMS\n";
echo "=================================================================\n\n";

// Example DTO with validation
#[ValidateRequest(throw: true)]
class ContactFormDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Min(2)]
        public readonly string $name,

        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(10)]
        #[Max(1000)]
        public readonly string $message,
    ) {}
}

// Invalid data for testing
$invalidData = [
    'name' => 'A',  // Too short
    'email' => 'invalid-email',  // Invalid format
    'message' => 'Short',  // Too short
];

try {
    $dto = ContactFormDTO::validateAndCreate($invalidData);
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    echo "1. BOOTSTRAP 5 ALERT:\n";
    echo "------------------------------------------------------------\n";
    /** @phpstan-ignore-next-line unknown */
    echo HtmlErrorFormatter::bootstrap($validationException);
    echo "\n\n";

    echo "2. TAILWIND CSS ALERT:\n";
    echo "------------------------------------------------------------\n";
    /** @phpstan-ignore-next-line unknown */
    echo HtmlErrorFormatter::tailwind($validationException);
    echo "\n\n";

    echo "3. SIMPLE HTML LIST:\n";
    echo "------------------------------------------------------------\n";
    /** @phpstan-ignore-next-line unknown */
    echo HtmlErrorFormatter::simple($validationException);
    echo "\n\n";

    echo "4. INLINE FIELD ERRORS (Bootstrap):\n";
    echo "------------------------------------------------------------\n";
    /** @phpstan-ignore-next-line unknown */
    $fieldErrors = HtmlErrorFormatter::bootstrapFields($validationException);
    foreach ($fieldErrors as $field => $html) {
        echo "Field '{$field}':\n";
        echo $html . "\n";
    }
    echo "\n";

    echo "5. INLINE FIELD ERRORS (Tailwind):\n";
    echo "------------------------------------------------------------\n";
    /** @phpstan-ignore-next-line unknown */
    $fieldErrors = HtmlErrorFormatter::tailwindFields($validationException);
    foreach ($fieldErrors as $field => $html) {
        echo "Field '{$field}':\n";
        echo $html . "\n";
    }
    echo "\n";

    echo "6. SINGLE FIELD ERROR:\n";
    echo "------------------------------------------------------------\n";
    echo "Email field error:\n";
    /** @phpstan-ignore-next-line unknown */
    echo HtmlErrorFormatter::firstField($validationException, 'email');
    echo "\n\n";

    echo "7. FIELD CSS CLASSES:\n";
    echo "------------------------------------------------------------\n";
    /** @phpstan-ignore-next-line unknown */
    echo "Name field class: " . HtmlErrorFormatter::fieldClass($validationException, 'name') . "\n";
    /** @phpstan-ignore-next-line unknown */
    echo "Email field class: " . HtmlErrorFormatter::fieldClass($validationException, 'email') . "\n";
    echo "Valid field class: " . HtmlErrorFormatter::fieldClass(
        /** @phpstan-ignore-next-line unknown */
        $validationException,
        'phone',
        'is-invalid',
        'is-valid'
    ) . "\n";
    echo "\n";

    echo "8. JSON RESPONSE (for AJAX):\n";
    echo "------------------------------------------------------------\n";
    /** @phpstan-ignore-next-line unknown */
    echo HtmlErrorFormatter::json($validationException, JSON_PRETTY_PRINT);
    echo "\n\n";
}

echo "9. COMPLETE BOOTSTRAP 5 FORM EXAMPLE:\n";
echo "------------------------------------------------------------\n";
echo <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Contact Form</h2>

        <!-- Global error alert -->
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Validation Error!</strong> Please correct the following errors:
            <ul class="mb-0 mt-2">
                <li>The name field must be at least 2 characters.</li>
                <li>The email must be a valid email address.</li>
                <li>The message field must be at least 10 characters.</li>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <form method="POST">
            <!-- Name field with inline error -->
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control is-invalid" id="name" name="name" value="A">
                <div class="invalid-feedback d-block">
                    The name field must be at least 2 characters.
                </div>
            </div>

            <!-- Email field with inline error -->
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control is-invalid" id="email" name="email" value="invalid-email">
                <div class="invalid-feedback d-block">
                    The email must be a valid email address.
                </div>
            </div>

            <!-- Message field with inline error -->
            <div class="mb-3">
                <label for="message" class="form-label">Message</label>
                <textarea class="form-control is-invalid" id="message" name="message" rows="3">Short</textarea>
                <div class="invalid-feedback d-block">
                    The message field must be at least 10 characters.
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
</body>
</html>
HTML;
echo "\n\n";

echo "10. COMPLETE TAILWIND CSS FORM EXAMPLE:\n";
echo "------------------------------------------------------------\n";
echo <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <h2 class="text-2xl font-bold mb-4">Contact Form</h2>

        <!-- Global error alert -->
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Validation Error!</strong>
            <span class="block sm:inline"> Please correct the following errors:</span>
            <ul class="mt-2 list-disc list-inside">
                <li>The name field must be at least 2 characters.</li>
                <li>The email must be a valid email address.</li>
                <li>The message field must be at least 10 characters.</li>
            </ul>
        </div>

        <form method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8">
            <!-- Name field with inline error -->
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                    Name
                </label>
                <input class="shadow appearance-none border border-red-500 rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                       id="name" type="text" name="name" value="A">
                <p class="text-red-600 text-sm mt-1">
                    The name field must be at least 2 characters.
                </p>
            </div>

            <!-- Email field with inline error -->
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                    Email
                </label>
                <input class="shadow appearance-none border border-red-500 rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                       id="email" type="email" name="email" value="invalid-email">
                <p class="text-red-600 text-sm mt-1">
                    The email must be a valid email address.
                </p>
            </div>

            <!-- Message field with inline error -->
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="message">
                    Message
                </label>
                <textarea class="shadow appearance-none border border-red-500 rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                          id="message" name="message" rows="3">Short</textarea>
                <p class="text-red-600 text-sm mt-1">
                    The message field must be at least 10 characters.
                </p>
            </div>

            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                    type="submit">
                Submit
            </button>
        </form>
    </div>
</body>
</html>
HTML;
echo "\n\n";

echo "=================================================================\n";
echo "✅  All HTML error response examples completed successfully!\n";
echo "=================================================================\n";
