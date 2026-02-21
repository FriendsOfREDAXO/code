<?php

namespace FriendsOfRedaxo\Code;

use rex_addon;
use rex_config;
use rex_dir;
use rex_logger;
use rex_path;
use rex_session;
use Exception;

/**
 * Code Editor Self-Destruct System
 * Automatisches Deaktivieren des File-Browsers nach einer konfigurierbaren Zeit
 * Das AddOn selbst bleibt aktiv, nur der File-Browser wird gesperrt
 */
class CodeSelfDestruct
{
    private rex_addon $addon;
    private int $autoDeactivateDays;
    private int $warningHours;
    
    public function __construct()
    {
        $this->addon = rex_addon::get('code');
        $this->autoDeactivateDays = (int) $this->addon->getConfig('auto_deactivate_after_days', 2);
        $this->warningHours = (int) $this->addon->getConfig('warning_hours_before', 24);
    }
    
    /**
     * Initialisiert das Deaktivierungssystem beim ersten Start
     */
    public function initialize(): void
    {
        if ($this->autoDeactivateDays <= 0) {
            return; // Auto-Deaktivierung deaktiviert
        }
        
        $installTime = $this->getInstallTime();
        if (!$installTime) {
            $this->setInstallTime();
            rex_logger::factory()->info('Code Editor: File-Browser Auto-Deaktivierung aktiviert - Deaktivierung in ' . $this->autoDeactivateDays . ' Tagen');
        }
    }
    
    /**
     * Prüft ob der File-Browser deaktiviert werden soll
     */
    public function checkAndExecute(): array
    {
        if ($this->autoDeactivateDays <= 0) {
            return ['status' => 'disabled'];
        }
        
        $installTime = $this->getInstallTime();
        if (!$installTime) {
            return ['status' => 'no_install_time'];
        }
        
        $now = time();
        $deactivationTime = $installTime + ($this->autoDeactivateDays * 24 * 60 * 60);
        $warningTime = $deactivationTime - ($this->warningHours * 60 * 60);
        
        // Zeit abgelaufen - File-Browser deaktivieren
        if ($now >= $deactivationTime) {
            return $this->deactivateFileBrowser();
        }
        
        // Warnung anzeigen
        if ($now >= $warningTime) {
            $hoursLeft = ceil(($deactivationTime - $now) / 3600);
            return [
                'status' => 'warning',
                'hours_left' => $hoursLeft,
                'deactivation_time' => date('d.m.Y H:i:s', $deactivationTime)
            ];
        }
        
        // Alles normal
        $daysLeft = ceil(($deactivationTime - $now) / (24 * 60 * 60));
        return [
            'status' => 'active',
            'days_left' => $daysLeft,
            'deactivation_time' => date('d.m.Y H:i:s', $deactivationTime)
        ];
    }
    
