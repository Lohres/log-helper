<?php declare(strict_types=1);

use Lohres\LogHelper\LogHelper;
use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(LogHelper::class)]
#[CoversMethod(LogHelper::class, "getLogger")]
#[CoversMethod(LogHelper::class, "backUpLogs")]
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
        $logger->info(message: "info");
        $this->assertDirectoryExists(directory: LOHRES_LOG_PATH . DIRECTORY_SEPARATOR . date("Ymd"));
        $backUp = LogHelper::backUpLogs();
        $this->assertTrue(condition: $backUp);
        $this->assertFileExists(
            filename: LOHRES_LOG_BACKUP_PATH . DIRECTORY_SEPARATOR . "backup-" . date(format: "Ymd") . ".zip"
        );
    }
}
