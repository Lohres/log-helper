<?php declare(strict_types=1);

namespace Lohres\LogHelper;

final class LocalFilesystemAdapter implements FilesystemAdapter
{
    public function isDir(string $path): bool
    {
        return is_dir(filename: $path);
    }

    public function makeDir(string $path, bool $recursive = false): bool
    {
        return mkdir(directory: $path, recursive: $recursive);
    }

    public function scanDir(string $path): array|false
    {
        return scandir(directory: $path);
    }

    public function unlink(string $path): bool
    {
        return unlink(filename: $path);
    }

    public function removeDir(string $path): bool
    {
        return rmdir(directory: $path);
    }

    public function fileMTime(string $path): int|false
    {
        return filemtime(filename: $path);
    }
}
