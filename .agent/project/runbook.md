# Runbook

## Zweck

Standardablauf fuer Nutzung, Verifikation und Stoerungsanalyse des Log-Helpers
in Entwicklungs- und CI-Umgebungen.

## Voraussetzungen

- Zugriff:
  - Schreibrechte auf `LOHRES_LOG_PATH` und `LOHRES_LOG_BACKUP_PATH`.
  - Leserechte fuer zu sichernde Logdateien.
- Umgebungsvariablen:
  - Keine ENV-Pflicht; stattdessen muessen vor Nutzung folgende Konstanten definiert sein:
    - `LOHRES_LOG_PATH`
    - `LOHRES_LOG_BACKUP_PATH`
- Tools:
  - PHP (Version passend zu PHPUnit 12 und Monolog 3).
  - Composer-Abhaengigkeiten installiert.
  - `vendor/bin/phpunit`.

## Ablauf

1. Konstanten fuer Log- und Backup-Verzeichnis frueh im Bootstrap setzen.
2. Logger beziehen: `LogHelper::getLogger(<channel>, <level>)` und mindestens einen Test-Log schreiben.
3. Optional Backup starten: `LogHelper::backUpLogs()`.
4. Optional Altlasten entfernen: `LogHelper::cleanUp(<path>, <force>)`.
5. Tests ausfuehren: `vendor/bin/phpunit -c phpunit.xml`.

## Verifikation

- Erwartetes Ergebnis:
  - Log-Datei unter `LOHRES_LOG_PATH/YYYYMMDD/HH/<channel>/YYYYMMDD-HH_<channel>.json`.
  - Backup-Datei `LOHRES_LOG_BACKUP_PATH/backup-YYYYMMDD.zip`.
  - Erfolgreicher Testlauf in PHPUnit.
- Health-Checks:
  - Verzeichnisse existieren und sind beschreibbar.
  - ZIP-Datei ist vorhanden und oeffnet fehlerfrei.
  - Cleanup-Rueckgabe zeigt plausible Zaehler fuer geloeschte Dateien/Ordner.

## Rollback

- Rueckgaengig-Schritte:
  - Neu erzeugte Test-Logs und Backups mit `LogHelper::cleanUp(..., true)` entfernen.
  - Falls noetig verbleibende leere Verzeichnisse manuell loeschen.
- Sicherheitspruefung danach:
  - Keine sensiblen Daten in verbleibenden Logdateien.
  - Dateirechte der Log-/Backup-Pfade weiterhin Least-Privilege-konform.
