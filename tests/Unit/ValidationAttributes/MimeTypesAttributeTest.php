<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\MimeTypes;

class MimeTypesTestDto extends SimpleDto
{
    public function __construct(
        #[MimeTypes(['image/jpeg', 'image/png', 'image/gif'])]
        public readonly ?string $image = null,
    ) {}
}

describe('MimeTypes Attribute - Plain PHP Validation', function(): void {
    it('fails with string path (expects UploadedFile object)', function(): void {
        $result = MimeTypesTestDto::validate(['image' => '/some/image.png']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('image'))->toBeTrue();
    });

    it('passes with null', function(): void {
        $result = MimeTypesTestDto::validate(['image' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
