<?php

include_once(LEGACY_ROOT . '/lib/DateUtility.php');

function update_381($db)
{
    $defaultTimeZone = 'UTC';
    if (defined('APPLICATION_TIME_ZONE'))
    {
        $configuredTimeZone = DateUtility::getApplicationTimeZone(
            APPLICATION_TIME_ZONE
        )->getName();
        if ($configuredTimeZone === APPLICATION_TIME_ZONE)
        {
            $defaultTimeZone = $configuredTimeZone;
        }
    }

    if (!columnExists_381($db, 'site', 'application_time_zone'))
    {
        $db->query(
            "ALTER TABLE `site`
             ADD COLUMN `application_time_zone` varchar(64)
             NOT NULL DEFAULT 'UTC'
             AFTER `time_zone`"
        );
        $db->query(
            "UPDATE `site`
             SET `application_time_zone` = " .
                $db->makeQueryString($defaultTimeZone)
        );
    }

    $dateTimeColumns = array(
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
            /* date is the scheduled event instant. */
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
            /* date is the instant when the pipeline status changed. */
            'columns' => array('date')
        ),
        'candidate_source' => array(
            'primaryKey' => 'source_id',
            'columns' => array('date_created')
        ),
        'career_portal_questionnaire_history' => array(
            'primaryKey' => 'career_portal_questionnaire_history_id',
            /* date is the instant when the questionnaire was submitted. */
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
            /* date is the instant when the message was recorded as sent. */
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
            /* set_date is the audit entry creation instant. */
            'columns' => array('set_date')
        ),
        'http_log' => array(
            'primaryKey' => 'log_id',
            /* date is the request log instant. */
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
            /* Queue dates are task lifecycle instants, including deadlines. */
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
            /* date and date_refreshed are login session instants. */
            'columns' => array('date', 'date_refreshed')
        )
    );

    /* Numeric offsets are ambiguous and cannot preserve historical daylight
     * saving behavior. Existing sites therefore use UTC unless an explicit
     * APPLICATION_TIME_ZONE IANA identifier was configured before upgrade.
     */
    $siteTimeZones = array();
    $sites = $db->getAllAssoc(
        "SELECT site_id, application_time_zone
         FROM site"
    );
    foreach ($sites as $site)
    {
        $siteTimeZones[(int) $site['site_id']] =
            DateUtility::getApplicationTimeZone(
                $site['application_time_zone']
            );
    }

    foreach ($dateTimeColumns as $table => $definition)
    {
        foreach ($definition['columns'] as $column)
        {
            convertDateTimeColumnToUtc_381(
                $db,
                $table,
                $definition['primaryKey'],
                $column,
                $siteTimeZones
            );
        }
    }
}

function convertDateTimeColumnToUtc_381(
    $db,
    $table,
    $primaryKey,
    $column,
    $siteTimeZones
)
{
    if (!columnExists_381($db, $table, $primaryKey) ||
        !dateTimeColumnExists_381($db, $table, $column))
    {
        return;
    }

    $hasSiteID = columnExists_381($db, $table, 'site_id');
    $siteSelect = $hasSiteID ? ', `site_id` AS site_id' : '';
    $additionalCriteria = $table === 'calendar_event' && $column === 'date'
        ? ' AND `all_day` = 0'
        : '';
    $lastPrimaryKey = -1;
    $batchSize = 200;

    while (true)
    {
        $rows = $db->getAllAssoc(
            "SELECT `" . $primaryKey . "` AS primary_key,
                    `" . $column . "` AS date_value" . $siteSelect . "
             FROM `" . $table . "`
             WHERE `" . $primaryKey . "` > " . (int) $lastPrimaryKey . "
               AND `" . $column . "` IS NOT NULL
               " . $additionalCriteria . "
             ORDER BY `" . $primaryKey . "` ASC
             LIMIT " . (int) $batchSize
        );

        if (empty($rows))
        {
            break;
        }

        foreach ($rows as $row)
        {
            $lastPrimaryKey = (int) $row['primary_key'];
            $siteID = $table === 'site'
                ? $lastPrimaryKey
                : ($hasSiteID ? (int) $row['site_id'] : 0);
            $applicationTimeZone = isset($siteTimeZones[$siteID])
                ? $siteTimeZones[$siteID]
                : new DateTimeZone('UTC');
            $utcValue = DateUtility::convertLocalDateTimeToUtc(
                $row['date_value'],
                $applicationTimeZone
            );

            if ($utcValue === $row['date_value'])
            {
                continue;
            }

            $db->query(
                "UPDATE `" . $table . "`
                 SET `" . $column . "` = " . $db->makeQueryString($utcValue) . "
                 WHERE `" . $primaryKey . "` = " . $lastPrimaryKey
            );
        }
    }
}

function columnExists_381($db, $table, $column)
{
    $columnData = $db->getAssoc(
        "SELECT COLUMN_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = " . $db->makeQueryString($table) . "
           AND COLUMN_NAME = " . $db->makeQueryString($column)
    );

    return !empty($columnData);
}

function dateTimeColumnExists_381($db, $table, $column)
{
    $columnData = $db->getAssoc(
        "SELECT COLUMN_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = " . $db->makeQueryString($table) . "
           AND COLUMN_NAME = " . $db->makeQueryString($column) . "
           AND DATA_TYPE = 'datetime'"
    );

    return !empty($columnData);
}

?>
