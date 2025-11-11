<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Mimes;

class MimesTestDto extends SimpleDto
{
    public function __construct(
        #[Mimes(['jpg', 'png', 'gif'])]
        public readonly ?string $image = null,
    ) {}
}

describe('Mimes Attribute - Plain PHP Validation', function(): void {
    it('fails with string path (expects UploadedFile object)', function(): void {
        $result = MimesTestDto::validate(['image' => '/some/image.png']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('image'))->toBeTrue();
    });

    it('passes with null', function(): void {
        $result = MimesTestDto::validate(['image' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
