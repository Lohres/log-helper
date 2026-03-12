# TODO - Verbesserungen fuer log-helper

## Prioritaet Hoch

- [x] `cleanUp()` ohne `die()` umsetzen
  - Statt Prozessabbruch Exceptions werfen oder kontrolliert rueckgeben.
  - Fehlerpfade testbar machen.

- [x] Retention-Logik in `cleanUp()` korrigieren
  - Aktuell wird `YYYYMMDD` numerisch subtrahiert (`$date - $fct`), was um Monatswechsel falsch ist.
  - Auf echte Datumsdifferenz (z. B. `DateTimeImmutable`) umstellen.

- [x] Backup-Dateinamen eindeutig machen
  - `backUpLogs()` ueberschreibt taeglich dieselbe Datei `backup-YYYYMMDD.zip`.
  - Optional Stunde/Minute/Sekunde oder Suffix verwenden.

## Prioritaet Mittel

- [x] Testabdeckung erweitern
  - Fehlerfall: fehlende Konstanten.
  - Fehlerfall: nicht beschreibbare Verzeichnisse.
  - `cleanUp()` fuer `force=false` und Altersgrenze testen.
  - Verifikation des ZIP-Inhalts (nicht nur Dateiexistenz).

- [x] Robustheit bei Dateisystemoperationen verbessern
  - Rueckgabewerte von `unlink`, `rmdir`, `scandir`, `opendir` strikt pruefen.
  - Bei Fehlern aussagekraeftige RuntimeExceptions mit Kontext liefern.

- [x] Kollisionen im ZIP vermeiden
  - Derzeit wird `basename(...)` als Entry-Name genutzt; gleichnamige Dateien aus verschiedenen Ordnern koennen kollidieren.
  - Relative Pfade im ZIP beibehalten.

## Prioritaet Niedrig

- [ ] README erweitern
  - Minimalbeispiel fuer Setup und Nutzung (`LOHRES_LOG_PATH`, `LOHRES_LOG_BACKUP_PATH`).
  - Hinweis auf Pfadstruktur und Cleanup-Verhalten.

- [ ] Konfigurierbare Retention einfuehren
  - Aufrufer sollte Aufbewahrungsdauer in Tagen setzen koennen.
  - Rueckwaertskompatibles Default-Verhalten beibehalten.

- [ ] CI-Check fuer Tests ergaenzen
  - GitHub Action fuer `vendor/bin/phpunit -c phpunit.xml`.

## Optionaler Refactor

- [ ] Statische Hilfsklasse schrittweise entkoppeln
  - Dateisystemzugriffe kapseln (Adapter/Service), um Unit-Tests ohne echtes FS zu vereinfachen.
