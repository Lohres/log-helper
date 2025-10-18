<?php declare(strict_types=1);

namespace Lohres\LogHelper;

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
        if (is_dir(filename: $source)) {
            $entries = scandir(directory: $source);
            if (is_array(value: $entries)) {
                self::removeDots($entries);
                if (count(value: $entries) < 1) {
                    return [];
                }
                foreach ($entries as $entry) {
                    if (is_dir(filename: $source . DIRECTORY_SEPARATOR . $entry)) {
                        $subArray = self::getAllFiles(source: $source . DIRECTORY_SEPARATOR . $entry);
                        foreach ($subArray as $subEntry) {
                            $result[] = $subEntry;
                        }
                        continue;
                    }
                    $result[] = $source . DIRECTORY_SEPARATOR . $entry;
                }
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
        if (is_dir(filename: $source)) {
            $dir = opendir(directory: $source);
            while (false !== ($file = readdir(dir_handle: $dir))) {
                if (($file !== ".") && ($file !== "..")) {
                    $full = $source . DIRECTORY_SEPARATOR . $file;
                    if (is_dir(filename: $full)) {
                        $subResult = self::removeDirsAndFiles(source: $full);
                        $result["folders"] += $subResult["folders"];
                        $result["files"] += $subResult["files"];
                    } else {
                        unlink(filename: $full);
                        $result["files"]++;
                    }
                }
            }
            closedir(dir_handle: $dir);
            rmdir(directory: $source);
            $result["folders"]++;
        } else {
            unlink(filename: $source);
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
        $file = date(format: "Ymd-H") . "_" . $name . ".log";
        if (!@mkdir(directory: $path, recursive: true) && !is_dir(filename: $path)) {
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
        if (!@mkdir(directory: $path, recursive: true) && !is_dir(filename: $path)) {
            throw new RuntimeException(message: sprintf('Directory "%s" was not created', $path));
        }
        $filename = $path . DIRECTORY_SEPARATOR . "backup-" . date(format: "Ymd") . ".zip";
        if (file_exists(filename: $filename)) {
            unlink(filename: $filename);
        }
        if ($zip->open(filename: $filename, flags: ZipArchive::CREATE) !== true) {
            throw new RuntimeException(message: sprintf('cannot open "%s"', $filename));
        }
        $entries = self::getAllFiles(source: LOHRES_LOG_PATH);
        foreach ($entries as $entry) {
            $zip->addFile(
                filepath: $entry,
                entryname: basename(str_replace(search: DIRECTORY_SEPARATOR, replace: "/", subject: $entry))
            );
        }
        $zip->close();
        return true;
    }

    /**
     * @param string $path
     * @param bool $force
     * @return array
     */
    public static function cleanUp(string $path, bool $force = false): array
    {
        try {
            $result = [
                "folders" => 0,
                "files" => 0
            ];
            if (is_dir(filename: $path)) {
                $date = date(format: "Ymd");
                $entries = scandir(directory: $path);
                if (is_array(value: $entries)) {
                    self::removeDots($entries);
                    if (count(value: $entries) < 1) {
                        return $result;
                    }
                    foreach ($entries as $entry) {
                        $fct = (int)date("Ymd", filectime($path . DIRECTORY_SEPARATOR . $entry));
                        $diff = (int)$date - $fct;
                        if ($force || $diff > 31) {
                            $subResult = self::removeDirsAndFiles(source: $path . DIRECTORY_SEPARATOR . $entry);
                            $result["folders"] += $subResult["folders"];
                            $result["files"] += $subResult["files"];
                        }
                    }
                }
            }
            return $result;
        } catch (Throwable $exception) {
            die($exception->getMessage());
        }
    }
}
