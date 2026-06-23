<?php
/*
 * OpenCATS
 * Migration 382 - Convert all DATETIME columns from local time to UTC
 *
 * Reads the IANA timezone from the installer session or from
 * site.time_zone_iana and converts every DATETIME value in the database
 * from that timezone to UTC.
 *
 * Date-only fields (candidate.date_available, joborder.start_date) are
 * intentionally excluded because they represent calendar dates, not
 * precise instants.
 *
 * Two paths:
 *   1. MySQL CONVERT_TZ with IANA identifier (fast, requires timezone tables)
 *   2. PHP DateTimeZone fallback (row-by-row, works everywhere)
 *
 * Sentinel values ('1000-01-01 00:00:00') and NULLs are left untouched.
 */

function update_382($db)
{
    $tables = array(
        'activity' => array(
            'pk' => 'activity_id',
            'columns' => array('date_occurred', 'date_created', 'date_modified'),
        ),
        'attachment' => array(
            'pk' => 'attachment_id',
            'columns' => array('date_created', 'date_modified'),
        ),
        'calendar_event' => array(
            'pk' => 'calendar_event_id',
            'columns' => array('date', 'date_created', 'date_modified'),
        ),
        'candidate' => array(
            'pk' => 'candidate_id',
            'columns' => array('date_created', 'date_modified'),
        ),
        'candidate_joborder' => array(
            'pk' => 'candidate_joborder_id',
            'columns' => array('date_submitted', 'date_created', 'date_modified'),
        ),
        'candidate_joborder_status_history' => array(
            'pk' => 'candidate_joborder_status_history_id',
            'columns' => array('date'),
        ),
        'candidate_source' => array(
            'pk' => 'source_id',
            'columns' => array('date_created'),
        ),
        'career_portal_questionnaire_history' => array(
            'pk' => 'career_portal_questionnaire_history_id',
            'columns' => array('date'),
        ),
        'company' => array(
            'pk' => 'company_id',
            'columns' => array('date_created', 'date_modified'),
        ),
        'company_department' => array(
            'pk' => 'company_department_id',
            'columns' => array('date_created'),
        ),
        'contact' => array(
            'pk' => 'contact_id',
            'columns' => array('date_created', 'date_modified'),
        ),
        'email_history' => array(
            'pk' => 'email_history_id',
            'columns' => array('date'),
        ),
        'extra_field_settings' => array(
            'pk' => 'extra_field_settings_id',
            'columns' => array('date_created'),
        ),
        'feedback' => array(
            'pk' => 'feedback_id',
            'columns' => array('date_created'),
        ),
        'history' => array(
            'pk' => 'history_id',
            'columns' => array('set_date'),
        ),
        'http_log' => array(
            'pk' => 'http_log_id',
            'columns' => array('date'),
        ),
        'import' => array(
            'pk' => 'import_id',
            'columns' => array('date_created'),
        ),
        'joborder' => array(
            'pk' => 'joborder_id',
            'columns' => array('date_created', 'date_modified'),
        ),
        'mru' => array(
            'pk' => 'mru_id',
            'columns' => array('date_created'),
        ),
        'queue' => array(
            'pk' => 'queue_id',
            'columns' => array('date_created', 'date_timeout', 'date_completed'),
        ),
        'saved_list' => array(
            'pk' => 'saved_list_id',
            'columns' => array('date_created', 'date_modified'),
        ),
        'saved_list_entry' => array(
            'pk' => 'saved_list_entry_id',
            'columns' => array('date_created'),
        ),
        'saved_search' => array(
            'pk' => 'search_id',
            'columns' => array('date_created'),
        ),
        'site' => array(
            'pk' => 'site_id',
            'columns' => array('date_created'),
        ),
        'system' => array(
            'pk' => 'system_id',
            'columns' => array('date_version_checked'),
        ),
        'user_login' => array(
            'pk' => 'user_login_id',
            'columns' => array('date', 'date_refreshed'),
        ),
    );

    $sentinel = '1000-01-01 00:00:00';

    $ianaTimeZone = _migration382_getIanaTimeZone($db);

    if ($ianaTimeZone === 'UTC')
    {
        return;
    }

    $mysqlHasIana = _migration382_mysqlSupportsIana($db, $ianaTimeZone);

    foreach ($tables as $tableName => $tableData)
    {
        $tableExists = $db->getAllAssoc(
            'SHOW TABLES LIKE ' . $db->makeQueryString($tableName)
        );
        if (empty($tableExists))
        {
            continue;
        }

        foreach ($tableData['columns'] as $column)
        {
            $columnExists = $db->getAllAssoc(
                'SHOW COLUMNS FROM `' . $tableName . '` LIKE '
                . $db->makeQueryString($column)
            );
            if (empty($columnExists))
            {
                continue;
            }

            if ($mysqlHasIana)
            {
                _migration382_convertColumnMySQL(
                    $db, $tableName, $column, $ianaTimeZone, $sentinel
                );
            }
            else
            {
                _migration382_convertColumnPHP(
                    $db, $tableName, $tableData['pk'], $column,
                    $ianaTimeZone, $sentinel
                );
            }
        }
    }
}


