<?php declare(strict_types=1);

namespace Lohres\LogHelper;

interface FilesystemAdapter
{
    public function isDir(string $path): bool;

    public function makeDir(string $path, bool $recursive = false): bool;

    /**
     * @return array<int, string>|false
     */
    public function scanDir(string $path): array|false;

    public function unlink(string $path): bool;

    public function removeDir(string $path): bool;

    public function fileMTime(string $path): int|false;
}
