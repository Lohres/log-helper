# Architecture Context

## Systemueberblick

- Zweck des Systems:
  - Bereitstellung eines wiederverwendbaren Log-Helfers fuer PHP-Projekte mit Monolog.
  - Ausgabe strukturierter JSON-Logs in eine feste Dateisystem-Hierarchie.
  - Backup- und Cleanup-Funktionen fuer erzeugte Log-Daten.
- Hauptkomponenten:
  - `LogHelper::getLogger(string $name, int $level): Logger`
  - `LogHelper::backUpLogs(): bool`
  - `LogHelper::cleanUp(string $path, bool $force = false): array`
  - Interne Hilfsfunktionen fuer Dateibaum-Iteration und rekursives Loeschen.

## Datenfluss

1. Eingang:
   - Aufrufer uebergibt Kanalname und Monolog-Level oder einen Cleanup-Pfad.
   - Pflichtkonstanten fuer Log- und Backup-Pfade muessen definiert sein.
2. Verarbeitung:
   - Logger-Erstellung: Zielpfad `LOHRES_LOG_PATH/YYYYMMDD/HH/<channel>` wird erzeugt.
   - Logging: Monolog schreibt JSON-Eintraege in `YYYYMMDD-HH_<channel>.json`.
   - Backup: Alle Dateien unter `LOHRES_LOG_PATH` werden in `backup-YYYYMMDD.zip` gesammelt.
   - Cleanup: Ordner/Dateien werden (optional erzwungen) rekursiv entfernt.
3. Speicherung/Ausgabe:
   - Ausgabe als Logdateien auf dem Dateisystem.
   - Rueckgabeobjekte: `Logger`, `bool` (Backup), Array mit Zaehlern (`folders`, `files`) fuer Cleanup.

## Integrationen

- Externe APIs:
  - Keine HTTP-APIs.
  - Bibliothek: Monolog v3.
- Messaging/Queues:
  - Keine.
- Datenbanken:
  - Keine.

## Nicht-funktionale Anforderungen

- Sicherheit:
  - Keine Secrets in Logs oder Beispielen.
  - Pfadkonfiguration muss kontrolliert und valide sein.
  - Fehler sollen keine sensitiven Informationen leaken.
- Performance:
  - Dateibasierte Operationen sind I/O-lastig; Backup/Cleanup laufen linear ueber Dateibestand.
  - Keine Streaming-/Chunking-Optimierung fuer sehr grosse Log-Mengen vorhanden.
- Verfuegbarkeit:
  - Abhaengig von Dateisystem- und Schreibrechten.
  - Fehler bei Verzeichnisanlage oder ZIP-Zugriff erzeugen RuntimeExceptions.

## Entscheidungen und Trade-offs

- Entscheidung:
  - Einfacher statischer Helper statt komplexer Service-Architektur.
- Alternative:
  - RotatingFileHandler/Retention direkt in Monolog oder externer Log-Stack (z. B. Elasticsearch).
- Begruendung:
  - Niedrige Integrationshuerde fuer kleine/isolierte PHP-Projekte.
  - Klare Dateistruktur und einfache Betriebsfaelle ohne weitere Infrastruktur.
