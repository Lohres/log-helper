# Project Agent Rules

Diese Datei erweitert `AGENTS.md` um projektspezifischen Kontext.

## Projektkontext

- Domain: PHP-Library fuer strukturiertes Logging in Lohres-Projekten.
- Hauptziele:
  - Einheitliches JSON-Logging auf Dateisystembasis ueber Monolog bereitstellen.
  - Tages- und stundenbasierte Log-Ablage nach Kanalname erzeugen.
  - Log-Bestaende als ZIP-Backup sichern und alte Logs aufraeumen.
- Kritische Komponenten:
  - `src/LogHelper.php` mit `getLogger()`, `backUpLogs()`, `cleanUp()`.
  - Runtime-Konstanten `LOHRES_LOG_PATH` und `LOHRES_LOG_BACKUP_PATH`.
  - Dateisystem-/ZIP-Funktionen (`mkdir`, `scandir`, `unlink`, `rmdir`, `ZipArchive`).

## Architekturhinweise

- Services/Module:
  - Ein zentrales Utility-Modul `Lohres\\LogHelper\\LogHelper`.
  - Unit-Tests in `tests/unit/LogHelperTest.php`.
- Externe Schnittstellen:
  - Monolog (`monolog/monolog`) fuer Logger, Handler und JSON-Formatter.
  - PHP-Extension `ext-zip` fuer Backups.
- Technische Grenzen:
  - Kein Rotation-/Retention-Service ausser `cleanUp()` (dateibasierte Loeschlogik).
  - Dateisystemzugriff und Schreibrechte sind zwingend.
  - Backup-ZIP nutzt Dateinamen als Entry-Namen (keine Verzeichnisstruktur im Archiv).

## Projektspezifische Regeln

- Namenskonventionen:
  - PSR-4 Namespace `Lohres\\LogHelper\\`.
  - Dateiname `LogHelper.php`, Testklasse `LogHelperTest`.
  - Branches gemaess `/.agent/policies/git.md` (feature/fix/chore/hotfix mit Ticket-ID).
- Testanforderungen:
  - Fuer Logik-Aenderungen mindestens `vendor/bin/phpunit -c phpunit.xml` ausfuehren.
  - Tests muessen Erstellung von Logs und Backup-Datei weiter absichern.
- Freigabeprozess:
  - Kein direkter Push auf `main`/`master`/`dev`/`development`.
  - Kleine, logisch getrennte Commits mit Format aus `/.agent/policies/commits.md`.
  - Vor Merge Verifikation + Risiko/Regressionen dokumentieren.

## Risiken

- Bekannte Risiken:
  - `cleanUp()` verwendet numerische Datumsdifferenz (Ymd), was Monatsgrenzen ungenau abbildet.
  - In `cleanUp()` fuehrt Exception-Behandlung mit `die()` zu hartem Prozessabbruch.
  - File-Operationen koennen bei fehlenden Rechten oder Locks fehlschlagen.
- Nicht aendern ohne Abstimmung:
  - Oeffentliche API-Signaturen von `getLogger()`, `backUpLogs()`, `cleanUp()`.
  - Erwartete Pfadstruktur der Logs (`Ymd/H/channel`).
  - JSON-Logging-Format und Backup-Dateiname `backup-YYYYMMDD.zip`.
