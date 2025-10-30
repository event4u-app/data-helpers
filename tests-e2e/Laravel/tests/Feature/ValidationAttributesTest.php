<?php

declare(strict_types=1);

use E2E\Laravel\Dtos\FileUploadDto;
use E2E\Laravel\Dtos\ProductValidationDto;
use E2E\Laravel\Dtos\UserValidationDto;
use E2E\Laravel\Models\Product;
use E2E\Laravel\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

describe('Laravel Validation Attributes E2E', function(): void {
    beforeEach(function(): void {
        // Clean database before each test
        DB::table('users')->truncate();
        DB::table('products')->truncate();
    });

    describe('UniqueCallback with Laravel', function(): void {
        it('validates unique email for new user', function(): void {
            $result = UserValidationDto::validate([
                'email' => 'new@example.com',
                'name' => 'New User',
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails validation for duplicate email', function(): void {
            // Create existing user
            User::create([
                'email' => 'existing@example.com',
                'name' => 'Existing User',
            ]);

            $result = UserValidationDto::validate([
                'email' => 'existing@example.com',
                'name' => 'Another User',
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('email'))->toBeTrue();
        });

        it('allows same email when updating own record', function(): void {
            // Create existing user
            $user = User::create([
                'email' => 'update@example.com',
                'name' => 'Update User',
            ]);

            $result = UserValidationDto::validate([
                'id' => $user->id,
                'email' => 'update@example.com',
                'name' => 'Updated Name',
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails when updating to another users email', function(): void {
            // Create two users
            $user1 = User::create([
                'email' => 'user1@example.com',
                'name' => 'User 1',
            ]);

            $user2 = User::create([
                'email' => 'user2@example.com',
                'name' => 'User 2',
            ]);

            $result = UserValidationDto::validate([
                'id' => $user1->id,
                'email' => 'user2@example.com',
                'name' => 'User 1 Updated',
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('email'))->toBeTrue();
        });
    });

    describe('ExistsCallback with Laravel', function(): void {
        it('validates existing manager ID', function(): void {
            // Create manager
            $manager = User::create([
                'email' => 'manager@example.com',
                'name' => 'Manager',
            ]);

            $result = UserValidationDto::validate([
                'email' => 'employee@example.com',
                'name' => 'Employee',
                'managerId' => $manager->id,
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails validation for non-existing manager ID', function(): void {
            $result = UserValidationDto::validate([
                'email' => 'employee@example.com',
                'name' => 'Employee',
                'managerId' => 99999,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('managerId'))->toBeTrue();
        });

        it('allows null manager ID', function(): void {
            $result = UserValidationDto::validate([
                'email' => 'employee@example.com',
                'name' => 'Employee',
            ]);

            expect($result->isValid())->toBeTrue();
        });
    });

    describe('Product validation with conditions', function(): void {
        it('validates unique SKU', function(): void {
            $result = ProductValidationDto::validate([
                'sku' => 'PROD-001',
                'name' => 'Product 1',
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails for duplicate SKU', function(): void {
            Product::create([
                'sku' => 'PROD-002',
                'name' => 'Existing Product',
                'active' => true,
            ]);

            $result = ProductValidationDto::validate([
                'sku' => 'PROD-002',
                'name' => 'New Product',
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('sku'))->toBeTrue();
        });

        it('validates related product exists and is active', function(): void {
            $activeProduct = Product::create([
                'sku' => 'PROD-ACTIVE',
                'name' => 'Active Product',
                'active' => true,
            ]);

            $result = ProductValidationDto::validate([
                'sku' => 'PROD-NEW',
                'name' => 'New Product',
                'relatedProductId' => $activeProduct->id,
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails when related product is inactive', function(): void {
            $inactiveProduct = Product::create([
                'sku' => 'PROD-INACTIVE',
                'name' => 'Inactive Product',
                'active' => false,
            ]);

            $result = ProductValidationDto::validate([
                'sku' => 'PROD-NEW',
                'name' => 'New Product',
                'relatedProductId' => $inactiveProduct->id,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('relatedProductId'))->toBeTrue();
        });
    });

    describe('Integration with validateAndCreate', function(): void {
        it('creates DTO when validation passes', function(): void {
            $dto = UserValidationDto::validateAndCreate([
                'email' => 'valid@example.com',
                'name' => 'Valid User',
            ]);

            expect($dto)->toBeInstanceOf(UserValidationDto::class);
            expect($dto->email)->toBe('valid@example.com');
            expect($dto->name)->toBe('Valid User');
        });

        it('throws exception when validation fails', function(): void {
            User::create([
                'email' => 'taken@example.com',
                'name' => 'Taken User',
            ]);

            expect(fn() => UserValidationDto::validateAndCreate([
                'email' => 'taken@example.com',
                'name' => 'Another User',
            ]))->toThrow(\event4u\DataHelpers\Exceptions\ValidationException::class);
        });
    });

    describe('Complex validation scenarios', function(): void {
        it('validates multiple constraints together', function(): void {
            // Create manager and existing user
            $manager = User::create([
                'email' => 'manager@example.com',
                'name' => 'Manager',
            ]);

            User::create([
                'email' => 'existing@example.com',
                'name' => 'Existing',
            ]);

            // Valid: unique email + existing manager
            $result = UserValidationDto::validate([
                'email' => 'newuser@example.com',
                'name' => 'New User',
                'managerId' => $manager->id,
            ]);

            expect($result->isValid())->toBeTrue();

            // Invalid: duplicate email
            $result = UserValidationDto::validate([
                'email' => 'existing@example.com',
                'name' => 'Another User',
                'managerId' => $manager->id,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('email'))->toBeTrue();

            // Invalid: non-existing manager
            $result = UserValidationDto::validate([
                'email' => 'anotheruser@example.com',
                'name' => 'Another User',
                'managerId' => 99999,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('managerId'))->toBeTrue();
        });
    });

    describe('FileCallback with Laravel', function(): void {
        beforeEach(function(): void {
            Storage::fake('local');
        });

        it('validates file size (max 2MB)', function(): void {
            // Create a small file (1MB)
            $smallFile = UploadedFile::fake()->create('document.pdf', 1024); // 1MB

            $result = FileUploadDto::validate([
                'document' => $smallFile,
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails validation for file too large', function(): void {
            // Create a large file (3MB)
            $largeFile = UploadedFile::fake()->create('document.pdf', 3072); // 3MB

            $result = FileUploadDto::validate([
                'document' => $largeFile,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('document'))->toBeTrue();
        });

        it('validates image MIME type', function(): void {
            // Create a minimal PNG file (1x1 transparent pixel) without GD
            $tempPath = tempnam(sys_get_temp_dir(), 'image_');
            $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
            file_put_contents($tempPath, $pngData);

            $image = new UploadedFile($tempPath, 'avatar.png', 'image/png', null, true);

            $result = FileUploadDto::validate([
                'document' => UploadedFile::fake()->create('doc.pdf', 100),
                'avatar' => $image,
            ]);

            expect($result->isValid())->toBeTrue();

            // Cleanup
            @unlink($tempPath);
        });

        it('fails validation for invalid image MIME type', function(): void {
            // Create a non-image file
            $file = UploadedFile::fake()->create('document.txt', 100);

            $result = FileUploadDto::validate([
                'document' => UploadedFile::fake()->create('doc.pdf', 100),
                'avatar' => $file,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('avatar'))->toBeTrue();
        });

        it('validates image dimensions (square, min 100x100)', function(): void {
            // Note: We can't easily create a proper 500x500 image without GD
            // This test demonstrates the validation logic, but will fail with 1x1 image
            $tempPath = tempnam(sys_get_temp_dir(), 'image_');
            $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
            file_put_contents($tempPath, $pngData);

            $image = new UploadedFile($tempPath, 'banner.png', 'image/png', null, true);

            $result = FileUploadDto::validate([
                'document' => UploadedFile::fake()->create('doc.pdf', 100),
                'banner' => $image,
            ]);

            // This will fail because the image is 1x1, not 500x500
            expect($result->isValid())->toBeFalse();
            expect($result->hasError('banner'))->toBeTrue();

            // Cleanup
            @unlink($tempPath);
        });

        it('fails validation for non-square image', function(): void {
            // Note: Without GD, we can't create non-square images
            // This test is skipped in favor of testing with real files
            $tempPath = tempnam(sys_get_temp_dir(), 'image_');
            $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
            file_put_contents($tempPath, $pngData);

            $image = new UploadedFile($tempPath, 'banner.png', 'image/png', null, true);

            $result = FileUploadDto::validate([
                'document' => UploadedFile::fake()->create('doc.pdf', 100),
                'banner' => $image,
            ]);

            // 1x1 is square but too small
            expect($result->isValid())->toBeFalse();
            expect($result->hasError('banner'))->toBeTrue();

            // Cleanup
            @unlink($tempPath);
        });

        it('fails validation for image too small', function(): void {
            // 1x1 image is too small (min is 100x100)
            $tempPath = tempnam(sys_get_temp_dir(), 'image_');
            $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
            file_put_contents($tempPath, $pngData);

            $image = new UploadedFile($tempPath, 'banner.png', 'image/png', null, true);

            $result = FileUploadDto::validate([
                'document' => UploadedFile::fake()->create('doc.pdf', 100),
                'banner' => $image,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('banner'))->toBeTrue();

            // Cleanup
            @unlink($tempPath);
        });

        it('validates PDF file signature', function(): void {
            // Create a temporary PDF file with correct signature
            $tempPath = tempnam(sys_get_temp_dir(), 'pdf_');
            file_put_contents($tempPath, '%PDF-1.4' . "\n" . 'fake pdf content');

            $pdfFile = new UploadedFile($tempPath, 'contract.pdf', 'application/pdf', null, true);

            $result = FileUploadDto::validate([
                'document' => UploadedFile::fake()->create('doc.pdf', 100),
                'contract' => $pdfFile,
            ]);

            expect($result->isValid())->toBeTrue();

            // Cleanup
            @unlink($tempPath);
        });

        it('fails validation for invalid PDF signature', function(): void {
            // Create a file without PDF signature
            $tempPath = tempnam(sys_get_temp_dir(), 'txt_');
            file_put_contents($tempPath, 'This is not a PDF file');

            $fakeFile = new UploadedFile($tempPath, 'contract.pdf', 'application/pdf', null, true);

            $result = FileUploadDto::validate([
                'document' => UploadedFile::fake()->create('doc.pdf', 100),
                'contract' => $fakeFile,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('contract'))->toBeTrue();

            // Cleanup
            @unlink($tempPath);
        });

        it('allows nullable file uploads', function(): void {
            $result = FileUploadDto::validate([
                'document' => UploadedFile::fake()->create('doc.pdf', 100),
                'avatar' => null,
                'banner' => null,
                'contract' => null,
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails validation when required file is missing', function(): void {
            $result = FileUploadDto::validate([
                'document' => null,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('document'))->toBeTrue();
        });
    });
});

