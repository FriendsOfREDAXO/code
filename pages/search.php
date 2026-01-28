<?php

// Nur für Admins verfügbar
if (!rex::getUser()->isAdmin()) {
    echo rex_view::error('Nur für Administratoren verfügbar');
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
/* Dark Mode Override for Search */
.code-search-container .panel {
    background-color: #1e1e1e;
    color: #d4d4d4;
    border: none;
}

.code-search-container .panel-heading {
    background-color: #252526;
    color: #cccccc;
    border-bottom: 1px solid #333;
    border-radius: 0;
}

.form-control {
    background-color: #3c3c3c;
    border: 1px solid #3c3c3c;
    color: #cccccc;
}

.form-control:focus {
    background-color: #3c3c3c;
    border-color: #007fd4;
    color: #ffffff;
}

.search-result-item {
    background-color: #252526;
    padding: 0;
    margin-bottom: 15px;
    border-radius: 0 4px 4px 0;
    border: 1px solid #333;
}

.search-result-file-header {
    background-color: #2d2d2d;
    padding: 10px 15px;
    border-bottom: 1px solid #333;
    color: #e7e7e7;
}

.search-result-file {
    font-weight: bold;
    cursor: pointer;
    margin: 0;
    color: #569cd6; /* VS Code Blue */
}

.search-result-file:hover {
    color: #9cdcfe;
}

.search-result-file strong {
    font-size: 14px;
}

.search-result-match {
    padding: 8px 15px;
    border-bottom: 1px solid #333;
    transition: background-color 0.2s;
}

.search-result-match:hover {
    background-color: #2a2d2e;
}

.search-result-match:last-child {
    border-bottom: none;
}

.search-result-line-info {
    margin-bottom: 5px;
    color: #858585;
}

.search-line-btn {
    font-size: 11px;
    padding: 2px 6px;
    background-color: #0e639c;
    border: none;
    color: white;
}

.search-line-btn:hover {
    background-color: #1177bb;
}

.search-result-content {
    font-family: Monaco, Consolas, "Courier New", monospace;
    padding: 8px 12px;
    background-color: #1e1e1e;
    border: 1px solid #333;
    color: #d4d4d4;
    border-radius: 3px;
    margin-top: 5px;
    overflow-x: auto;
    cursor: pointer;
    font-size: 12px;
    line-height: 1.4;
}

.search-result-content:hover {
    box-shadow: 0 0 15px rgba(255, 255, 255, 0.1);
    border-color: #007fd4;
    transition: box-shadow 0.2s ease-in-out, border-color 0.2s ease-in-out;
}

.search-result-content mark {
    background-color: #264f78;
    color: white;
    padding: 1px 2px;
    border-radius: 2px;
    font-weight: bold;
}

.search-loading {
    text-align: center;
    padding: 20px;
    color: #cccccc;
}

.search-stats {
    padding: 10px 15px;
    border: 1px solid #333;
    background-color: #252526;
    color: #cccccc;
    border-radius: 4px;
    margin-bottom: 15px;
}

.no-results {
    text-align: center;
    padding: 40px;
    color: #858585;
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
