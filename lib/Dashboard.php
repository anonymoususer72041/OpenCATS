<?php
/**
 * CATS
 * Dashboard Library
 *
 * Copyright (C) 2005 - 2007 Cognizo Technologies, Inc.
 *
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
 *
 * @package    CATS
 * @subpackage Library
 * @copyright Copyright (C) 2005 - 2007 Cognizo Technologies, Inc.
 * @version    $Id: Dashboard.php 3784 2007-12-03 21:57:10Z brian $
 */

include_once(LEGACY_ROOT . '/lib/Calendar.php');

/**
 *	Dashboard Library
 *	@package    CATS
 *	@subpackage Library
 */
class Dashboard 
{

    private $_db;
    private $_siteID;
    
    public function __construct($siteID)
    {
        $this->_siteID = $siteID;
        $this->_db = DatabaseConnection::getInstance();
    }

    /**
     * Returns an array of recent placements to display on the dashboard.
     *
     * @return array recent placements
     */
    public function getPlacements()
    {
        $sql = sprintf(
            "SELECT
                candidate.first_name as firstName,
                candidate.last_name as lastName,
                candidate.candidate_id as candidateID,
                company.name as companyName,
                company.company_id as companyID,
                user.first_name as userFirstName,
                user.last_name as userLastName,
                IF (company.is_hot = 1, 'jobLinkHot', 'jobLinkCold') as companyClassName,
                IF (candidate.is_hot = 1, 'jobLinkHot', 'jobLinkCold') as candidateClassName,
                DATE_FORMAT(
                    candidate_joborder_status_history.date, '%%m-%%d-%%y'
                ) AS date,
                candidate_joborder_status_history.date AS datesort
            FROM
                candidate_joborder_status_history
            LEFT JOIN candidate ON
                candidate.candidate_id = candidate_joborder_status_history.candidate_id
            LEFT JOIN joborder ON
                joborder.joborder_id = candidate_joborder_status_history.joborder_id
            LEFT JOIN company ON
                joborder.company_id = company.company_id
            LEFT JOIN user ON
                joborder.recruiter = user.user_id
            WHERE
                status_to = 800
            AND
                candidate_joborder_status_history.site_id = %s
            ORDER BY 
                datesort DESC
            LIMIT
                10",
            $this->_siteID
        );

        $rs = $this->_db->getAllAssoc($sql);

        return $rs;
    }

    /**
     * Returns an associative array with 4 rows of either the last 4 weeks or 4 months 
     * statistics on submitted, interviewing, and placed candidates.
     *
     * @param integer pipeline view indentifier
     * @return array pipeline graph data
     */
    public function getPipelineData($view)
    {
        $calendarSettings = new CalendarSettings($this->_siteID);
        $calendarSettingsRS = $calendarSettings->getAll();
        $timeZone = DateUtility::getApplicationTimeZone();
        $periodStart = new DateTime('now', $timeZone);
        $periodStart->setTime(0, 0, 0);
        $periodUnit = 'week';

        if ($view == DASHBOARD_GRAPH_YEARLY)
        {
            $periodStart->setDate((int) $periodStart->format('Y'), 1, 1);
            $periodUnit = 'year';
        }
        else if ($view == DASHBOARD_GRAPH_MONTHLY)
        {
            $periodStart->modify('first day of this month');
            $periodUnit = 'month';
        }
        else
        {
            $firstDay = $calendarSettingsRS['firstDayMonday'] == 1 ? 1 : 0;
            $daysSinceStart = ((int) $periodStart->format('w') - $firstDay + 7) % 7;
            $periodStart->modify('-' . $daysSinceStart . ' days');
        }

        $data = array();
        $periodStarts = array();
        for ($index = 0; $index < 4; ++$index)
        {
            $start = clone $periodStart;
            if ($index > 0)
            {
                $start->modify('-' . $index . ' ' . $periodUnit);
            }
            $key = (int) $start->format('U');
            $periodStarts[$key] = $start;

            if ($view == DASHBOARD_GRAPH_YEARLY)
            {
                $label = $start->format('Y');
            }
            else if ($view == DASHBOARD_GRAPH_MONTHLY)
            {
                $label = $start->format('F');
            }
            else
            {
                if ($_SESSION['CATS']->isDateDMY())
                {
                    $pattern = 'd/m';
                }
                else
                {
                    $pattern = 'm/d';
                }
                $end = clone $start;
                $end->modify('+6 days');
                $label = $start->format($pattern) . ' - ' . $end->format($pattern);
            }

            $data[$key] = array(
                'label' => $label,
                'submitted' => 0,
                'interviewing' => 0,
                'placed' => 0
            );
        }

        $earliest = min(array_keys($periodStarts));
        $earliestUtc = clone $periodStarts[$earliest];
        $earliestUtc->setTimezone(new DateTimeZone('UTC'));
        $rows = $this->_db->getAllAssoc(sprintf(
            "SELECT date, status_to AS statusTo
             FROM candidate_joborder_status_history
             WHERE site_id = %s
               AND date >= %s",
            $this->_siteID,
            $this->_db->makeQueryString(
                $earliestUtc->format(DateUtility::DATABASE_DATETIME_FORMAT)
            )
        ));

        foreach ($rows as $row)
        {
            $localDate = new DateTime($row['date'], new DateTimeZone('UTC'));
            $localDate->setTimezone($timeZone);
            $localDate->setTime(0, 0, 0);
            if ($view == DASHBOARD_GRAPH_YEARLY)
            {
                $localDate->setDate((int) $localDate->format('Y'), 1, 1);
            }
            else if ($view == DASHBOARD_GRAPH_MONTHLY)
            {
                $localDate->modify('first day of this month');
            }
            else
            {
                $firstDay = $calendarSettingsRS['firstDayMonday'] == 1 ? 1 : 0;
                $daysSinceStart = ((int) $localDate->format('w') - $firstDay + 7) % 7;
                $localDate->modify('-' . $daysSinceStart . ' days');
            }

            $key = (int) $localDate->format('U');
            if (!isset($data[$key]))
            {
                continue;
            }

            if ($row['statusTo'] == PIPELINE_STATUS_SUBMITTED)
            {
                ++$data[$key]['submitted'];
            }
            else if ($row['statusTo'] == PIPELINE_STATUS_INTERVIEWING)
            {
                ++$data[$key]['interviewing'];
            }
            else if ($row['statusTo'] == PIPELINE_STATUS_PLACED)
            {
                ++$data[$key]['placed'];
            }
        }

        ksort($data, SORT_NUMERIC);
        return $data;
    }
}
    
?>
