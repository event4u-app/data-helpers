<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\File;

class FileTestDto extends SimpleDto
{
    public function __construct(
        #[File]
        public readonly ?string $document = null,
    ) {}
}

describe('File Attribute - Plain PHP Validation', function(): void {
    it('fails with string path (expects UploadedFile object)', function(): void {
        $result = FileTestDto::validate(['document' => '/some/file.txt']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('document'))->toBeTrue();
    });

    it('passes with null', function(): void {
        $result = FileTestDto::validate(['document' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