function _migration382_getIanaTimeZone($db)
{
    $ianaTimeZone = '';

    if (defined('CATS_SESSION_NAME'))
    {
        @session_name(CATS_SESSION_NAME);
    }

    if (session_id() === '')
    {
        @session_start();
    }

    if (isset($_SESSION['ianaTimeZoneInstaller']))
    {
        $ianaTimeZone = trim($_SESSION['ianaTimeZoneInstaller']);
    }

    if ($ianaTimeZone === '')
    {
        $rs = $db->getAllAssoc(
            'SELECT time_zone_iana FROM site LIMIT 1'
        );

        if (!empty($rs))
        {
            $ianaTimeZone = trim($rs[0]['time_zone_iana']);
        }
    }

    if ($ianaTimeZone === '')
    {
        throw new Exception(
            'Cannot migrate timestamps to UTC without a configured IANA timezone.'
        );
    }

    try {
        new DateTimeZone($ianaTimeZone);
    } catch (Exception $e) {
        throw new Exception(
            'Cannot migrate timestamps to UTC because the configured IANA '
            . 'timezone is invalid: ' . $ianaTimeZone
        );
    }

    return $ianaTimeZone;
}


function _migration382_mysqlSupportsIana($db, $ianaTimeZone)
{
    $rs = $db->getAllAssoc(sprintf(
        "SELECT CONVERT_TZ('2024-06-15 12:00:00', %s, 'UTC') AS result",
        $db->makeQueryString($ianaTimeZone)
    ));

    return !empty($rs) && $rs[0]['result'] !== null;
}


function _migration382_convertColumnMySQL($db, $table, $column, $ianaTimeZone, $sentinel)
{
    $db->query(sprintf(
        "UPDATE `%s` SET `%s` = CONVERT_TZ(`%s`, %s, 'UTC') "
        . "WHERE `%s` IS NOT NULL AND `%s` > '%s'",
        $table,
        $column,
        $column,
        $db->makeQueryString($ianaTimeZone),
        $column,
        $column,
        $sentinel
    ));
}


function _migration382_convertColumnPHP($db, $table, $pk, $column, $ianaTimeZone, $sentinel)
{
    $srcTz = new DateTimeZone($ianaTimeZone);
    $utcTz = new DateTimeZone('UTC');

    $rows = $db->getAllAssoc(sprintf(
        "SELECT `%s`, `%s` FROM `%s` WHERE `%s` IS NOT NULL AND `%s` > '%s'",
        $pk, $column, $table, $column, $column, $sentinel
    ));

    foreach ($rows as $row)
    {
        $localValue = $row[$column];
        if (empty($localValue))
        {
            continue;
        }

        $dt = new DateTime($localValue, $srcTz);
        $dt->setTimezone($utcTz);
        $utcValue = $dt->format('Y-m-d H:i:s');

        if ($utcValue === $localValue)
        {
            continue;
        }

        $db->query(sprintf(
            "UPDATE `%s` SET `%s` = %s WHERE `%s` = %s",
            $table,
            $column,
            $db->makeQueryString($utcValue),
            $pk,
            $db->makeQueryInteger($row[$pk])
        ));
    }
}
