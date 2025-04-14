<?php
/*
 * OPENCATS
 * AJAX Backup interface
 */
@ini_set('memory_limit', '512M');

require_once __DIR__ . '/../../../config.php'; // Load config

// Ensure LEGACY_ROOT is set
if (!defined('LEGACY_ROOT')) {
    die('Error: LEGACY_ROOT is not defined in backup.php!');
}

// Include required files
require_once LEGACY_ROOT . '/constants.php';  // Ensure ACCESS_LEVEL_SA is defined
require_once LEGACY_ROOT . '/lib/ACL.php';
require_once LEGACY_ROOT . '/lib/Session.php';
require_once LEGACY_ROOT . '/lib/AJAXInterface.php';

// Ensure session is started if not active
if (session_status() === PHP_SESSION_NONE) {
    session_name(CATS_SESSION_NAME);
    session_start();
}

// Debugging: Check if $_SESSION['CATS'] is set
if (!isset($_SESSION['CATS'])) {
    error_log('Error: $_SESSION["CATS"] is not set in backup.php!');
    require_once LEGACY_ROOT . '/lib/Session.php'; // Ensure dependencies are loaded
    $_SESSION['CATS'] = new CATSSession();  // Initialize session object
}

// Secure AJAX Interface
$interface = new SecureAJAXInterface();





if ($_SESSION['CATS']->getAccessLevel(ACL::SECOBJ_ROOT) < ACCESS_LEVEL_SA) {
    die('No permission.');
}

if (!isset($_REQUEST['a'])) {
    die('No action.');
}

$action = $_REQUEST['a'];
$backupDir = LEGACY_ROOT . '/backups';

if ($action === 'start') {
    echo '<script type="text/javascript">startBackupProcess();</script>';
}

if ($action === 'backup') {
    if (ini_get('safe_mode')) {
        // Don't do anything in safe mode
    } else {
        set_time_limit(0); // Don't limit execution time
    }

    $backupCommand = "php " . LEGACY_ROOT . "/modules/install/backupDB.php backup";
    exec($backupCommand, $output, $returnVar);

    if ($returnVar !== 0) {
        echo json_encode(['status' => 'error', 'message' => 'Backup failed.']);
        exit;
    }

    echo json_encode(['status' => 'success', 'message' => 'Backup completed']);
    exit;
}

if ($action === 'list') {
    $backups = glob("$backupDir/backup_*.sql.gz");
    $attachments = glob("$backupDir/attachments_*.tar.gz");

    $backupList = [];
    foreach ($backups as $backup) {
        $timestamp = preg_replace('/[^0-9_]/', '', basename($backup, ".sql.gz"));
        $attachmentsFile = "$backupDir/attachments_$timestamp.tar.gz";

        $backupList[] = [
            'database' => basename($backup),
            'attachments' => file_exists($attachmentsFile) ? basename($attachmentsFile) : 'N/A',
            'timestamp' => $timestamp
        ];
    }

    echo json_encode($backupList);
    exit;
}

if ($action === 'delete') {
    if (!isset($_REQUEST['timestamp'])) {
        die('Invalid request.');
    }

    $timestamp = preg_replace('/[^0-9_]/', '', $_REQUEST['timestamp']);
    $backupFile = "$backupDir/backup_$timestamp.sql.gz";
    $attachmentsFile = "$backupDir/attachments_$timestamp.tar.gz";

    if (file_exists($backupFile)) {
        unlink($backupFile);
    }
    if (file_exists($attachmentsFile)) {
        unlink($attachmentsFile);
    }

    echo json_encode(['status' => 'success', 'message' => 'Backup deleted.']);
    exit;
}

die('Invalid action.');
