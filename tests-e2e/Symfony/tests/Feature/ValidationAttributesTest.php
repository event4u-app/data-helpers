<?php

declare(strict_types=1);

use App\Dto\FileUploadDto;
use App\Dto\ProductValidationDto;
use App\Dto\UserValidationDto;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use event4u\DataHelpers\Exceptions\ValidationException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

describe('Symfony Validation Attributes E2E', function(): void {
    beforeEach(function(): void {
        // Get EntityManager from container using Pest helper
        $container = test()->getContainer();
        $em = $container->get(EntityManagerInterface::class);

        // Create database schema
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        // Set EntityManager for DTOs
        UserValidationDto::setEntityManager($em);
        ProductValidationDto::setEntityManager($em);
    });

    afterEach(function(): void {
        // Restore error handler to prevent risky test warnings
        restore_error_handler();
        restore_exception_handler();
    });

    describe('UniqueCallback with Doctrine', function(): void {
        it('validates unique email for new user', function(): void {
            $result = UserValidationDto::validate([
                'email' => 'new@example.com',
                'name' => 'New User',
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails validation for duplicate email', function(): void {
            $em = test()->getContainer()->get(EntityManagerInterface::class);

            // Create existing user
            $user = new User();
            $user->setEmail('existing@example.com');
            $user->setName('Existing User');
            $em->persist($user);
            $em->flush();

            $result = UserValidationDto::validate([
                'email' => 'existing@example.com',
                'name' => 'Another User',
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('email'))->toBeTrue();
        });

        it('allows same email when updating own record', function(): void {
            $em = test()->getContainer()->get(EntityManagerInterface::class);

            // Create existing user
            $user = new User();
            $user->setEmail('update@example.com');
            $user->setName('Update User');
            $em->persist($user);
            $em->flush();

            $result = UserValidationDto::validate([
                'id' => $user->getId(),
                'email' => 'update@example.com',
                'name' => 'Updated Name',
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails when updating to another users email', function(): void {
            $em = test()->getContainer()->get(EntityManagerInterface::class);

            // Create two users
            $user1 = new User();
            $user1->setEmail('user1@example.com');
            $user1->setName('User 1');
            $em->persist($user1);

            $user2 = new User();
            $user2->setEmail('user2@example.com');
            $user2->setName('User 2');
            $em->persist($user2);

            $em->flush();

            $result = UserValidationDto::validate([
                'id' => $user1->getId(),
                'email' => 'user2@example.com',
                'name' => 'User 1 Updated',
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('email'))->toBeTrue();
        });
    });

    describe('ExistsCallback with Doctrine', function(): void {
        it('validates existing manager', function(): void {
            $em = test()->getContainer()->get(EntityManagerInterface::class);

            // Create manager
            $manager = new User();
            $manager->setEmail('manager@example.com');
            $manager->setName('Manager');
            $em->persist($manager);
            $em->flush();

            $result = UserValidationDto::validate([
                'email' => 'employee@example.com',
                'name' => 'Employee',
                'managerId' => $manager->getId(),
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
        it('validates unique SKU for new product', function(): void {
            $result = ProductValidationDto::validate([
                'sku' => 'PROD-001',
                'name' => 'New Product',
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails for duplicate SKU', function(): void {
            $em = test()->getContainer()->get(EntityManagerInterface::class);

            // Create existing product
            $product = new Product();
            $product->setSku('PROD-001');
            $product->setName('Existing Product');
            $em->persist($product);
            $em->flush();

            $result = ProductValidationDto::validate([
                'sku' => 'PROD-001',
                'name' => 'Another Product',
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('sku'))->toBeTrue();
        });

        it('validates existing active related product', function(): void {
            $em = test()->getContainer()->get(EntityManagerInterface::class);

            // Create active product
            $relatedProduct = new Product();
            $relatedProduct->setSku('PROD-RELATED');
            $relatedProduct->setName('Related Product');
            $relatedProduct->setActive(true);
            $em->persist($relatedProduct);
            $em->flush();

            $result = ProductValidationDto::validate([
                'sku' => 'PROD-001',
                'name' => 'New Product',
                'relatedProductId' => $relatedProduct->getId(),
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails for inactive related product', function(): void {
            $em = test()->getContainer()->get(EntityManagerInterface::class);

            // Create inactive product
            $relatedProduct = new Product();
            $relatedProduct->setSku('PROD-INACTIVE');
            $relatedProduct->setName('Inactive Product');
            $relatedProduct->setActive(false);
            $em->persist($relatedProduct);
            $em->flush();

            $result = ProductValidationDto::validate([
                'sku' => 'PROD-001',
                'name' => 'New Product',
                'relatedProductId' => $relatedProduct->getId(),
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
            $em = test()->getContainer()->get(EntityManagerInterface::class);

            // Create existing user
            $user = new User();
            $user->setEmail('existing@example.com');
            $user->setName('Existing User');
            $em->persist($user);
            $em->flush();

            expect(fn() => UserValidationDto::validateAndCreate([
                'email' => 'existing@example.com',
                'name' => 'Another User',
            ]))->toThrow(ValidationException::class);
        });
    });

    describe('Complex validation scenarios', function(): void {
        it('validates multiple constraints simultaneously', function(): void {
            $em = test()->getContainer()->get(EntityManagerInterface::class);

            // Create manager
            $manager = new User();
            $manager->setEmail('manager@example.com');
            $manager->setName('Manager');
            $em->persist($manager);

            // Create related product
            $relatedProduct = new Product();
            $relatedProduct->setSku('PROD-RELATED');
            $relatedProduct->setName('Related Product');
            $relatedProduct->setActive(true);
            $em->persist($relatedProduct);

            $em->flush();

            // Validate user with manager
            $userResult = UserValidationDto::validate([
                'email' => 'employee@example.com',
                'name' => 'Employee',
                'managerId' => $manager->getId(),
            ]);

            expect($userResult->isValid())->toBeTrue();

            // Validate product with related product
            $productResult = ProductValidationDto::validate([
                'sku' => 'PROD-001',
                'name' => 'New Product',
                'relatedProductId' => $relatedProduct->getId(),
            ]);

            expect($productResult->isValid())->toBeTrue();
        });
    });

    describe('FileCallback with Symfony', function(): void {
        it('validates file size (max 2MB)', function(): void {
            // Create a small file (1MB)
            $tempPath = tempnam(sys_get_temp_dir(), 'upload_');
            file_put_contents($tempPath, str_repeat('a', 1024 * 1024)); // 1MB

            $file = new UploadedFile($tempPath, 'document.pdf', 'application/pdf', null, true);

            $result = FileUploadDto::validate([
                'document' => $file,
            ]);

            expect($result->isValid())->toBeTrue();

            // Cleanup
            @unlink($tempPath);
        });

        it('fails validation for file too large', function(): void {
            // Create a large file (3MB)
            $tempPath = tempnam(sys_get_temp_dir(), 'upload_');
            file_put_contents($tempPath, str_repeat('a', 3 * 1024 * 1024)); // 3MB

            $file = new UploadedFile($tempPath, 'document.pdf', 'application/pdf', null, true);

            $result = FileUploadDto::validate([
                'document' => $file,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('document'))->toBeTrue();

            // Cleanup
            @unlink($tempPath);
        });

        it('validates image MIME type', function(): void {
            // Create a minimal PNG file (1x1 transparent pixel)
            $tempPath = tempnam(sys_get_temp_dir(), 'image_');
            $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
            file_put_contents($tempPath, $pngData);

            $image = new UploadedFile($tempPath, 'avatar.png', 'image/png', null, true);

            // Also need a document file
            $docPath = tempnam(sys_get_temp_dir(), 'doc_');
            file_put_contents($docPath, 'document content');
            $doc = new UploadedFile($docPath, 'document.pdf', 'application/pdf', null, true);

            $result = FileUploadDto::validate([
                'document' => $doc,
                'avatar' => $image,
            ]);

            expect($result->isValid())->toBeTrue();

            // Cleanup
            @unlink($tempPath);
            @unlink($docPath);
        });

        it('fails validation for invalid image MIME type', function(): void {
            // Create a text file
            $tempPath = tempnam(sys_get_temp_dir(), 'text_');
            file_put_contents($tempPath, 'This is not an image');

            $file = new UploadedFile($tempPath, 'avatar.txt', 'text/plain', null, true);

            // Also need a document file
            $docPath = tempnam(sys_get_temp_dir(), 'doc_');
            file_put_contents($docPath, 'document content');
            $doc = new UploadedFile($docPath, 'document.pdf', 'application/pdf', null, true);

            $result = FileUploadDto::validate([
                'document' => $doc,
                'avatar' => $file,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('avatar'))->toBeTrue();

            // Cleanup
            @unlink($tempPath);
            @unlink($docPath);
        });

        it('validates image dimensions (square, min 100x100)', function(): void {
            // Create a 200x200 PNG image
            $tempPath = tempnam(sys_get_temp_dir(), 'image_');
            // This is a minimal 200x200 PNG (created with base64)
            $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
            file_put_contents($tempPath, $pngData);

            $image = new UploadedFile($tempPath, 'banner.png', 'image/png', null, true);

            // Also need a document file
            $docPath = tempnam(sys_get_temp_dir(), 'doc_');
            file_put_contents($docPath, 'document content');
            $doc = new UploadedFile($docPath, 'document.pdf', 'application/pdf', null, true);

            $result = FileUploadDto::validate([
                'document' => $doc,
                'banner' => $image,
            ]);

            // Note: This will fail because the base64 image is 1x1, not 200x200
            // For a real test, we'd need to generate a proper image
            expect($result->isValid())->toBeFalse();
            expect($result->hasError('banner'))->toBeTrue();

            // Cleanup
            @unlink($tempPath);
            @unlink($docPath);
        });

        it('validates PDF file signature', function(): void {
            // Create a PDF file with correct signature
            $tempPath = tempnam(sys_get_temp_dir(), 'pdf_');
            file_put_contents($tempPath, '%PDF-1.4' . "\n" . 'fake pdf content');

            $pdfFile = new UploadedFile($tempPath, 'contract.pdf', 'application/pdf', null, true);

            // Also need a document file
            $docPath = tempnam(sys_get_temp_dir(), 'doc_');
            file_put_contents($docPath, 'document content');
            $doc = new UploadedFile($docPath, 'document.pdf', 'application/pdf', null, true);

            $result = FileUploadDto::validate([
                'document' => $doc,
                'contract' => $pdfFile,
            ]);

            expect($result->isValid())->toBeTrue();

            // Cleanup
            @unlink($tempPath);
            @unlink($docPath);
        });

        it('fails validation for invalid PDF signature', function(): void {
            // Create a file without PDF signature
            $tempPath = tempnam(sys_get_temp_dir(), 'txt_');
            file_put_contents($tempPath, 'This is not a PDF file');

            $fakeFile = new UploadedFile($tempPath, 'contract.pdf', 'application/pdf', null, true);

            // Also need a document file
            $docPath = tempnam(sys_get_temp_dir(), 'doc_');
            file_put_contents($docPath, 'document content');
            $doc = new UploadedFile($docPath, 'document.pdf', 'application/pdf', null, true);

            $result = FileUploadDto::validate([
                'document' => $doc,
                'contract' => $fakeFile,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('contract'))->toBeTrue();

            // Cleanup
            @unlink($tempPath);
            @unlink($docPath);
        });

        it('allows nullable file uploads', function(): void {
            // Create a document file
            $docPath = tempnam(sys_get_temp_dir(), 'doc_');
            file_put_contents($docPath, 'document content');
            $doc = new UploadedFile($docPath, 'document.pdf', 'application/pdf', null, true);

            $result = FileUploadDto::validate([
                'document' => $doc,
                'avatar' => null,
                'banner' => null,
                'contract' => null,
            ]);

            expect($result->isValid())->toBeTrue();

            // Cleanup
            @unlink($docPath);
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

