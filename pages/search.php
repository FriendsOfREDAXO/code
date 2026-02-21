<?php

use FriendsOfRedaxo\Code\CodeSelfDestruct;

$addon = rex_addon::get('code');

// Nur für Admins verfügbar
if (!rex::getUser()->isAdmin()) {
    echo rex_view::error('Nur für Administratoren verfügbar');
    return;
}

// Aktivierung über POST-Request
if (rex_post('enable_file_browser', 'string') === '1') {
    rex_config::set('code', 'enable_file_browser', true);
    // Timer zurücksetzen
    $selfDestruct = new CodeSelfDestruct();
    $selfDestruct->resetTimer();
    echo rex_view::success('File-Browser wurde aktiviert. Auto-Deaktivierung in 2 Tagen.');
}

// Prüfe ob File-Browser aktiviert ist
$fileBrowserEnabled = rex_config::get('code', 'enable_file_browser', true);

if (!$fileBrowserEnabled) {
    // Aktivierungs-Formular mit Warnung anzeigen
    $activationPanel = '
    <div class="panel panel-warning">
        <header class="panel-heading">
            <div class="panel-title">
                <i class="rex-icon fa-exclamation-triangle"></i> ' . $addon->i18n('file_browser_deactivated_title') . '
            </div>
        </header>
        <div class="panel-body">
            <p>' . $addon->i18n('file_browser_deactivated_notice') . '</p>
            
            <div class="alert alert-danger" style="margin-top: 15px;">
                <h4><i class="fa fa-exclamation-triangle"></i> ' . $addon->i18n('file_browser_security_warning') . '</h4>
                <p>' . $addon->i18n('file_browser_security_info') . '</p>
                <ul>
                    <li>' . $addon->i18n('file_browser_security_point_1') . '</li>
                    <li>' . $addon->i18n('file_browser_security_point_2') . '</li>
                    <li>' . $addon->i18n('file_browser_security_point_3') . '</li>
                    <li>' . $addon->i18n('file_browser_security_point_4') . '</li>
                    <li>' . $addon->i18n('file_browser_security_point_5') . '</li>
                </ul>
            </div>
            
            <form method="post" style="margin-top: 15px;">
                <input type="hidden" name="enable_file_browser" value="1">
                <button type="submit" class="btn btn-warning">
                    <i class="fa fa-unlock"></i> ' . $addon->i18n('file_browser_activate_button') . '
                </button>
                <p class="help-block" style="margin-top: 10px;">
                    ' . $addon->i18n('file_browser_activate_help') . '
                </p>
            </form>
        </div>
    </div>
    ';
    
    echo $activationPanel;
    return;
}

$content = '
<div class="code-search-container">
    <div class="panel panel-default">
        <header class="panel-heading">
            <div class="panel-title">
                <i class="rex-icon fa-search"></i> Code-Suche
            </div>
        </header>
        <div class="panel-body">
            <form id="search-form" class="form-horizontal">
                <div class="form-group">
                    <label for="search-term" class="col-sm-2 control-label">Suchbegriff</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" id="search-term" name="term" 
                               placeholder="Funktion, Klasse, Variable oder Text suchen..." 
                               required minlength="2">
                    </div>
                    <div class="col-sm-2">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fa fa-search"></i> Suchen
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Search Results -->
    <div class="panel panel-default" id="search-results" style="display: none;">
        <header class="panel-heading">
            <div class="panel-title">
                <i class="rex-icon fa-list"></i> 
                <span id="results-title">Suchergebnisse</span>
            </div>
        </header>
        <div class="panel-body">
            <div id="search-results-content"></div>
        </div>
    </div>
</div>

<style>
/* Force Dark Mode Override for Search with higher specificity */
.code-search-container,
.code-search-container .panel,
.code-search-container .panel-default,
.code-search-container .panel-body,
.code-search-container .panel-heading {
    background-color: #1e1e1e !important;
    border-color: #333 !important;
    color: #d4d4d4 !important;
}

/* Remove white backgrounds from panels */
.code-search-container .panel-default > .panel-heading {
    background-color: #252526 !important;
    border-bottom: 1px solid #333 !important;
    color: #cccccc !important;
}

