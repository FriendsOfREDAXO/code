# Changelog

## [1.5.0] - 2026-03-03
### Added
- API-Integration für das `api` AddOn über RoutePackages (`code` und `backend/code`).
- Neue Endpunkte für Dateiverwaltung:
  - `GET /api/code/files` (`code/files/list`)
  - `POST /api/code/files` (`code/files/create`)
  - `GET /api/code/file` (`code/file/read`)
  - `PUT/PATCH /api/code/file` (`code/file/update`)
  - `DELETE /api/code/file` (`code/file/delete`)
- Backend-Mirror-Routen für Session-Auth über `/api/backend/code/*`.
- Neuer `CodeFileService` mit zentraler Logik für Browse/Create/Read/Update/Delete.
- Erweiterte erlaubte Textformate (u.a. `csv`, `tsv`, `log`, `rst`, `toml`, `cfg`, `properties`).

### Changed
- API-Routen werden nur registriert, wenn das `api` AddOn verfügbar ist.
- API-Dateioperationen respektieren den Schalter `enable_file_browser` und liefern bei Deaktivierung `403`.
- README erweitert um Scope-Liste, Curl-Beispiele und Copilot-Instructions-Beispiel.

### Security
- Pfadzugriffe bleiben auf den REDAXO-Basispfad beschränkt (Traversal-Schutz via `realpath`).
- Löschen geschützter Dateien bleibt blockiert (z.B. `.htaccess`, `index.php`, `composer.json`, `boot.php`, `install.php`).

## [1.2.1] - 2026-01-28
### Fixed
- Fatal Error: `CodeApi::__construct()` neu deklariert (doppelter Konstruktor entfernt).
- Fatal Error: `CodeSelfDestruct` Klasse doppelt vorhanden (Case-Sensitivity Bereinigung).
- Cleanup: Ungenutzte Datei `monaco-loader-simple.js` entfernt.
- Security: API-Zugriff weiter gehärtet.

### Added
- **Global Editor**: Monaco Editor ersetzt nun automatisch Textareas mit der Klasse `.rex-code` im gesamten Backend (wenn `be_style/codemirror` nicht aktiv ist).
- **Editor Toolbar**: Neue Toolbar über den Textareas mit nützlichen Tools.
  - **Snippets**: Umfangreiche Bibliothek für REDAXO-Module (`REX_VALUE`...), MForm (v8+), MBlock, Templates und Core-Funktionen.
  - **Fullscreen**: Echter Vollbildmodus für entspanntes Coden in engen Modul-Eingaben.
  - **Formatierung**: Code-Beautifier auf Knopfdruck.
  - **Theme Switcher**: Schneller Wechsel zwischen Dark, Light und High Contrast Mode.
- **Theme Sync**: Das gewählte Theme wird global gespeichert und synchronisiert.
- **Monaco Update**: Version auf 0.52.0 angehoben.
- **Slice Values (PHP)**: Neue Snippet-Kategorie für objektorientierten Zugriff auf Slice-Daten.

## [1.1.0] - 2026-01-28
### Added
- Code Editor im Backend integriert.
- Dateibrowser mit Dateimanagement.
- Backup & Trash System.
- Suchfunktion.
