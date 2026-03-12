# log-helper

Helper class for logging in Lohres PHP projects.

## Voraussetzungen

- PHP mit aktivierter `ext-zip`
- `monolog/monolog`

## Setup

Definiere vor der Nutzung beide Konstanten:

```php
define("LOHRES_LOG_PATH", __DIR__ . "/logs");
define("LOHRES_LOG_BACKUP_PATH", __DIR__ . "/logsBackup");
```

## Nutzung

```php
use Lohres\LogHelper\LogHelper;
use Monolog\Level;

$logger = LogHelper::getLogger("app", Level::Info->value);
$logger->info("application started");

LogHelper::backUpLogs();
LogHelper::cleanUp(LOHRES_LOG_PATH);
```

## Log-Struktur

Logs werden nach Datum, Stunde und Kanal abgelegt:

`<LOHRES_LOG_PATH>/YYYYMMDD/HH/<channel>/YYYYMMDD-HH_<channel>.json`

Backups werden unter folgendem Muster erzeugt:

`<LOHRES_LOG_BACKUP_PATH>/backup-YYYYMMDD-HHMMSS.zip`

## Cleanup-Verhalten

- Standard: loescht nur Eintraege, die aelter als 31 Tage sind.
- Mit `force=true`: loescht alle Eintraege im angegebenen Pfad.
