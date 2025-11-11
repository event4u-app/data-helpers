<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Image;

class ImageTestDto extends SimpleDto
{
    public function __construct(
        #[Image]
        public readonly ?string $photo = null,
    ) {}
}

describe('Image Attribute - Plain PHP Validation', function(): void {
    it('fails with string path (expects UploadedFile object)', function(): void {
        $result = ImageTestDto::validate(['photo' => '/some/image.png']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('photo'))->toBeTrue();
    });

    it('passes with null', function(): void {
        $result = ImageTestDto::validate(['photo' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
