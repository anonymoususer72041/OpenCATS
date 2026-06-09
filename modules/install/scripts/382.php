<?php
/*
 * CATS
 * Update 382 - convert legacy base timestamps to UTC
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
 * $Id: 382.php $
 */

function update_382_convertLegacyTimestampToUTC($value, $offsetGMT)
{
    if ($value === null || $value === '' ||
        $value === '0000-00-00' ||
        $value === '0000-00-00 00:00:00' ||
        $value === '1000-01-01 00:00:00')
    {
        return $value;
    }

    if (!is_string($value) ||
        !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/', $value))
    {
        return $value;
    }

    $utc = new DateTimeZone('UTC');
    $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, $utc);
    $errors = DateTimeImmutable::getLastErrors();
    if ($date === false ||
        ($errors !== false &&
         ($errors['warning_count'] !== 0 || $errors['error_count'] !== 0)) ||
        $date->format('Y-m-d H:i:s') !== $value)
    {
        return $value;
    }

    $offsetGMT = (int) $offsetGMT;
    if ($offsetGMT !== 0)
    {
        $modifier = ($offsetGMT > 0 ? '-' : '+') . abs($offsetGMT) . ' hours';
        $date = $date->modify($modifier);
    }

    $converted = $date->format('Y-m-d H:i:s');
    if ($converted < '1000-01-01 00:00:00' ||
        $converted > '9999-12-31 23:59:59')
    {
        return $value;
    }

    return $converted;
}

function update_382_isValidLegacyTimeZoneOffset($offset)
{
    if (is_int($offset))
    {
        $numericOffset = $offset;
    }
    else if (is_float($offset))
    {
        if (floor($offset) !== $offset)
        {
            return false;
        }

        $numericOffset = (int) $offset;
    }
    else if (is_string($offset) &&
             preg_match('/^[+-]?[0-9]+$/', $offset))
    {
        $numericOffset = (int) $offset;
    }
    else
    {
        return false;
    }

    return $numericOffset >= -12 && $numericOffset <= 14;
}

function update_382_isValidTimeZoneIdentifier($timeZone)
{
    if ($timeZone === 'UTC')
    {
        return true;
    }

    return in_array(
        $timeZone,
        DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC),
        true
    );
}

function update_382_legacyTimeZoneOffsetToIdentifier($offset)
{
    if (!update_382_isValidLegacyTimeZoneOffset($offset))
    {
        return 'UTC';
    }

    $offset = (int) $offset;
    if ($offset === 0)
    {
        return 'UTC';
    }

    $timeZone = 'Etc/GMT' . ($offset > 0 ? '-' : '+') . abs($offset);
    if (!update_382_isValidTimeZoneIdentifier($timeZone))
    {
        return 'UTC';
    }

    return $timeZone;
}

/*
 * Returns the legacy storage/base offset from the OFFSET_GMT constant.
 * This is the offset that was in effect when legacy timestamps were written.
 * Falls back to 0 (UTC) if OFFSET_GMT is missing or out of the -12..+14 range.
 */
function update_382_resolveStorageOffset()
{
    $offset = defined('OFFSET_GMT') ? OFFSET_GMT : 0;
    if (!update_382_isValidLegacyTimeZoneOffset($offset))
    {
        return 0;
    }

    return (int) $offset;
}

/*
 * Returns the legacy site/display offset from site.time_zone.
 * Falls back to $storageOffset when the column does not exist or contains an
 * invalid value.
 */
function update_382_resolveDisplayOffset($db, $storageOffset)
{
    if (update_382_tableExists($db, 'site'))
    {
        $columns = update_382_existingColumns(
            $db,
            'site',
            array('time_zone')
        );
        if (!empty($columns))
        {
            $site = $db->getAssoc(
                'SELECT `time_zone` FROM `site` ' .
                'ORDER BY `site_id` ASC LIMIT 1'
            );
            if (!empty($site) &&
                update_382_isValidLegacyTimeZoneOffset($site['time_zone']))
            {
                return (int) $site['time_zone'];
            }
        }
    }

    return $storageOffset;
}

function update_382_tableExists($db, $tableName)
{
    $rows = $db->getAllAssoc(
        'SHOW TABLES LIKE ' . $db->makeQueryString($tableName)
    );

    return !empty($rows);
}

function update_382_existingColumns($db, $tableName, $columnNames)
{
    $existing = array();

    foreach ($columnNames as $columnName)
    {
        $rows = $db->getAllAssoc(
            'SHOW COLUMNS FROM `' . $tableName . '` LIKE ' .
            $db->makeQueryString($columnName)
        );
        if (!empty($rows))
        {
            $existing[] = $columnName;
        }
    }

    return $existing;
}

