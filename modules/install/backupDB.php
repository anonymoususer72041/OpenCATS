<?php
/*
 * CATS
 * Database Backup Script
 *
 * Copyright (C) 2005 - 2007 Cognizo Technologies, Inc.
 *
 * The contents of this file are subject to the CATS Public License
 * Version 1.1a (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.catsone.com/.
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "CATS Standard Edition".
 *
 * The Initial Developer of the Original Code is Cognizo Technologies, Inc.
 * Portions created by the Initial Developer are Copyright (C) 2005 - 2007
 * (or from the year in which this file was created to the year 2007) by
 * Cognizo Technologies, Inc. All Rights Reserved.
 *
 * $Id: backupDB.php 3797 2007-12-04 17:13:21Z brian $
 */


/* Dumps the entire database schema for the currents site into $file, and
 * splits it up into ~1MB chunks with the naming convention $file.(number).
 *
 * The function returns the total number of chunks.
 *
 * If $useStatus is true, use setStatusBackup(status) to display progress.
 */

function BackupDBErrorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
    // Get more context from the error context (if available)
    $contextInfo = "";
    if (is_array($errcontext)) {
        $contextInfo = "Context: " . json_encode($errcontext) . "\n";
    }

    // Construct a detailed error message
    $errorMessage = "**Backup Error**\n" .
    "Time: " . date("Y-m-d H:i:s") . "\n" .
    "Error Number: " . $errno . "\n" .
    "Error Message: " . $errstr . "\n" .
    "File: " . $errfile . "\n" .
    "Line: " . $errline . "\n" .
    $contextInfo;

    // Log the error (example using a dedicated log file)
    $logFile = "backup_errors.log";
    error_log($errorMessage . "\n", 3, $logFile);

    // Display a user-friendly error message
    echo "An error occurred during the backup process. Please check the logs for details.";

    // Exit the script
    die();
}

function dumpDB($db, $file, $useStatus = false, $splitFiles = true, $siteID = -1)
{
    // Use set_error_handler with a custom function for better error handling (optional)
    // set_error_handler('BackupDBErrorHandler');

    if ($siteID === -1) {
        $siteID = $_SESSION['CATS']->getSiteID();
    }

    $len = 0;
    $fileNumber = 0;

    $connection = $db->getConnection();

    $text = '';

    $result = mysqli_query($connection, sprintf("SHOW TABLES FROM `%s`", DATABASE_NAME));
    $tables = array_map(fn($row) => $row[0], mysqli_fetch_all($result, MYSQLI_NUM));

    if ($splitFiles) {
        $fh = fopen($file . '.' . $fileNumber, 'w');
    }
    $fh2 = fopen($file, 'w');

    $tableCounter = 0;
    $totalTables = count($tables);
    foreach ($tables as $table) {
        ++$tableCounter;

        if (in_array($table, [
            'arb_queue',
            'prepaid_payment',
            'monthly_payment',
            'address_parser_failures',
            'admin_user',
            'admin_user_login',
            'candidate_joborder_status_type',
            'timecard_user',
            'word_verification',
        ])) {
            continue;
        }

        $text .= 'DROP TABLE IF EXISTS `' . $table . '`((ENDOFQUERY))' . "\n";
        $sql = 'SHOW CREATE TABLE ' . $table;
        $rs = mysqli_query($connection, $sql);
        if ($rs) {
            if ($row = mysqli_fetch_assoc($rs)) {
                $text .= $row['Create Table'] . "((ENDOFQUERY))\n\n";
            }
        }

        if ($table === 'history') {
            continue;
        }

        $isSiteIdColumn = false;
        $sql = sprintf("SHOW COLUMNS FROM %s", $table);
        $rs = mysqli_query($connection, $sql);
        while ($recordSet = mysqli_fetch_assoc($rs)) {
            if ($recordSet['Field'] === 'site_id') {
                $isSiteIdColumn = true;
                break;
            }
        }

        $sql = $isSiteIdColumn ? 'SELECT * FROM ' . $table . ' WHERE site_id = ' . $siteID : 'SELECT * FROM ' . $table . '';
        $rs = mysqli_query($connection, $sql);
        $index = 0;
        while ($recordSet = mysqli_fetch_assoc($rs)) {
            $continue = true;

            if (isset($recordSet['site_id'])) {
                if ($recordSet['site_id'] !== $siteID) {
                    $continue = ($table === 'site' && $recordSet['site_id'] === CATS_ADMIN_SITE) ||
                    ($table === 'user' && $recordSet['password'] === 'cantlogin' && $recordSet['site_id'] === CATS_ADMIN_SITE);
                } else {
                    $continue = $table !== 'user' || ($recordSet['user_name'] !== 'brian' && $recordSet['email'] !== 'brian@catsone.com');
                }
            }

            $continue = $continue && ($table !== 'user_login' && $table !== 'zipcodes');

            if ($continue) {
                if ($table === 'site') {
                    unset($recordSet['unix_name'], $recordSet['company_id'], $recordSet['is_free'],
                          $recordSet['size_limit'], $recordSet['user_licenses'], $recordSet['invoice_number']);
                    $recordSet['account_active'] = 1;
                }

                if ($table === 'user') {
                    if (strpos($recordSet['user_name'], '@' . $siteID) !== false) {
                        $recordSet['user_name'] = str_replace('@' . $siteID, '', $recordSet['user_name']);
                    }
                    if (strtolower($recordSet['user_name']) === 'john@mycompany.net') {
                        $recordSet['access_level'] = 500;
                    }
                }

                if ($index === 0) {
                    $text .= 'INSERT INTO `' . $table . '` VALUES ' . "\n";
                } else {
                    $text .= ",\n";
                }

                $text .= '(';
                $i = 0;
                foreach ($recordSet as $field) {
                    $text .= "'" . mysqli_real_escape_string($connection, $field) . "'";
                    $i++;
                    if ($i != count($recordSet)) {
                        $text .= ',';
                    }
                }
                $text .= ")";
                $index++;

                if ($splitFiles) {
                    fwrite($fh, $text);
                }
                $text = str_replace('((ENDOFQUERY))', ';', $text);
                fwrite($fh2, $text);
                $text = '';

                if ($len > 1000000 && $splitFiles) {
                    $text .= "((ENDOFQUERY))\n\n\n";
                    $index = 0;
                    $len = 0;
                    fwrite($fh, $text);
                    $text = str_replace('((ENDOFQUERY))', ';', $text);
                    fwrite($fh2, $text);
                    $text = '';
                    fclose($fh);
                    $fileNumber++;
                    $fh = fopen($file . '.' . $fileNumber, 'w');
                }
            }
        }

        if ($index > 0) {
            $text .= "((ENDOFQUERY))\n\n\n";
        }
    }

    if ($splitFiles) {
        fwrite($fh, $text);
    }
    $text = str_replace('((ENDOFQUERY))', ';', $text);
    fwrite($fh2, $text);
    $text = '';

    if ($splitFiles) {
        fclose($fh);
    }
    fclose($fh2);

    // Use set_error_handler with a custom function for better error handling (optional)
    // restore_error_handler();

    return $fileNumber + 1;
}
