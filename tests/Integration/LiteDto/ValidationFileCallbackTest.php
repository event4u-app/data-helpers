<?php

declare(strict_types=1);

namespace Tests\Integration\LiteDto;

use event4u\DataHelpers\LiteDto\Attributes\Validation\FileCallback;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Required;
use event4u\DataHelpers\LiteDto\LiteDto;

beforeEach(function(): void {
    // Create temporary directory for test files
    $this->tempDir = sys_get_temp_dir().'/litedto_file_tests_'.uniqid();
    mkdir($this->tempDir, 0777, true);
});

afterEach(function(): void {
    // Clean up temporary files
    if (property_exists($this, 'tempDir') && null !== $this->tempDir && is_dir($this->tempDir)) {
        $files = glob($this->tempDir.'/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
    }
});

it('validates file size with callback', function(): void {
    $dto = new class() extends LiteDto {
        public function __construct(
            #[FileCallback([self::class, 'validateFileSize'])]
            public readonly array $file = [],
        ) {}

        public static function validateFileSize(mixed $value, string $propertyName): bool
        {
            if (! is_array($value) || ! isset($value['size'])) {
                return false;
            }

            // Max 1MB
            return 1024 * 1024 >= $value['size'];
        }
    };

    // Valid file (500KB)
    $result = $dto::validate([
        'file' => [
            'name' => 'test.txt',
            'size' => 500 * 1024,
            'tmp_name' => '/tmp/test',
            'error' => UPLOAD_ERR_OK,
        ],
    ]);

    expect($result->isValid())->toBeTrue();

    // Invalid file (2MB)
    $result = $dto::validate([
        'file' => [
            'name' => 'large.txt',
            'size' => 2 * 1024 * 1024,
            'tmp_name' => '/tmp/large',
            'error' => UPLOAD_ERR_OK,
        ],
    ]);

    expect($result->isValid())->toBeFalse();
    expect($result->hasError('file'))->toBeTrue();
});

it('validates image MIME type with callback', function(): void {
    // Create a minimal PNG file (1x1 transparent pixel)
    $imagePath = $this->tempDir.'/test.png';
    $pngData = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
    );
    file_put_contents($imagePath, $pngData);

    $dto = new class() extends LiteDto {
        public function __construct(
            #[FileCallback([self::class, 'validateImage'])]
            public readonly array $image = [],
        ) {}

        public static function validateImage(mixed $value, string $propertyName): bool
        {
            if (! is_array($value) || ! isset($value['tmp_name'])) {
                return false;
            }

            if (! file_exists($value['tmp_name'])) {
                return false;
            }

            // Check MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $value['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            return in_array($mimeType, $allowedMimes, true);
        }
    };

    // Valid image
    $result = $dto::validate([
        'image' => [
            'name' => 'test.png',
            'tmp_name' => $imagePath,
            'size' => filesize($imagePath),
            'error' => UPLOAD_ERR_OK,
        ],
    ]);

    expect($result->isValid())->toBeTrue();
});

it('validates image dimensions with callback', function(): void {
    // Create a minimal 1x1 PNG (valid but too small)
    $smallImagePath = $this->tempDir.'/small.png';
    $pngData = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
    );
    file_put_contents($smallImagePath, $pngData);

    $dto = new class() extends LiteDto {
        public function __construct(
            #[FileCallback([self::class, 'validateAvatar'])]
            public readonly array $avatar = [],
        ) {}

        public static function validateAvatar(mixed $value, string $propertyName): bool
        {
            if (! is_array($value) || ! isset($value['tmp_name'])) {
                return false;
            }

            if (! file_exists($value['tmp_name'])) {
                return false;
            }

            // Check if it's a valid image
            $imageInfo = getimagesize($value['tmp_name']);
            if (false === $imageInfo) {
                return false;
            }

            [$width, $height] = $imageInfo;

            // Avatar must be square and between 100x100 and 500x500
            if ($width !== $height) {
                return false;
            }
            return 100 <= $width && 500 >= $width;
        }
    };

    // Invalid avatar (1x1 - too small)
    $result = $dto::validate([
        'avatar' => [
            'name' => 'small.png',
            'tmp_name' => $smallImagePath,
            'size' => filesize($smallImagePath),
            'error' => UPLOAD_ERR_OK,
        ],
    ]);

    expect($result->isValid())->toBeFalse();
    expect($result->hasError('avatar'))->toBeTrue();
});