/* Form Inputs */
.code-search-container .form-control {
    background-color: #3c3c3c !important;
    border: 1px solid #3c3c3c !important;
    color: #cccccc !important;
    box-shadow: none !important;
}

.code-search-container .form-control:focus {
    background-color: #3c3c3c !important;
    border-color: #007fd4 !important;
    color: #ffffff !important;
}

/* Buttons */
.code-search-container .btn-primary {
    background-color: #0e639c !important;
    border-color: #0e639c !important;
    color: #ffffff !important;
}

.code-search-container .btn-primary:hover {
    background-color: #1177bb !important;
}

.code-search-container .btn-success {
    background-color: #388a34 !important; /* VS Code Green */
    border-color: #388a34 !important;
}

/* Search Results Wrapper */
#search-results-content {
    background-color: #1e1e1e !important;
}

.search-result-item {
    background-color: #252526 !important;
    padding: 0;
    margin-bottom: 15px;
    border-radius: 4px;
    border: 1px solid #333 !important;
    box-shadow: none !important;
}

.search-result-file-header {
    background-color: #2d2d2d !important;
    padding: 10px 15px;
    border-bottom: 1px solid #333 !important;
    color: #e7e7e7 !important;
    border-radius: 4px 4px 0 0;
}

.search-result-file {
    font-weight: bold;
    cursor: pointer;
    margin: 0;
    color: #569cd6 !important; /* VS Code Blue */
}

.search-result-file:hover {
    color: #9cdcfe !important;
}

.search-result-match {
    background-color: #1e1e1e !important; /* Force dark background */
    padding: 10px 15px;
    border-bottom: 1px solid #333 !important;
    transition: background-color 0.2s;
}

.search-result-match:hover {
    background-color: #2a2d2e !important;
}

.search-result-match:last-child {
    border-bottom: none !important;
    border-radius: 0 0 4px 4px;
}

/* Line Number "Badge" */
.search-line-btn {
    font-size: 11px;
    padding: 2px 6px;
    background-color: #2d2d2d !important;
    border: 1px solid #444 !important;
    color: #cccccc !important;
    border-radius: 3px;
    font-family: monospace;
}

.search-line-btn:hover {
    background-color: #0e639c !important;
    color: #fff !important;
    border-color: #0e639c !important;
}

/* Code Snippet */
.search-result-content {
    font-family: "Menlo", "Monaco", "Courier New", monospace;
    padding: 8px 12px;
    background-color: #1e1e1e !important; /* Matches main bg */
    border: 1px solid #333 !important;
    color: #d4d4d4 !important;
    border-radius: 3px;
    margin-top: 5px;
    overflow-x: auto;
    cursor: pointer;
    font-size: 12px;
    line-height: 1.5;
}

.search-result-content:hover {
    border-color: #007fd4 !important;
    box-shadow: 0 0 8px rgba(0, 127, 212, 0.45) !important;
}

.search-result-content mark {
    background-color: #264f78 !important; /* VS Code Selection/Find Match Color */
    color: #d4d4d4 !important; 
    padding: 0 2px;
    border-radius: 2px;
    font-weight: normal;
}

/* Stats Box */
.search-stats {
    padding: 10px 15px;
    border: 1px solid #007fd4 !important;
    background-color: #1e1e1e !important;
    color: #d4d4d4 !important;
    border-left-width: 4px !important;
    border-radius: 4px;
    margin-bottom: 15px;
}

/* Info Icon in Stats */
.search-stats .fa {
    color: #007fd4;
}

.no-results {
    text-align: center;
    padding: 40px;
    color: #858585 !important;
}


/* Zeilen-Highlighting im Editor */
.highlight-line {
    background-color: rgba(255, 235, 59, 0.3) !important;
    animation: fadeOut 3s ease-out;
}

@keyframes fadeOut {
    0% { background-color: rgba(255, 235, 59, 0.8) !important; }
    100% { background-color: rgba(255, 235, 59, 0.1) !important; }
}
</style>';

// Fragment erstellen und ausgeben
$fragment = new rex_fragment();
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');

// JavaScript für Suche
echo '<script>
$(document).on("rex:ready", function() {
    if (typeof CodeFileSearch !== "undefined") {
        window.codeSearch = new CodeFileSearch();
        window.codeSearch.init();
    }
});
</script>';
