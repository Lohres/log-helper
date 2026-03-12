# Agent Rules (Core)

Diese Datei definiert die verbindlichen Basisregeln fuer agentische Arbeit in diesem Projekt.
Ziel ist reproduzierbares, sicheres und nachvollziehbares Arbeiten ueber verschiedene Modelle und Agent-Clients hinweg.

## Prioritaet von Regeln

1. Direkte Nutzeranforderung
2. Sicherheits- und Compliance-Regeln
3. Projektregeln aus `./.agent/project/` (falls vorhanden)
4. Core-Regeln aus dieser Datei und `./.agent/policies/`

Bei Konflikten zwischen Projekt- und Core-Regeln gewinnt die projektspezifische Regel.
Bei Konflikten zwischen direkter Nutzeranforderung und Security/Compliance gewinnen Security/Compliance-Regeln.

## Arbeitsprinzipien

- Kleine, nachvollziehbare Aenderungen statt grosser ungepruefter Umbauten.
- Vor Datei-Aenderungen Kontext lesen und Auswirkungen verstehen.
- Vor Abschluss immer mindestens Basis-Checks ausfuehren.
- Entscheidungen, Annahmen und Grenzen transparent benennen.
- Keine Secrets in Logs, Commits oder Beispiel-Konfigurationen.

## Erstlauf-Verhalten (Pflichtablauf)

Der Erstlauf ist nur abgeschlossen, wenn alle Pflichtschritte in Reihenfolge erledigt sind.

1. `./.agent/project/AGENTS.project.md` aus Projektkontext vorbefuellen.
2. `./.agent/project/architecture-context.md` aus Projektkontext vorbefuellen.
3. `./.agent/project/runbook.md` aus Projektkontext vorbefuellen.
4. Nutzer explizit um Kontrolle/Freigabe der Entwuerfe bitten.
5. Nur nach expliziter Erlaubnis die projektseitige `AGENTS.md` im Zielprojekt sinnvoll ergaenzen (falls fachlich sinnvoll).
6. Nacharbeiten aus Nutzerfeedback umsetzen.
7. Sinnvolle Skills fuer das Projekt mit `./.agent/skill.sh` heraussuchen und vorschlagen; erst nach Bestaetigung durch den Nutzer installieren.
8. Zum Abschluss einmal die installierten Skills als Liste ausgeben.
9. Direkt danach darauf hinweisen, dass weitere Skills mit `.agent/skill.sh` hinzugefuegt werden koennen.

## Erstlauf-Abschlusskriterien

Der Agent darf den Erstlauf erst als `abgeschlossen` markieren, wenn alle Punkte erfuellt sind:
- alle drei `.agent/project/*`-Dateien sind befuellt
- eine explizite Kontrollanfrage wurde gestellt
- Feedback wurde eingearbeitet
- installierte Skills wurden einmal gelistet
- Hinweis auf `.agent/skill.sh` zum weiteren Hinzufuegen wurde gegeben
- fuer die projektseitige `AGENTS.md` wurde entweder eine explizite Erlaubnis umgesetzt oder eine explizite Ablehnung dokumentiert

Ohne Rueckmeldung bzw. Freigabe keine weitreichenden Annahmen als final behandeln.

## Git und Commits

- Branch-Namen nach Policy `./.agent/policies/git.md`.
- Commit-Messages nach Policy `./.agent/policies/commits.md`.
- Kein direkter Commit auf `main` oder `master` in Team-Workflows.

## Skill-Nutzung

- Alle Skill-Befehle ausschliesslich ueber `./.agent/skill.sh` ausfuehren.
- Skills fuer die Bearbeitung auch direkt aus dem Root-Symlink `./skills/` lesen (zeigt auf `./.agent/skills/.shared/`).
- Sobald es um Skills geht oder nach Skills gefragt wird, die Projekt-Skills immer frisch aus `./skills/` einlesen (nicht aus veraltetem Kontext antworten).
- Bei der Frage nach verfuegbaren Skills immer mit einer exakten, aktuellen Namensliste der gefundenen Skills antworten.

## Guardrails (Nicht erlaubt)

- Keine Umgehung von Security-/Compliance-Regeln zugunsten von Tempo.
- Keine Installation oder Nutzung unklarer Skill-Quellen ohne Nutzerfreigabe.
- Keine stillschweigende Interpretation fehlender Freigaben als Zustimmung.
- Keine destruktiven Aktionen ohne explizite Nutzeranweisung.

## Referenzen

- Skills: `./.agent/skills/.shared/` (Root-Symlink: `./skills`)
- Policies: `./.agent/policies/`
- Templates: `./.agent/templates/`
- Skripte: `./.agent/scripts/`

## Projektseitige Ergaenzungen (log-helper)

Diese Ergaenzungen gelten fuer das Zielprojekt und konkretisieren die Core-Regeln.

### Scope und Ziel

- Repository-Typ: kleine PHP-Logging-Library (`lohres/log-helper`).
- Zentrale Datei: `src/LogHelper.php`.
- Kernfunktionen: `getLogger()`, `backUpLogs()`, `cleanUp()`.

### Technische Leitplanken

- Oeffentliche API-Signaturen von `LogHelper` nur nach expliziter Abstimmung aendern.
- Erwartete Log-Pfadstruktur beibehalten: `LOHRES_LOG_PATH/YYYYMMDD/HH/<channel>/`.
- Erwartetes Backup-Muster beibehalten: `LOHRES_LOG_BACKUP_PATH/backup-YYYYMMDD.zip`.
- Laufzeitkonstanten `LOHRES_LOG_PATH` und `LOHRES_LOG_BACKUP_PATH` als Pflichtkonfiguration behandeln.

### Verifikation und Checks

- Nach relevanten Codeaenderungen mindestens folgenden Basis-Check ausfuehren:
  - `vendor/bin/phpunit -c phpunit.xml`
- Falls `vendor/` fehlt oder Abhaengigkeiten nicht installiert sind, das transparent melden.
- Bei Aenderungen an Dateisystemlogik (Backup/Cleanup) immer auch einen realen Dateisystempfad-Test einplanen.

### Sicherheits- und Qualitaetshinweise

- Keine Secrets oder sensitiven Inhalte in Logs, Beispielen oder Testdaten.
- Fehlerbehandlung so gestalten, dass keine sensiblen Pfad-/Systemdetails unnoetig ausgegeben werden.
- Dateisystemoperationen (mkdir/unlink/rmdir/zip) auf Fehlerszenarien pruefen und dokumentieren.
