<?php declare(strict_types=1);

namespace Lohres\LogHelper;

use DateTimeImmutable;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Class LogHelper
 * Helper class for logging in lohres projects.
 * @package Lohres\LogHelper
 */
class LogHelper
{
    private static ?FilesystemAdapter $filesystem = null;

    public static function setFilesystemAdapter(?FilesystemAdapter $filesystemAdapter): void
    {
        self::$filesystem = $filesystemAdapter;
    }

    private static function filesystem(): FilesystemAdapter
    {
        if (self::$filesystem === null) {
            self::$filesystem = new LocalFilesystemAdapter();
        }
        return self::$filesystem;
    }

    /**
     * @param string $basePath
     * @param string $fullPath
     * @return string
     */
    private static function toZipEntryName(string $basePath, string $fullPath): string
    {
        $normalizedBase = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (str_starts_with($fullPath, $normalizedBase)) {
            $relativePath = substr($fullPath, strlen($normalizedBase));
            if (is_string($relativePath) && $relativePath !== "") {
                return str_replace(search: DIRECTORY_SEPARATOR, replace: "/", subject: $relativePath);
            }
        }
        return basename(str_replace(search: DIRECTORY_SEPARATOR, replace: "/", subject: $fullPath));
    }

    /**
     * @return void
     */
    private static function checkConfig(): void
    {
        if (!defined(constant_name: "LOHRES_LOG_PATH") || !defined(constant_name: "LOHRES_LOG_BACKUP_PATH")) {
            throw new RuntimeException(message: "config for logger invalid!");
        }
    }

    /**
     * @param array $entries
     * @return void
     */
    private static function removeDots(array &$entries): void
    {
        $dotKey = array_search(needle: ".", haystack: $entries, strict: true);
        if (!is_bool(value: $dotKey)) {
            unset($entries[$dotKey]);
        }
        $doubleDotKey = array_search(needle: "..", haystack: $entries, strict: true);
        if (!is_bool(value: $doubleDotKey)) {
            unset($entries[$doubleDotKey]);
        }
    }

    /**
     * @param string $source
     * @return array
     */
    private static function getAllFiles(string $source): array
    {
        $result = [];
        if (self::filesystem()->isDir($source)) {
            $entries = self::filesystem()->scanDir($source);
            if (!is_array(value: $entries)) {
                throw new RuntimeException(message: sprintf('cannot scan directory "%s"', $source));
            }
            self::removeDots($entries);
            if (count(value: $entries) < 1) {
                return [];
            }
            foreach ($entries as $entry) {
                $fullPath = $source . DIRECTORY_SEPARATOR . $entry;
                if (self::filesystem()->isDir($fullPath)) {
                    $subArray = self::getAllFiles(source: $source . DIRECTORY_SEPARATOR . $entry);
                    foreach ($subArray as $subEntry) {
                        $result[] = $subEntry;
                    }
                    continue;
                }
                $result[] = $fullPath;
            }
        }
        sort($result);
        return $result;
    }

    /**
     * @param string $source
     * @return array
     */
    private static function removeDirsAndFiles(string $source): array
    {
        $result = [
            "folders" => 0,
            "files" => 0
        ];
        if (self::filesystem()->isDir($source)) {
            $entries = self::filesystem()->scanDir($source);
            if (!is_array($entries)) {
                throw new RuntimeException(message: sprintf('cannot scan directory "%s"', $source));
            }
            self::removeDots($entries);
            foreach ($entries as $file) {
                $full = $source . DIRECTORY_SEPARATOR . $file;
                if (self::filesystem()->isDir($full)) {
                    $subResult = self::removeDirsAndFiles(source: $full);
                    $result["folders"] += $subResult["folders"];
                    $result["files"] += $subResult["files"];
                } else {
                    if (!self::filesystem()->unlink($full)) {
                        throw new RuntimeException(message: sprintf('cannot remove file "%s"', $full));
                    }
                    $result["files"]++;
                }
            }
            if (!self::filesystem()->removeDir($source)) {
                throw new RuntimeException(message: sprintf('cannot remove directory "%s"', $source));
            }
            $result["folders"]++;
        } else {
            if (!self::filesystem()->unlink($source)) {
                throw new RuntimeException(message: sprintf('cannot remove file "%s"', $source));
            }
            $result["files"]++;
        }
        return $result;
    }

