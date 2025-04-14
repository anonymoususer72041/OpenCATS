<?php

/**
 *
 * OPENCATS
 * Backup library
 *
 */

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../config.php'; // Load OpenCATS configuration

use Spatie\DbDumper\Databases\MySql;
use Spatie\DbDumper\Compressors\GzipCompressor;
use PhpMyAdmin\SqlParser\Parser;

// Load database credentials from config.php
$DB_HOST = defined('DATABASE_HOST') ? DATABASE_HOST : 'localhost';
$DB_NAME = defined('DATABASE_NAME') ? DATABASE_NAME : 'opencats';
$DB_USER = defined('DATABASE_USER') ? DATABASE_USER : 'root';
$DB_PASS = defined('DATABASE_PASS') ? DATABASE_PASS : '';
$BACKUP_DIR = realpath(__DIR__ . '/../../') . '/backups';
$ATTACHMENTS_DIR = __DIR__ . '/../../attachments';

if (!is_dir($BACKUP_DIR)) {
    mkdir($BACKUP_DIR, 0777, true);
}

$timestamp = date('Ymd_His');
$backupFile = sprintf('%s/backup_%s.sql.gz', $BACKUP_DIR, $timestamp);
$attachmentsBackupFile = sprintf('%s/attachments_%s.tar.gz', $BACKUP_DIR, $timestamp);

function backupDatabase($dbHost, $dbName, $dbUser, $dbPass, $backupFile)
{
    try {
        MySql::create()
        ->setHost($dbHost)
        ->setDbName($dbName)
        ->setUserName($dbUser)
        ->setPassword($dbPass)
        ->useCompressor(new GzipCompressor())
        ->dumpToFile($backupFile);

        echo "Database backup created successfully: $backupFile\n";
    } catch (Exception $e) {
        echo "Database backup failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function backupAttachments($attachmentsDir, $backupFile)
{
    if (!is_dir($attachmentsDir)) {
        echo "Attachments directory not found: $attachmentsDir\n";
        return;
    }
    $command = "tar -czf \"$backupFile\" -C \"$attachmentsDir\" .";
    exec($command, $output, $returnVar);
    if ($returnVar === 0) {
        echo "Attachments backup created successfully: $backupFile\n";
    } else {
        echo "Attachments backup failed.\n";
        exit(1);
    }
}

function restoreDatabase($dbHost, $dbName, $dbUser, $dbPass, $backupFile)
{
    if (!file_exists($backupFile)) {
        echo "Database backup file not found: $backupFile\n";
        exit(1);
    }
    try {
        $sql = gzdecode(file_get_contents($backupFile));

        // Remove all problematic SET statements to avoid MySQL restore issues
        $sql = preg_replace("/SET .*?;/i", "", $sql);
        $sql = preg_replace("/SET .*?= NULL;/i", "", $sql);

        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        // Drop all existing tables before restoring
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `$table`;");
        }

        $parser = new Parser($sql);
        foreach ($parser->statements as $statement) {
            $pdo->exec($statement->build());
        }

        echo "Database restored successfully from $backupFile\n";
    } catch (Exception $e) {
        echo "Restore failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function restoreAttachments($backupFile, $restoreDir)
{
    if (!file_exists($backupFile)) {
        echo "Attachments backup file not found: $backupFile\n";
        return;
    }
    $command = "tar --no-same-owner --no-same-permissions --no-overwrite-dir -xzf \"$backupFile\" -C \"$restoreDir\"";


    exec($command, $output, $returnVar);
    if ($returnVar === 0) {
        echo "Attachments restored successfully to $restoreDir\n";
    } else {
        echo "Attachments restore failed.\n";
        exit(1);
    }
}

// Command-line usage
if ($argc < 2) {
    echo "Usage: php backupDB.php [backup|restore] [file]\n";
    exit(1);
}

$action = $argv[1];
$file = $argv[2] ?? '';

if ($action === 'backup') {
    backupDatabase($DB_HOST, $DB_NAME, $DB_USER, $DB_PASS, $backupFile);
    backupAttachments($ATTACHMENTS_DIR, $attachmentsBackupFile);
} elseif ($action === 'restore') {
    if (!file_exists($file)) {
        echo "Specified backup file does not exist: $file\n";
        exit(1);
    }
    restoreDatabase($DB_HOST, $DB_NAME, $DB_USER, $DB_PASS, $file);

    $fileTimestamp = preg_replace('/backup_|[^0-9_]/', '', basename($file));

    $attachmentsRestoreFile = sprintf('%s/attachments_%s.tar.gz', $BACKUP_DIR, $fileTimestamp);
    restoreAttachments($attachmentsRestoreFile, $ATTACHMENTS_DIR);
} else {
    echo "Invalid command. Use 'backup' or 'restore'.\n";
    exit(1);
}
