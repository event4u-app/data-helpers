---
title: FileCallback Attribute
description: Custom file validation with callback functions
---

The `#[FileCallback]` attribute allows you to implement **custom file validation logic** that works in LiteDto, including Plain PHP environments without framework dependencies.

## Overview

Unlike `#[File]`, `#[Image]`, `#[Mimes]`, and `#[MimeTypes]` which are marker attributes for Laravel/Symfony validators, `#[FileCallback]` performs actual validation in LiteDto using your custom callback function.

## Syntax

```php
#[FileCallback(array [ClassName::class, 'methodName'])]
```

**Parameters:**
- `$callback` - Callable array `[ClassName::class, 'methodName']` that performs the file validation

## Callback Signature

```php
public static function callbackName(
    mixed $value,           // The file data (array or object)
    string $propertyName    // The property name
): bool
```

**Returns:** `true` if file is valid, `false` otherwise

## Basic Usage

### With PHP Upload Array

```php
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\Validation\FileCallback;

class UploadDto extends LiteDto
{
    public function __construct(
        #[FileCallback([self::class, 'validateFile'])]
        public readonly array $file,

        public readonly string $title,
    ) {}

    public static function validateFile(mixed $value, string $propertyName): bool
    {
        // Check if it's a valid upload array
        if (!is_array($value) || !isset($value['tmp_name'], $value['size'], $value['error'])) {
            return false;
        }

        // Check for upload errors
        if ($value['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        // Check if file was actually uploaded
        if (!is_uploaded_file($value['tmp_name'])) {
            return false;
        }

        // Check file size (2MB max)
        if ($value['size'] > 2 * 1024 * 1024) {
            return false;
        }

        return true;
    }
}

// Usage
$result = UploadDto::validate([
    'file' => $_FILES['upload'],
    'title' => 'My Document'
]);

if ($result->isValid()) {
    $upload = UploadDto::validateAndCreate([
        'file' => $_FILES['upload'],
        'title' => 'My Document'
    ]);
}
```

### Image Validation

```php
class ImageUploadDto extends LiteDto
{
    public function __construct(
        #[FileCallback([self::class, 'validateImage'])]
        public readonly array $image,

        public readonly string $alt,
    ) {}

    public static function validateImage(mixed $value, string $propertyName): bool
    {
        if (!is_array($value) || !isset($value['tmp_name'])) {
            return false;
        }

        // Check if file is uploaded
        if (!is_uploaded_file($value['tmp_name'])) {
            return false;
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $value['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes, true)) {
            return false;
        }

        // Check file size (5MB max)
        if ($value['size'] > 5 * 1024 * 1024) {
            return false;
        }

        // Verify it's a valid image
        $imageInfo = getimagesize($value['tmp_name']);
        if ($imageInfo === false) {
            return false;
        }

        return true;
    }
}
```

### PDF Validation

```php
class DocumentDto extends LiteDto
{
    public function __construct(
        #[FileCallback([self::class, 'validatePdf'])]
        public readonly array $document,

        public readonly string $name,
    ) {}

    public static function validatePdf(mixed $value, string $propertyName): bool
    {
        if (!is_array($value) || !isset($value['tmp_name'])) {
            return false;
        }

        // Check if file is uploaded
        if (!is_uploaded_file($value['tmp_name'])) {
            return false;
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $value['tmp_name']);
        finfo_close($finfo);

        if ($mimeType !== 'application/pdf') {
            return false;
        }

        // Check file size (10MB max)
        if ($value['size'] > 10 * 1024 * 1024) {
            return false;
        }

        // Verify PDF signature
        $handle = fopen($value['tmp_name'], 'r');
        $header = fread($handle, 5);
        fclose($handle);

        if ($header !== '%PDF-') {
            return false;
        }

        return true;
    }
}
```

## Advanced Usage

### Multiple File Types

```php
class MediaDto extends LiteDto
{
    public function __construct(
        #[FileCallback([self::class, 'validateMedia'])]
        public readonly array $file,

        public readonly string $type, // 'image', 'video', 'document'
    ) {}

    public static function validateMedia(mixed $value, string $propertyName): bool
    {
        if (!is_array($value) || !isset($value['tmp_name'])) {
            return false;
        }

        if (!is_uploaded_file($value['tmp_name'])) {
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $value['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'video/mpeg', 'video/quicktime',
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        return in_array($mimeType, $allowedMimes, true);
    }
}
```

### File Dimensions Validation

```php
class AvatarDto extends LiteDto
{
    public function __construct(
        #[FileCallback([self::class, 'validateAvatar'])]
        public readonly array $avatar,
    ) {}

    public static function validateAvatar(mixed $value, string $propertyName): bool
    {
        if (!is_array($value) || !isset($value['tmp_name'])) {
            return false;
        }

        if (!is_uploaded_file($value['tmp_name'])) {
            return false;
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $value['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
            return false;
        }

        // Check dimensions
        $imageInfo = getimagesize($value['tmp_name']);
        if ($imageInfo === false) {
            return false;
        }

        [$width, $height] = $imageInfo;

        // Avatar must be square and between 100x100 and 1000x1000
        if ($width !== $height) {
            return false;
        }

        if ($width < 100 || $width > 1000) {
            return false;
        }

        return true;
    }
}
```

### Nullable File Upload

```php
class ProfileDto extends LiteDto
{
    public function __construct(
        public readonly string $name,

        #[FileCallback([self::class, 'validateAvatar'])]
        public readonly ?array $avatar = null,
    ) {}

    public static function validateAvatar(mixed $value, string $propertyName): bool
    {
        // Null is automatically allowed by the callback attribute
        if ($value === null) {
            return true;
        }

        if (!is_array($value) || !isset($value['tmp_name'])) {
            return false;
        }

        // Rest of validation...
        return is_uploaded_file($value['tmp_name']);
    }
}
```

## Important Notes

### Null Handling

The callback is **automatically skipped** when the value is `null`. Use `#[Required]` if you want to enforce file upload:

```php
#[Required]
#[FileCallback([self::class, 'validateFile'])]
public readonly array $file;
```

### Error Messages

Default error message: `"The {property} must be a valid file."`

Custom error messages are not yet supported for callback attributes.

### Security Considerations

- **Always validate MIME types** using `finfo_file()`, not just file extensions
- **Check file signatures** (magic bytes) for critical file types
- **Limit file sizes** to prevent DoS attacks
- **Sanitize file names** before storing
- **Store uploaded files outside the web root**
- **Use virus scanning** for production environments

### Performance Considerations

- File validation can be slow for large files
- Consider async validation for better UX
- Use streaming validation for very large files

## Comparison with Framework Attributes

| Feature | #[File] / #[Image] | #[FileCallback] |
|---------|-------------------|-----------------|
| **Works in Plain PHP** | ❌ No | ✅ Yes |
| **Works in Laravel** | ✅ Yes | ✅ Yes |
| **Works in Symfony** | ✅ Yes | ✅ Yes |
| **Validation Location** | Framework validator | LiteDto |
| **Custom Logic** | ❌ No | ✅ Yes |
| **MIME Type Check** | ✅ Yes | ✅ Yes (manual) |
| **Dimension Check** | ✅ Yes (Laravel) | ✅ Yes (manual) |

## See Also

- [UniqueCallback](/data-helpers/attributes/validation/unique-callback/) - Check if value is unique
- [ExistsCallback](/data-helpers/attributes/validation/exists-callback/) - Check if value exists
- [Validation Attributes](/data-helpers/attributes/validation/) - All validation attributes
- [LiteDto Validation](/data-helpers/lite-dto/validation/) - Validation guide

