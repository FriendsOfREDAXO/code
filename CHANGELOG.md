# Changelog

## [1.2.0] - 2026-01-28
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
