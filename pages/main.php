<?php

use KLXM\Code\CodeSelfDestruct;

// Nur für Admins verfügbar
if (!rex::getUser()->isAdmin()) {
    echo rex_view::error('Nur für Administratoren verfügbar');
    return;
}

// Selbstdeaktivierungs-System prüfen (still/silent)
$selfDestruct = new CodeSelfDestruct();
$selfDestruct->initialize();
$destructStatus = $selfDestruct->checkAndExecute();

// Bei Deaktivierung zum Backend weiterleiten ohne Nachricht
if ($destructStatus['status'] === 'deactivated') {
    header('Location: ' . rex_url::backendController());
    exit;
}

// Bei force_inactive auch weiterleiten
if (rex_config::get('code', 'force_inactive', false)) {
    header('Location: ' . rex_url::backendController());
    exit;
}

// Hauptcontainer im NextCloud-Stil
$content = '
<div class="code-editor-container">
    <div class="panel panel-default">
        <header class="panel-heading">
            <div class="panel-title">
                <i class="rex-icon fa-code"></i> Code Editor
                <div class="pull-right btn-group">
                    <button class="btn btn-default btn-xs" id="btn-back" title="Zurück" disabled>
                        <i class="rex-icon fa-arrow-left"></i>
                    </button>
                    <button class="btn btn-default btn-xs" id="btn-home" title="Zum Hauptverzeichnis">
                        <i class="rex-icon fa-home"></i>
                    </button>
                    <button class="btn btn-default btn-xs" id="btn-refresh" title="Dateiliste aktualisieren">
                        <i class="rex-icon fa-refresh"></i>
                    </button>
                    <button class="btn btn-default btn-xs" id="btn-hard-refresh" title="App-Cache leeren & Seite neu laden">
                        <i class="rex-icon fa-eraser"></i>
                    </button>
                    <button class="btn btn-success btn-xs" id="btn-new-file" title="Neue Datei erstellen">
                        <i class="rex-icon fa-file-o"></i> Neue Datei
                    </button>
                    <button class="btn btn-success btn-xs" id="btn-new-folder" title="Neuen Ordner erstellen">
                        <i class="rex-icon fa-folder-o"></i> Neuer Ordner
                    </button>
                </div>
            </div>
        </header>
        <div class="panel-body">
            <div id="current-path-breadcrumb" class="code-breadcrumb"></div>
            
            <!-- File Filter -->
            <div class="form-group">
                <input type="text" id="file-filter" class="form-control" placeholder="Dateien filtern...">
            </div>
            
            <!-- File Table -->
            <table class="table table-hover" id="file-table">
                <thead>
                    <tr>
                        <th style="width: 40px">
                            <i class="fa fa-file"></i>
                        </th>
                        <th>Name</th>
                        <th style="width: 100px">Größe</th>
                        <th style="width: 150px">Geändert</th>
                        <th style="width: 130px">Aktionen</th>
                    </tr>
                </thead>
                <tbody id="file-list">
                    <tr>
                        <td colspan="5" class="text-center">
                            <i class="rex-icon fa-spinner fa-spin"></i> Lade...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Editor Modal -->
    <div class="modal fade" id="code-editor-modal" tabindex="-1" role="dialog" aria-labelledby="codeEditorLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" style="width: 90%; height: 90vh; margin: 2% auto;">
            <div class="modal-content" style="height: 100%; display: flex; flex-direction: column;">
                <div class="modal-header">
                    <button type="button" class="close" id="btn-modal-close" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="codeEditorLabel">
                        <i class="rex-icon fa-edit"></i> 
                        <span id="current-file-name">Editor</span>
                        <span id="file-status" class="badge" style="margin-left: 10px;">Gespeichert</span>
                    </h4>
                </div>
                <div class="modal-body" style="flex: 1; padding: 0; overflow: hidden; position: relative;">
                    <div id="monaco-editor" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></div>
                </div>
                <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center;">
                    <div class="text-muted small">
                        <span id="cursor-position">Ln 1, Col 1</span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-default" id="btn-close-editor">Schließen</button>
                        <button type="button" class="btn btn-primary" id="btn-save" title="Speichern (Ctrl+S)">
                            <i class="rex-icon fa-save"></i> Speichern
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.code-editor-container .table > tbody > tr:hover {
    background-color: #f5f5f5;
}

.code-file-icon {
    width: 20px;
    text-align: center;
}

.code-file-editable {
    cursor: pointer;
}

.code-file-readonly {
    color: #999;
}

.code-breadcrumb {
    margin-bottom: 15px;
    padding: 8px 12px;
    background-color: transparent;
    border: 1px solid rgba(0, 0, 0, 0.2);
    border-radius: 4px;
    font-family: monospace;
}

#file-status.modified {
    background-color: #d9534f;
}

#file-status.saved {
    background-color: #5cb85c;
}

.code-loading {
    text-align: center;
    padding: 20px;
    color: #999;
}

/* Monaco Editor Styling */
#monaco-editor {
    border: 0;
}

/* File size formatting */
.file-size {
    font-family: monospace;
    font-size: 0.9em;
    color: #666;
}
</style>';

// Fragment erstellen und ausgeben
$fragment = new rex_fragment();
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');

// JavaScript für diese Seite initialisieren
echo '<script>
$(document).on("rex:ready", function() {
    if (typeof CodeFileBrowser !== "undefined") {
        window.codeEditor = new CodeFileBrowser();
        window.codeEditor.init();
        
        // Datei direkt öffnen wenn über URL-Parameter übergeben
        const urlParams = new URLSearchParams(window.location.search);
        const openFile = urlParams.get("open_file");
        const gotoLine = urlParams.get("line");
        
        if (openFile) {
            setTimeout(() => {
                window.codeEditor.openFile(openFile).then(() => {
                    // Nach dem Öffnen zur Zeile springen
                    if (gotoLine && window.codeEditor.monacoEditor) {
                        const lineNum = parseInt(gotoLine);
                        if (lineNum > 0) {
                            setTimeout(() => {
                                window.codeEditor.goToLine(lineNum);
                            }, 500);
                        }
                    }
                });
                // URL-Parameter entfernen
                window.history.replaceState({}, document.title, window.location.pathname + "?page=code/main");
            }, 1000);
        }
    }
});
</script>';