function update_382_saveProgress($db, $tableName, $lastID, $completed)
{
    $db->query(
        'REPLACE INTO `migration_382_utc_timestamp` ' .
        '(`table_name`, `last_id`, `completed`) VALUES (' .
        $db->makeQueryString($tableName) . ', ' .
        $db->makeQueryInteger($lastID) . ', ' .
        ($completed ? '1' : '0') . ')'
    );
}

function update_382_complete($db)
{
    $db->query(
        "UPDATE `module_schema`
         SET `version` = 382
         WHERE `name` = 'install'"
    );

    /*
     * Progress is intentionally temporary and is removed only after success;
     * module_schema remains the long-term protection against a second run.
     */
    $db->query('DROP TABLE IF EXISTS `migration_382_utc_timestamp`');
}

function update_382($db)
{
    $migration = $db->getAssoc(
        "SELECT `version`
         FROM `module_schema`
         WHERE `name` = 'install'"
    );
    if (!empty($migration) && (int) $migration['version'] >= 382)
    {
        $db->query('DROP TABLE IF EXISTS `migration_382_utc_timestamp`');
        return;
    }

    $db->query(
        "CREATE TABLE IF NOT EXISTS `migration_382_utc_timestamp` (
            `table_name` varchar(64) NOT NULL,
            `last_id` bigint(20) NOT NULL DEFAULT 0,
            `completed` tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`table_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
    );

    $migration = $db->getAssoc(
        "SELECT `completed`
         FROM `migration_382_utc_timestamp`
         WHERE `table_name` = '__migration__'"
    );
    if (!empty($migration) && (int) $migration['completed'] === 1)
    {
        update_382_complete($db);
        return;
    }

    $tables = array(
        'activity' => array(
            'primaryKey' => 'activity_id',
            'columns' => array('date_occurred', 'date_created', 'date_modified')
        ),
        'attachment' => array(
            'primaryKey' => 'attachment_id',
            'columns' => array('date_created', 'date_modified')
        ),
        'calendar_event' => array(
            'primaryKey' => 'calendar_event_id',
            'columns' => array('date', 'date_created', 'date_modified')
        ),
        'candidate' => array(
            'primaryKey' => 'candidate_id',
            'columns' => array('date_created', 'date_modified')
        ),
        'candidate_joborder' => array(
            'primaryKey' => 'candidate_joborder_id',
            'columns' => array('date_submitted', 'date_created', 'date_modified')
        ),
        'candidate_joborder_status_history' => array(
            'primaryKey' => 'candidate_joborder_status_history_id',
            'columns' => array('date')
        ),
        'candidate_source' => array(
            'primaryKey' => 'source_id',
            'columns' => array('date_created')
        ),
        'career_portal_questionnaire_history' => array(
            'primaryKey' => 'career_portal_questionnaire_history_id',
            'columns' => array('date')
        ),
        'company' => array(
            'primaryKey' => 'company_id',
            'columns' => array('date_created', 'date_modified')
        ),
        'company_department' => array(
            'primaryKey' => 'company_department_id',
            'columns' => array('date_created')
        ),
        'contact' => array(
            'primaryKey' => 'contact_id',
            'columns' => array('date_created', 'date_modified')
        ),
        'email_history' => array(
            'primaryKey' => 'email_history_id',
            'columns' => array('date')
        ),
        'extra_field_settings' => array(
            'primaryKey' => 'extra_field_settings_id',
            'columns' => array('date_created')
        ),
        'feedback' => array(
            'primaryKey' => 'feedback_id',
            'columns' => array('date_created')
        ),
        'history' => array(
            'primaryKey' => 'history_id',
            'columns' => array('set_date')
        ),
        'http_log' => array(
            'primaryKey' => 'log_id',
            'columns' => array('date')
        ),
        'import' => array(
            'primaryKey' => 'import_id',
            'columns' => array('date_created')
        ),
        'joborder' => array(
            'primaryKey' => 'joborder_id',
            'columns' => array('date_created', 'date_modified')
        ),
        'mru' => array(
            'primaryKey' => 'mru_id',
            'columns' => array('date_created')
        ),
        'queue' => array(
            'primaryKey' => 'queue_id',
            'columns' => array('date_created', 'date_timeout', 'date_completed')
        ),
        'saved_list' => array(
            'primaryKey' => 'saved_list_id',
            'columns' => array('date_created', 'date_modified')
        ),
        'saved_list_entry' => array(
            'primaryKey' => 'saved_list_entry_id',
            'columns' => array('date_created')
        ),
        'saved_search' => array(
            'primaryKey' => 'search_id',
            'columns' => array('date_created')
        ),
        'site' => array(
            'primaryKey' => 'site_id',
            'columns' => array('date_created')
        ),
        'user_login' => array(
            'primaryKey' => 'user_login_id',
            'columns' => array('date', 'date_refreshed')
        )
    );

    $batchSize = 250;

    /*
     * Storage offset: the base offset in effect when legacy data was written,
     * sourced from OFFSET_GMT.  This is what we subtract to reach UTC.
     *
     * Display offset: the site's configured display timezone (site.time_zone).
     * Falls back to the storage offset when site.time_zone is missing or invalid.
     *
     * Historical DST changes cannot be reconstructed from a single numeric offset.
     */
    $storageOffset = update_382_resolveStorageOffset();
    $displayOffset = update_382_resolveDisplayOffset($db, $storageOffset);
    $timeZoneIANA  = update_382_legacyTimeZoneOffsetToIdentifier($displayOffset);

    /*
     * Update site timezone settings.
     * Preserve site.time_zone when it already holds a valid value; only
     * normalize it (to the storage offset) when the stored value was invalid.
     * Always write time_zone_iana, derived from the preserved display offset.
     */
    if (update_382_tableExists($db, 'site'))
    {
        $ianaColumns = update_382_existingColumns(
            $db,
            'site',
            array('time_zone_iana')
        );
        if (!empty($ianaColumns))
        {
            $tzColumns = update_382_existingColumns(
                $db,
                'site',
                array('time_zone')
            );
            $siteTimeZoneValid = false;
            if (!empty($tzColumns))
            {
                $siteRow = $db->getAssoc(
                    'SELECT `time_zone` FROM `site` ' .
                    'ORDER BY `site_id` ASC LIMIT 1'
                );
                $siteTimeZoneValid = !empty($siteRow) &&
                    update_382_isValidLegacyTimeZoneOffset($siteRow['time_zone']);
            }

            if ($siteTimeZoneValid)
            {
                $db->query(
                    'UPDATE `site` SET `time_zone_iana` = ' .
                    $db->makeQueryString($timeZoneIANA)
                );
            }
            else
            {
                $db->query(
                    'UPDATE `site` SET `time_zone` = ' .
                    $db->makeQueryInteger($storageOffset) .
                    ', `time_zone_iana` = ' .
                    $db->makeQueryString($timeZoneIANA)
                );
            }
        }
    }

    foreach ($tables as $tableName => $tableData)
    {
        if (!update_382_tableExists($db, $tableName))
        {
            continue;
        }

        $primaryKeyRows = $db->getAllAssoc(
            'SHOW COLUMNS FROM `' . $tableName . '` LIKE ' .
            $db->makeQueryString($tableData['primaryKey'])
        );
        if (empty($primaryKeyRows))
        {
            continue;
        }

        $columns = update_382_existingColumns(
            $db,
            $tableName,
            $tableData['columns']
        );
        if (empty($columns))
        {
            continue;
        }

        $progress = $db->getAssoc(
            'SELECT `last_id`, `completed` ' .
            'FROM `migration_382_utc_timestamp` ' .
            'WHERE `table_name` = ' . $db->makeQueryString($tableName)
        );
        if (!empty($progress) && (int) $progress['completed'] === 1)
        {
            continue;
        }

        $lastID = empty($progress) ? 0 : (int) $progress['last_id'];
        $selectColumns = array('`' . $tableData['primaryKey'] . '`');
        foreach ($columns as $columnName)
        {
            $selectColumns[] = '`' . $columnName . '`';
        }

        while (true)
        {
            $rows = $db->getAllAssoc(
                'SELECT ' . implode(', ', $selectColumns) .
                ' FROM `' . $tableName . '`' .
                ' WHERE `' . $tableData['primaryKey'] . '` > ' .
                $db->makeQueryInteger($lastID) .
                ' ORDER BY `' . $tableData['primaryKey'] . '` ASC' .
                ' LIMIT ' . (int) $batchSize
            );

            if (empty($rows))
            {
                update_382_saveProgress($db, $tableName, $lastID, true);
                break;
            }

            $db->query('START TRANSACTION');

            foreach ($rows as $row)
            {
                $rowID = (int) $row[$tableData['primaryKey']];
                $updates = array();

                foreach ($columns as $columnName)
                {
                    $converted = update_382_convertLegacyTimestampToUTC(
                        $row[$columnName],
                        $storageOffset
                    );
                    if ($converted !== $row[$columnName])
                    {
                        $updates[] = '`' . $columnName . '` = ' .
                            $db->makeQueryString($converted);
                    }
                }

                if (!empty($updates))
                {
                    $db->query(
                        'UPDATE `' . $tableName . '` SET ' .
                        implode(', ', $updates) .
                        ' WHERE `' . $tableData['primaryKey'] . '` = ' .
                        $db->makeQueryInteger($rowID)
                    );
                }

                $lastID = $rowID;
            }

            update_382_saveProgress($db, $tableName, $lastID, false);
            $db->query('COMMIT');
        }
    }

    update_382_saveProgress($db, '__migration__', 0, true);
    update_382_complete($db);
}

?>
