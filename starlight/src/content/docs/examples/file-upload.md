---
title: File Upload Examples
description: Examples for handling file uploads
---

Examples for handling file uploads.

## Introduction

Common file upload patterns:

- ✅ **Image Upload** - Avatar, profile pictures
- ✅ **Document Upload** - PDF, Word, Excel
- ✅ **Multiple Files** - Multiple file uploads
- ✅ **Validation** - File type, size, dimensions

## Avatar Upload

```php
class UploadAvatarDTO extends SimpleDTO
{
    public function __construct(
        #[Required, File, Image, MaxFileSize(2048), Dimensions(['min_width' => 100, 'min_height' => 100])]
        public readonly UploadedFile $avatar,
    ) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dto = UploadAvatarDTO::validateAndCreate($_FILES);
        
        $path = $dto->avatar->store('avatars', 'public');
        
        auth()->user()->update(['avatar' => $path]);
        
        back()->with('success', 'Avatar uploaded!');
    } catch (ValidationException $e) {
        back()->withErrors($e->getErrors());
    }
}
```

## Document Upload

```php
class UploadDocumentDTO extends SimpleDTO
{
    public function __construct(
        #[Required, File, Mimes(['pdf', 'doc', 'docx']), MaxFileSize(10240)]
        public readonly UploadedFile $document,
        
        #[Required, Min(3)]
        public readonly string $title,
        
        public readonly ?string $description = null,
    ) {}
}

$dto = UploadDocumentDTO::validateAndCreate(array_merge($_POST, $_FILES));

$path = $dto->document->store('documents', 'private');

Document::create([
    'title' => $dto->title,
    'description' => $dto->description,
    'path' => $path,
    'user_id' => auth()->id(),
]);
```

## Multiple Files

```php
class UploadImagesDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly array $images,
    ) {}
    
    public function validate(): void
    {
        foreach ($this->images as $image) {
            if (!$image instanceof UploadedFile) {
                throw new ValidationException(['images' => 'Invalid file']);
            }
            
            if (!$image->isValid()) {
                throw new ValidationException(['images' => 'File upload failed']);
            }
            
            if ($image->getSize() > 2048 * 1024) {
                throw new ValidationException(['images' => 'File too large']);
            }
        }
    }
}

$dto = UploadImagesDTO::fromArray($_FILES);
$dto->validate();

$paths = [];
foreach ($dto->images as $image) {
    $paths[] = $image->store('images', 'public');
}

Post::create([
    'title' => $_POST['title'],
    'images' => $paths,
]);
```

## Image with Thumbnail

```php
class UploadImageDTO extends SimpleDTO
{
    public function __construct(
        #[Required, File, Image, MaxFileSize(5120)]
        public readonly UploadedFile $image,
    ) {}
}

$dto = UploadImageDTO::validateAndCreate($_FILES);

// Store original
$path = $dto->image->store('images', 'public');

// Create thumbnail
$thumbnail = Image::make($dto->image)
    ->fit(200, 200)
    ->encode('jpg', 80);

$thumbnailPath = 'thumbnails/' . basename($path);
Storage::disk('public')->put($thumbnailPath, $thumbnail);

Product::create([
    'image' => $path,
    'thumbnail' => $thumbnailPath,
]);
```

## CSV Import

```php
class ImportCsvDTO extends SimpleDTO
{
    public function __construct(
        #[Required, File, Mimes(['csv', 'txt']), MaxFileSize(10240)]
        public readonly UploadedFile $file,
    ) {}
}

$dto = ImportCsvDTO::validateAndCreate($_FILES);

$handle = fopen($dto->file->getRealPath(), 'r');
$header = fgetcsv($handle);

while (($row = fgetcsv($handle)) !== false) {
    $data = array_combine($header, $row);
    
    User::create([
        'name' => $data['name'],
        'email' => $data['email'],
    ]);
}

fclose($handle);
```

## File Download

```php
class DownloadFileDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Exists('documents', 'id')]
        public readonly int $documentId,
    ) {}
}

$dto = DownloadFileDTO::validateAndCreate($_GET);

$document = Document::find($dto->documentId);

// Check permissions
if (!auth()->user()->can('download', $document)) {
    abort(403);
}

return Storage::download($document->path, $document->title);
```

## See Also

- [Validation](/simple-dto/validation/) - Validation rules
- [Form Processing](/examples/form-processing/) - Form examples
- [Database Operations](/examples/database-operations/) - Database examples