    /**
     * Deaktiviert den File-Browser (nicht das ganze AddOn)
     */
    private function deactivateFileBrowser(): array
    {
        try {
            rex_logger::factory()->warning('Code Editor: File-Browser wird automatisch deaktiviert');
            
            // File-Browser Config deaktivieren
            rex_config::set('code', 'enable_file_browser', false);
            
            rex_logger::factory()->info('Code Editor: File-Browser deaktiviert. Kann in Einstellungen wieder aktiviert werden.');
            
            return [
                'status' => 'deactivated',
                'message' => 'File-Browser wurde automatisch deaktiviert. Sicherheitszeitlimit erreicht. Kann in Einstellungen wieder aktiviert werden.'
            ];
            
        } catch (Exception $e) {
            rex_logger::factory()->error('Code Editor: Fehler bei File-Browser-Deaktivierung: ' . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Fehler bei der Deaktivierung: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Löscht alle Backup-Dateien
     */
    private function cleanupBackups(): void
    {
        $backupDir = $this->addon->getDataPath('backups');
        if (is_dir($backupDir)) {
            $files = glob($backupDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rex_dir::delete($backupDir);
        }
    }
    
    /**
     * Löscht alle Trash-Dateien
     */
    private function cleanupTrash(): void
    {
        $trashDir = $this->addon->getDataPath('trash');
        if (is_dir($trashDir)) {
            // Trash-Dateien
            $files = glob($trashDir . '/*.trash');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            
            // Meta-Dateien
            $metaFiles = glob($trashDir . '/*.meta');
            foreach ($metaFiles as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            
            rex_dir::delete($trashDir);
        }
    }
    
    /**
     * Löscht Addon-Assets
     */
    private function cleanupAssets(): void
    {
        $assetsDir = rex_path::assets('addons/code');
        if (is_dir($assetsDir)) {
            rex_dir::delete($assetsDir);
        }
    }
    
    /**
     * Löscht Addon-Daten
     */
    private function cleanupData(): void
    {
        $dataDir = $this->addon->getDataPath();
        if (is_dir($dataDir)) {
            rex_dir::delete($dataDir);
        }
        
        // Konfiguration löschen
        rex_config::removeNamespace('code');
    }
    
    /**
     * Deinstalliert das Addon
     */
    private function uninstallAddon(): void
    {
        // Addon deaktivieren
        $this->addon->setProperty('status', false);
        
        // Versuche das Addon zu deinstallieren
        // Das wird nur funktionieren wenn wir nicht gerade im Addon-Kontext sind
        try {
            if (method_exists($this->addon, 'uninstall')) {
                $this->addon->uninstall();
            }
        } catch (Exception $e) {
            // Fehlschlag ist OK - das Addon wird beim nächsten Neustart nicht mehr geladen
            rex_logger::factory()->info('Code Editor: Addon-Deinstallation nicht möglich (normal bei Selbstlöschung)');
        }
    }
    
    /**
     * Gibt die Installationszeit zurück
     */
    private function getInstallTime(): ?int
    {
        $time = rex_config::get('code', 'install_time');
        return $time ? (int) $time : null;
    }
    
    /**
     * Setzt die Installationszeit
     */
    private function setInstallTime(): void
    {
        rex_config::set('code', 'install_time', time());
    }
    
    /**
     * Manuelle Verlängerung der Laufzeit
     */
    public function extendLifetime(int $additionalDays = 2): bool
    {
        try {
            $currentTime = $this->getInstallTime();
            if (!$currentTime) {
                return false;
            }
            
            $newTime = $currentTime + ($additionalDays * 24 * 60 * 60);
            rex_config::set('code', 'install_time', time() - ($this->autoDeletionDays * 24 * 60 * 60) + ($additionalDays * 24 * 60 * 60));
            
            rex_logger::factory()->info('Code Editor: Laufzeit um ' . $additionalDays . ' Tage verlängert');
            return true;
            
        } catch (Exception $e) {
            rex_logger::factory()->error('Code Editor: Fehler bei Laufzeitverlängerung: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Deaktiviert das Selbstlöschsystem
     */
    public function disable(): bool
    {
        try {
            rex_config::set('code', 'auto_delete_after_days', 0);
            rex_logger::factory()->info('Code Editor: Selbstlöschsystem deaktiviert');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Gibt Status-Informationen zurück
     */
    public function getStatus(): array
    {
        if ($this->autoDeletionDays <= 0) {
            return [
                'enabled' => false,
                'message' => 'Selbstlöschsystem ist deaktiviert'
            ];
        }
        
        $installTime = $this->getInstallTime();
        if (!$installTime) {
            return [
                'enabled' => true,
                'message' => 'Installationszeit nicht gefunden'
            ];
        }
        
        $now = time();
        $deletionTime = $installTime + ($this->autoDeletionDays * 24 * 60 * 60);
        $timeLeft = $deletionTime - $now;
        
        if ($timeLeft <= 0) {
            return [
                'enabled' => true,
                'expired' => true,
                'message' => 'Selbstlöschung überfällig'
            ];
        }
        
        $daysLeft = ceil($timeLeft / (24 * 60 * 60));
        $hoursLeft = ceil($timeLeft / 3600);
        
        return [
            'enabled' => true,
            'expired' => false,
            'install_time' => date('d.m.Y H:i:s', $installTime),
            'deletion_time' => date('d.m.Y H:i:s', $deletionTime),
            'days_left' => $daysLeft,
            'hours_left' => $hoursLeft,
            'warning_active' => $timeLeft <= ($this->warningHours * 3600),
            'message' => $daysLeft > 1 ? 
                "Selbstlöschung in {$daysLeft} Tagen" : 
                "Selbstlöschung in {$hoursLeft} Stunden"
        ];
    }
    
    /**
     * Setzt den Timer zurück (als ob AddOn gerade installiert wurde)
     * Wird aufgerufen wenn File-Browser reaktiviert wird
     * 
     * @return bool True bei Erfolg
     */
    public function resetTimer(): bool
    {
        $now = time();
        
        try {
            rex_config::set('code', 'install_time', $now);
            
            rex_logger::factory()->log('info', sprintf(
                'Code AddOn: File-Browser Timer zurückgesetzt auf %s',
                date('d.m.Y H:i:s', $now)
            ));
            
            return true;
        } catch (Exception $e) {
            rex_logger::factory()->log('error', sprintf(
                'Code AddOn: Fehler beim Zurücksetzen des Timers: %s',
                $e->getMessage()
            ));
            
            return false;
        }
    }
}