it('validates PDF file signature with callback', function(): void {
    // Create a fake PDF file with correct signature
    $pdfPath = $this->tempDir.'/document.pdf';
    file_put_contents($pdfPath, '%PDF-1.4'."\n".'fake pdf content');

    $dto = new class() extends LiteDto {
        public function __construct(
            #[FileCallback([self::class, 'validatePdf'])]
            public readonly array $document = [],
        ) {}

        public static function validatePdf(mixed $value, string $propertyName): bool
        {
            if (! is_array($value) || ! isset($value['tmp_name'])) {
                return false;
            }

            if (! file_exists($value['tmp_name'])) {
                return false;
            }

            // Check MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $value['tmp_name']);
            finfo_close($finfo);

            // Note: finfo might not detect our fake PDF correctly, so we also check signature
            $handle = fopen($value['tmp_name'], 'r');
            $header = fread($handle, 5);
            fclose($handle);

            return '%PDF-' === $header;
        }
    };

    // Valid PDF
    $result = $dto::validate([
        'document' => [
            'name' => 'document.pdf',
            'tmp_name' => $pdfPath,
            'size' => filesize($pdfPath),
            'error' => UPLOAD_ERR_OK,
        ],
    ]);

    expect($result->isValid())->toBeTrue();

    // Invalid PDF (wrong signature)
    $invalidPath = $this->tempDir.'/fake.pdf';
    file_put_contents($invalidPath, 'not a pdf');

    $result = $dto::validate([
        'document' => [
            'name' => 'fake.pdf',
            'tmp_name' => $invalidPath,
            'size' => filesize($invalidPath),
            'error' => UPLOAD_ERR_OK,
        ],
    ]);

    expect($result->isValid())->toBeFalse();
    expect($result->hasError('document'))->toBeTrue();
});

it('validates multiple MIME types with callback', function(): void {
    // Create test files
    $imagePath = $this->tempDir.'/image.png';
    $pngData = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
    );
    file_put_contents($imagePath, $pngData);

    $textPath = $this->tempDir.'/text.txt';
    file_put_contents($textPath, 'Hello World');

    $dto = new class() extends LiteDto {
        public function __construct(
            #[FileCallback([self::class, 'validateMedia'])]
            public readonly array $file = [],
        ) {}

        public static function validateMedia(mixed $value, string $propertyName): bool
        {
            if (! is_array($value) || ! isset($value['tmp_name'])) {
                return false;
            }

            if (! file_exists($value['tmp_name'])) {
                return false;
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $value['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'text/plain',
                'application/pdf',
            ];

            return in_array($mimeType, $allowedMimes, true);
        }
    };

    // Valid image
    $result = $dto::validate([
        'file' => [
            'name' => 'image.png',
            'tmp_name' => $imagePath,
            'size' => filesize($imagePath),
            'error' => UPLOAD_ERR_OK,
        ],
    ]);

    expect($result->isValid())->toBeTrue();

    // Valid text file
    $result = $dto::validate([
        'file' => [
            'name' => 'text.txt',
            'tmp_name' => $textPath,
            'size' => filesize($textPath),
            'error' => UPLOAD_ERR_OK,
        ],
    ]);

    expect($result->isValid())->toBeTrue();
});

it('allows null file when not required', function(): void {
    $dto = new class() extends LiteDto {
        public function __construct(
            #[FileCallback([self::class, 'validateFile'])]
            public readonly ?array $file = null,
        ) {}

        public static function validateFile(mixed $value, string $propertyName): bool
        {
            if (null === $value) {
                return true;
            }

            return is_array($value) && isset($value['tmp_name']);
        }
    };

    // Null is valid
    $result = $dto::validate(['file' => null]);
    expect($result->isValid())->toBeTrue();

    // Empty array is invalid
    $result = $dto::validate(['file' => []]);
    expect($result->isValid())->toBeFalse();
});

it('requires file when marked as required', function(): void {
    $dto = new class() extends LiteDto {
        public function __construct(
            #[Required]
            #[FileCallback([self::class, 'validateFile'])]
            public readonly array $file = [],
        ) {}

        public static function validateFile(mixed $value, string $propertyName): bool
        {
            return is_array($value) && isset($value['tmp_name']);
        }
    };

    // Missing file
    $result = $dto::validate([]);
    expect($result->isValid())->toBeFalse();
    expect($result->hasError('file'))->toBeTrue();
});
