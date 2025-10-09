<?php

declare(strict_types=1);

use event4u\DataHelpers\Support\FileLoader;

describe('FileLoader Caching', function(): void {
    beforeEach(function(): void {
        // Create temporary test files
        $this->jsonFile = sys_get_temp_dir() . '/test_' . bin2hex(random_bytes(8)) . '.json';
        $this->xmlFile = sys_get_temp_dir() . '/test_' . bin2hex(random_bytes(8)) . '.xml';

        file_put_contents($this->jsonFile, json_encode(['name' => 'John', 'age' => 30]));
        file_put_contents($this->xmlFile, '<?xml version="1.0"?><root><name>Jane</name><age>25</age></root>');
    });

    afterEach(function(): void {
        // Clean up
        if (file_exists($this->jsonFile)) {
            unlink($this->jsonFile);
        }
        if (file_exists($this->xmlFile)) {
            unlink($this->xmlFile);
        }
    });

    it('caches JSON file content', function(): void {
        // First load
        $result1 = FileLoader::loadAsArray($this->jsonFile);

        // Modify file
        file_put_contents($this->jsonFile, json_encode(['name' => 'Modified', 'age' => 99]));

        // Second load - should return cached result (not modified content)
        $result2 = FileLoader::loadAsArray($this->jsonFile);

        expect($result1)->toBe($result2);
        expect($result1)->toBe(['name' => 'John', 'age' => 30]);
    });

    it('caches XML file content', function(): void {
        // First load
        $result1 = FileLoader::loadAsArray($this->xmlFile);

        // Modify file
        file_put_contents($this->xmlFile, '<?xml version="1.0"?><root><name>Modified</name><age>99</age></root>');

        // Second load - should return cached result
        $result2 = FileLoader::loadAsArray($this->xmlFile);

        expect($result1)->toBe($result2);
        expect($result1['name'])->toBe('Jane');
    });

    it('normalizes file paths for caching', function(): void {
        // Load with absolute path
        $result1 = FileLoader::loadAsArray($this->jsonFile);

        // Load with relative path (if possible)
        $relativePath = basename($this->jsonFile);
        $cwd = getcwd();
        chdir(sys_get_temp_dir());

        try {
            $result2 = FileLoader::loadAsArray($relativePath);
            expect($result1)->toBe($result2);
        } finally {
            if ($cwd) {
                chdir($cwd);
            }
        }
    });
});

