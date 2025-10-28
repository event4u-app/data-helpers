<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support\Cache\Adapters;

use event4u\DataHelpers\Support\Cache\CacheInterface;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function serialize;
use function time;
use function unlink;
use function unserialize;

/**
 * Filesystem cache adapter.
 *
 * Stores cache entries as serialized PHP files in the filesystem.
 * Always available as fallback when Laravel/Symfony cache is not available.
 */
final class FilesystemCacheAdapter implements CacheInterface
{
    private string $cachePath;

    public function __construct(string $cachePath)
    {
        $this->cachePath = rtrim($cachePath, '/');
        $this->ensureCacheDirectoryExists();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getCacheFilePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $content = file_get_contents($file);
        if (false === $content) {
            return $default;
        }

        $data = unserialize($content);
        if (!is_array($data) || !isset($data['expires_at'], $data['value'])) {
            return $default;
        }

        // Check if expired
        if (null !== $data['expires_at'] && $data['expires_at'] < time()) {
            $this->delete($key);

            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $file = $this->getCacheFilePath($key);
        $expiresAt = null !== $ttl ? time() + $ttl : null;

        $data = [
            'value' => $value,
            'expires_at' => $expiresAt,
            'created_at' => time(),
        ];

        $serialized = serialize($data);

        return false !== file_put_contents($file, $serialized, LOCK_EX);
    }

    public function has(string $key): bool
    {
        return null !== $this->get($key);
    }

    public function delete(string $key): bool
    {
        $file = $this->getCacheFilePath($key);

        if (!file_exists($file)) {
            return true;
        }

        return unlink($file);
    }

    public function clear(): bool
    {
        if (!is_dir($this->cachePath)) {
            return true;
        }

        $files = glob($this->cachePath . '/*.cache');
        if (false === $files) {
            return false;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    public function getMultiple(array $keys, mixed $default = null): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    public function setMultiple(array $values, ?int $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    public function deleteMultiple(array $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }

        return $success;
    }

    private function getCacheFilePath(string $key): string
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);

        return $this->cachePath . '/' . $safeKey . '.cache';
    }

    private function ensureCacheDirectoryExists(): void
    {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }
}

