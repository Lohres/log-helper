<?php declare(strict_types=1);

use Lohres\LogHelper\LogHelper;
use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(LogHelper::class)]
#[CoversMethod(LogHelper::class, "getLogger")]
final class LogHelperConfigTest extends TestCase
{
    #[Test]
    #[RunInSeparateProcess]
    public function testGetLoggerThrowsWhenConfigMissing(): void
    {
        $this->expectException(\RuntimeException::class);
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

        $this->expectException(\RuntimeException::class);
        set_error_handler(static fn (): bool => true, E_WARNING);
        try {
            LogHelper::getLogger(name: "testChannel", level: Level::Debug->value);
        } finally {
            restore_error_handler();
            @unlink($tmpFile);
        }
    }
}
