<?php
/**
 * Code AddOn - Settings Page
 * Konfiguration für Editor-Verhalten und Einstellungen
 */

$addon = rex_addon::get('code');

// Config-Formular erstellen
$form = rex_config_form::factory($addon->getName());

$field = $form->addFieldset($addon->i18n('settings_editor_title'));

// Monaco Editor für .rex-code Textareas aktivieren
$field = $form->addSelectField('replace_rex_code');
$field->setLabel($addon->i18n('settings_replace_rex_code_label'));
$field->setNotice($addon->i18n('settings_replace_rex_code_notice'));
$select = $field->getSelect();
$select->addOption('Aktiviert', '1');
$select->addOption('Deaktiviert', '0');

// Zweite Fieldset: File-Browser & Backup
$field = $form->addFieldset($addon->i18n('settings_backup_title'));

// File-Browser aktivieren
$field = $form->addSelectField('enable_file_browser');
$field->setLabel($addon->i18n('settings_enable_file_browser_label'));
$field->setNotice($addon->i18n('settings_enable_file_browser_notice'));
$select = $field->getSelect();
$select->addOption('Aktiviert', '1');
$select->addOption('Deaktiviert', '0');

// Auto-Backup beim Speichern
$field = $form->addSelectField('auto_backup_on_save');
$field->setLabel($addon->i18n('settings_auto_backup_label'));
$field->setNotice($addon->i18n('settings_auto_backup_notice'));
$select = $field->getSelect();
$select->addOption('Aktiviert', '1');
$select->addOption('Deaktiviert', '0');

// Backup-Limit
$field = $form->addInputField('text', 'backup_limit');
$field->setLabel($addon->i18n('settings_backup_limit_label'));
$field->setNotice($addon->i18n('settings_backup_limit_notice'));
$field->setAttribute('type', 'number');
$field->setAttribute('min', '5');
$field->setAttribute('max', '100');
$field->setAttribute('step', '5');

// Trash-Limit
$field = $form->addInputField('text', 'trash_limit');
$field->setLabel($addon->i18n('settings_trash_limit_label'));
$field->setNotice($addon->i18n('settings_trash_limit_notice'));
$field->setAttribute('type', 'number');
$field->setAttribute('min', '10');
$field->setAttribute('max', '100');
$field->setAttribute('step', '10');

// Fragment für den Wrapper
$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $addon->i18n('settings_title'), false);
$fragment->setVar('body', $form->get(), false);
echo $fragment->parse('core/page/section.php');
