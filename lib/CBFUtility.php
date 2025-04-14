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

/**
 * BackupDBErrorHandler
 *
 * A custom error handler to log errors during the backup process.
 */
function BackupDBErrorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
    // Get extra context if available
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
    // Log the error to a dedicated log file
    $logFile = "backup_errors.log";
    error_log($errorMessage . "\n", 3, $logFile);
    // Inform the user and exit
    echo "An error occurred during the backup process. Please check the logs for details.";
    die();
}

/**
 * dumpDB
 *
 * Dumps the entire database schema for the current site into $file and splits
 * the output into ~1MB chunks using a custom delimiter.
 *
 * @param object $db         Database connection object.
 * @param string $file       Base filename for the dump.
 * @param bool   $useStatus  If true, status messages are displayed.
 * @param bool   $splitFiles If true, splits the dump into multiple files.
 * @param int    $siteID     Site ID (defaults to the current session's site).
 * @return int               The number of file chunks created.
 */
function dumpDB($db, $file, $useStatus = false, $splitFiles = true, $siteID = -1)
{
    // (Optional: set_error_handler('BackupDBErrorHandler'); )

    if ($siteID === -1) {
        $siteID = $_SESSION['CATS']->getSiteID();
    }

    $len = 0;
    $fileNumber = 0;
    $connection = $db->getConnection();
    $text = '';

    // Retrieve list of tables from the database
    $result = mysqli_query($connection, sprintf("SHOW TABLES FROM `%s`", DATABASE_NAME));
    if (!$result) {
        error_log("dumpDB: Failed to retrieve tables: " . mysqli_error($connection));
        return 0;
    }
    $tables = array_map(fn($row) => $row[0], mysqli_fetch_all($result, MYSQLI_NUM));

    // Open file handles for the split and complete dump files
    if ($splitFiles) {
        $fh = fopen($file . '.' . $fileNumber, 'w');
        if (!$fh) {
            error_log("dumpDB: Unable to open split file for writing: " . $file . '.' . $fileNumber);
        }
    }
    $fh2 = fopen($file, 'w');
    if (!$fh2) {
        error_log("dumpDB: Unable to open complete dump file for writing: " . $file);
    }

    $tableCounter = 0;
    $totalTables = count($tables);
    foreach ($tables as $table) {
        ++$tableCounter;

        // Skip tables that are not to be backed up
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

        $text .= 'DROP TABLE IF EXISTS `' . $table . '`((END_OF_QUERY))' . "\n";
        $sql = 'SHOW CREATE TABLE ' . $table;
        $rs = mysqli_query($connection, $sql);
        if ($rs) {
            if ($row = mysqli_fetch_assoc($rs)) {
                $text .= $row['Create Table'] . "((END_OF_QUERY))\n\n";
            }
        } else {
            error_log("dumpDB: Failed to get create table for $table: " . mysqli_error($connection));
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
        $sql = $isSiteIdColumn ? 'SELECT * FROM ' . $table . ' WHERE site_id = ' . $siteID : 'SELECT * FROM ' . $table;
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
                    // Remove fields we don't need
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
                    // Process each field: convert to string, strip HTML tags,
                    // decode entities, collapse whitespace, trim and escape.
                    $value = (string)$field;
                    $value = strip_tags($value);
                    $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
                    $value = preg_replace('/\s+/', ' ', $value);
                    $value = trim($value);
                    $text .= "'" . mysqli_real_escape_string($connection, $value) . "'";
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
                // For the complete dump file, replace our custom delimiter with a semicolon
                $cleanText = str_replace('((END_OF_QUERY))', ';', $text);
                fwrite($fh2, $cleanText);
                $text = '';

                // (Optional) Reset length counter if file size becomes large
                if ($len > 1000000 && $splitFiles) {
                    $text .= "((END_OF_QUERY))\n\n\n";
                    $index = 0;
                    $len = 0;
                    fwrite($fh, $text);
                    $cleanText = str_replace('((END_OF_QUERY))', ';', $text);
                    fwrite($fh2, $cleanText);
                    $text = '';
                    fclose($fh);
                    $fileNumber++;
                    $fh = fopen($file . '.' . $fileNumber, 'w');
                }
            }
        }

        if ($index > 0) {
            $text .= "((END_OF_QUERY))\n\n\n";
        }
    }

    if ($splitFiles) {
        fwrite($fh, $text);
    }
    $cleanText = str_replace('((END_OF_QUERY))', ';', $text);
    fwrite($fh2, $cleanText);
    $text = '';

    if ($splitFiles) {
        fclose($fh);
    }
    fclose($fh2);

    // Optionally restore the previous error handler:
    // restore_error_handler();

    return $fileNumber + 1;
}
?>
