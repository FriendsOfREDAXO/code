<?php

/**
 * Code Editor AddOn Installation
 */

$addon = rex_addon::get('code');

// Backup-Verzeichnis erstellen
$backupDir = $addon->getDataPath('backups');
if (!is_dir($backupDir)) {
    rex_dir::create($backupDir);
}

// Htaccess für Backup-Schutz
$htaccessContent = "Order Deny,Allow\nDeny from all";
rex_file::put($backupDir . '/.htaccess', $htaccessContent);

// Standard-Konfiguration setzen (falls noch nicht vorhanden)
if (!$addon->hasConfig('replace_rex_code')) {
    $addon->setConfig('replace_rex_code', '1'); // Standardmäßig aktiviert
}
if (!$addon->hasConfig('enable_file_browser')) {
    $addon->setConfig('enable_file_browser', '1');
}
if (!$addon->hasConfig('auto_backup_on_save')) {
    $addon->setConfig('auto_backup_on_save', '1');
}
if (!$addon->hasConfig('backup_limit')) {
    $addon->setConfig('backup_limit', '10');
}
if (!$addon->hasConfig('trash_limit')) {
    $addon->setConfig('trash_limit', '50');
}

#$this->setProperty('installmsg', 'Code Editor wurde erfolgreich installiert!');
