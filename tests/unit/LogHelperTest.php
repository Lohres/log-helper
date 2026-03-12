<?php declare(strict_types=1);

use Lohres\LogHelper\LogHelper;
use Monolog\Level;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

#[CoversClass(LogHelper::class)]
#[CoversMethod(LogHelper::class, "getLogger")]
#[CoversMethod(LogHelper::class, "backUpLogs")]
#[CoversMethod(LogHelper::class, "cleanUp")]
final class LogHelperTest extends TestCase
{
    protected function setUp(): void
    {
        define("LOHRES_LOG_PATH", realpath(".") . DIRECTORY_SEPARATOR . "logs");
        define("LOHRES_LOG_BACKUP_PATH", realpath(".") . DIRECTORY_SEPARATOR . "logsBackup");
    }

    protected function tearDown(): void
    {
        LogHelper::cleanUp(path: LOHRES_LOG_PATH, force: true);
        LogHelper::cleanUp(path: LOHRES_LOG_BACKUP_PATH, force: true);
        if (is_dir(filename: LOHRES_LOG_BACKUP_PATH)) {
            rmdir(directory: LOHRES_LOG_BACKUP_PATH);
        }
        if (is_dir(filename: LOHRES_LOG_PATH)) {
            rmdir(directory: LOHRES_LOG_PATH);
        }
    }

    #[Test]
    public function testLogHelper(): void
    {
        $logger = LogHelper::getLogger(name: "testChannel", level: Level::Debug->value);
        $logger->info(message: "info", context: ["id" => 42]);
        $this->assertDirectoryExists(directory: LOHRES_LOG_PATH . DIRECTORY_SEPARATOR . date("Ymd"));
        $backUp = LogHelper::backUpLogs();
        $this->assertTrue(condition: $backUp);
        $backupFiles = glob(pattern: LOHRES_LOG_BACKUP_PATH . DIRECTORY_SEPARATOR . "backup-" . date("Ymd") . "-*.zip");
        $this->assertIsArray(actual: $backupFiles);
        $this->assertNotEmpty(actual: $backupFiles);

        $zip = new ZipArchive();
        $openResult = $zip->open($backupFiles[0]);
        $this->assertTrue($openResult === true);
        $this->assertGreaterThan(0, $zip->numFiles);
        $fileContent = $zip->getFromIndex(0);
        $this->assertIsString($fileContent);
        $this->assertStringContainsString('"message":"info"', $fileContent);
        $zip->close();
    }

    #[Test]
    public function testCleanupHonorsRetentionWithoutForce(): void
    {
        if (!is_dir(LOHRES_LOG_PATH)) {
            mkdir(LOHRES_LOG_PATH, 0777, true);
        }

        $oldDir = LOHRES_LOG_PATH . DIRECTORY_SEPARATOR . "old";
        $newDir = LOHRES_LOG_PATH . DIRECTORY_SEPARATOR . "new";
        mkdir($oldDir, 0777, true);
        mkdir($newDir, 0777, true);
        file_put_contents($oldDir . DIRECTORY_SEPARATOR . "old.log", "old");
        file_put_contents($newDir . DIRECTORY_SEPARATOR . "new.log", "new");

        $oldTimestamp = strtotime("-40 days");
        $newTimestamp = strtotime("-1 day");
        touch($oldDir . DIRECTORY_SEPARATOR . "old.log", $oldTimestamp);
        touch($newDir . DIRECTORY_SEPARATOR . "new.log", $newTimestamp);
        touch($oldDir, $oldTimestamp);
        touch($newDir, $newTimestamp);

        $result = LogHelper::cleanUp(LOHRES_LOG_PATH, false);

        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey("folders", $result);
        $this->assertArrayHasKey("files", $result);
        $this->assertDirectoryDoesNotExist($oldDir);
        $this->assertDirectoryExists($newDir);
    }

    #[Test]
    #[RunInSeparateProcess]
    public function testGetLoggerThrowsWhenConfigMissing(): void
    {
        $this->expectException(RuntimeException::class);
        LogHelper::getLogger(name: "broken", level: Level::Debug->value);
    }

    #[Test]
    #[RunInSeparateProcess]
    public function testGetLoggerThrowsOnUnwritablePath(): void
    {
        $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "log-helper-not-a-dir-" . uniqid("", true);
        file_put_contents($tmpFile, "x");
        define("LOHRES_LOG_PATH", $tmpFile);
        define("LOHRES_LOG_BACKUP_PATH", sys_get_temp_dir() . DIRECTORY_SEPARATOR . "backup-" . uniqid("", true));

        $this->expectException(RuntimeException::class);
        try {
            LogHelper::getLogger(name: "testChannel", level: Level::Debug->value);
        } finally {
            @unlink($tmpFile);
        }
    }

    #[Test]
    public function testBackupKeepsRelativePathsForDuplicateFileNames(): void
    {
        $dirA = LOHRES_LOG_PATH . DIRECTORY_SEPARATOR . "a";
        $dirB = LOHRES_LOG_PATH . DIRECTORY_SEPARATOR . "b";
        mkdir($dirA, 0777, true);
        mkdir($dirB, 0777, true);
        file_put_contents($dirA . DIRECTORY_SEPARATOR . "same.log", "one");
        file_put_contents($dirB . DIRECTORY_SEPARATOR . "same.log", "two");

        $this->assertTrue(LogHelper::backUpLogs());
        $backupFiles = glob(pattern: LOHRES_LOG_BACKUP_PATH . DIRECTORY_SEPARATOR . "backup-" . date("Ymd") . "-*.zip");
        $this->assertIsArray($backupFiles);
        $this->assertNotEmpty($backupFiles);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($backupFiles[0]) === true);
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (is_array($stat) && isset($stat["name"]) && is_string($stat["name"])) {
                $entries[] = $stat["name"];
            }
        }
        $zip->close();

        $this->assertContains("a/same.log", $entries);
        $this->assertContains("b/same.log", $entries);
    }
}