    /**
     * @param string $name
     * @param int $level
     * @return Logger
     */
    public static function getLogger(string $name, int $level): Logger
    {
        self::checkConfig();
        $path = LOHRES_LOG_PATH . DIRECTORY_SEPARATOR . date(format: "Ymd") . DIRECTORY_SEPARATOR . date(format: "H")
            . DIRECTORY_SEPARATOR . $name;
        $file = date(format: "Ymd-H") . "_" . $name . ".json";
        if (!self::filesystem()->isDir($path) && !self::filesystem()->makeDir(path: $path, recursive: true)) {
            throw new RuntimeException(message: sprintf('Directory "%s" was not created', $path));
        }
        $log = new Logger(name: $name);
        $handler = new StreamHandler(stream: $path . DIRECTORY_SEPARATOR . $file, level: $level);
        $handler->setFormatter(new JsonFormatter());
        $log->pushHandler(handler: $handler);
        return $log;
    }

    /**
     * @return bool
     */
    public static function backUpLogs(): bool
    {
        self::checkConfig();
        $zip = new ZipArchive();
        $path = LOHRES_LOG_BACKUP_PATH;
        if (!self::filesystem()->isDir($path) && !self::filesystem()->makeDir(path: $path, recursive: true)) {
            throw new RuntimeException(message: sprintf('Directory "%s" was not created', $path));
        }
        $filename = $path . DIRECTORY_SEPARATOR . "backup-" . date(format: "Ymd-His") . ".zip";
        if ($zip->open(filename: $filename, flags: ZipArchive::CREATE) !== true) {
            throw new RuntimeException(message: sprintf('cannot open "%s"', $filename));
        }
        $entries = self::getAllFiles(source: LOHRES_LOG_PATH);
        foreach ($entries as $entry) {
            if (!$zip->addFile(
                filepath: $entry,
                entryname: self::toZipEntryName(LOHRES_LOG_PATH, $entry)
            )) {
                throw new RuntimeException(message: sprintf('cannot add "%s" to zip', $entry));
            }
        }
        if (!$zip->close()) {
            throw new RuntimeException(message: sprintf('cannot finalize backup "%s"', $filename));
        }
        return true;
    }

    /**
     * @param string $path
     * @param bool $force
     * @param int $retentionDays
     * @return array
     */
    public static function cleanUp(string $path, bool $force = false, int $retentionDays = 31): array
    {
        try {
            if ($retentionDays < 0) {
                throw new RuntimeException("retentionDays must be >= 0");
            }
            $result = [
                "folders" => 0,
                "files" => 0
            ];
            if (self::filesystem()->isDir($path)) {
                $today = new DateTimeImmutable("today");
                $entries = self::filesystem()->scanDir($path);
                if (!is_array(value: $entries)) {
                    throw new RuntimeException(message: sprintf('cannot scan directory "%s"', $path));
                }
                self::removeDots($entries);
                if (count(value: $entries) < 1) {
                    return $result;
                }
                foreach ($entries as $entry) {
                    $entryPath = $path . DIRECTORY_SEPARATOR . $entry;
                    $entryTimestamp = self::filesystem()->fileMTime($entryPath);
                    if (!is_int($entryTimestamp)) {
                        throw new RuntimeException(message: sprintf('cannot read modification time for "%s"', $entryPath));
                    }
                    $entryDate = (new DateTimeImmutable())->setTimestamp($entryTimestamp);
                    $ageInDays = (int)$entryDate->diff($today)->format("%a");
                    if ($force || $ageInDays > $retentionDays) {
                        $subResult = self::removeDirsAndFiles(source: $entryPath);
                        $result["folders"] += $subResult["folders"];
                        $result["files"] += $subResult["files"];
                    }
                }
            }
            return $result;
        } catch (Throwable $exception) {
            throw new RuntimeException(message: "cleanup failed", previous: $exception);
        }
    }
}
