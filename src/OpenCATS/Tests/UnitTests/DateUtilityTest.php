<?php
/**
 * CATS
* Date Utility Library
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
* @version    $Id: DateUtility.php 3592 2007-11-13 17:30:46Z brian $
*/

/**
 *	Date Utility Library
*	@package    CATS
*	@subpackage Library
*/

use PHPUnit\Framework\TestCase;

include_once(LEGACY_ROOT . '/constants.php');
include_once(LEGACY_ROOT . '/lib/StringUtility.php');
include_once(LEGACY_ROOT . '/lib/DateUtility.php');   /* Depends on StringUtility. */

class DateUtilityTest extends TestCase
{
    function testConvertLocalDateTimeToUtcWithIanaTimeZone()
    {
        $this->assertSame(
            '2024-01-15 09:00:00',
            DateUtility::convertLocalDateTimeToUtc(
                '2024-01-15 10:00:00',
                'Europe/Berlin'
            )
        );
        $this->assertSame(
            '2024-07-15 08:00:00',
            DateUtility::convertLocalDateTimeToUtc(
                '2024-07-15 10:00:00',
                'Europe/Berlin'
            )
        );
    }

    function testConvertUtcDateTimeToLocalWithIanaTimeZone()
    {
        $this->assertSame(
            '2024-01-15 10:00:00',
            DateUtility::convertUtcDateTimeToLocal(
                '2024-01-15 09:00:00',
                'Europe/Berlin'
            )
        );
        $this->assertSame(
            '2024-07-15 10:00:00',
            DateUtility::convertUtcDateTimeToLocal(
                '2024-07-15 08:00:00',
                'Europe/Berlin'
            )
        );
    }

    function testUtcDateTimeConversionsPreserveUnsupportedValues()
    {
        $values = array(
            null,
            '',
            0,
            '0',
            'invalid',
            '0000-00-00 00:00:00',
            '1000-01-01 00:00:00',
            '2024-01-15'
        );

        foreach ($values as $value)
        {
            $this->assertSame(
                $value,
                DateUtility::convertLocalDateTimeToUtc(
                    $value,
                    'Europe/Berlin'
                )
            );
            $this->assertSame(
                $value,
                DateUtility::convertUtcDateTimeToLocal(
                    $value,
                    'Europe/Berlin'
                )
            );
        }
    }

    function testUtcDayBoundaryIncludesTheSelectedEndDay()
    {
        $this->assertSame(
            '2024-07-14 22:00:00',
            DateUtility::getUtcDayBoundary(
                '2024-07-15',
                false,
                'Europe/Berlin'
            )
        );
        $this->assertSame(
            '2024-07-15 22:00:00',
            DateUtility::getUtcDayBoundary(
                '2024-07-15',
                true,
                'Europe/Berlin'
            )
        );
    }

    function testUtcRelativeDayBoundaryUsesLocalStartOfDay()
    {
        $this->assertSame(
            '2024-06-14 22:00:00',
            DateUtility::getUtcRelativeDayBoundary(
                '-1 month',
                'Europe/Berlin',
                '2024-07-15'
            )
        );
    }

    function testUtcRelativeMonthBoundaryClampsToLastValidDay()
    {
        $this->assertSame(
            '2024-02-28 23:00:00',
            DateUtility::getUtcRelativeDayBoundary(
                '-1 month',
                'Europe/Berlin',
                '2024-03-31'
            )
        );
    }

    function testInvalidApplicationTimeZoneFallsBackToUtc()
    {
        $this->assertSame(
            'UTC',
            DateUtility::getApplicationTimeZone('Invalid/TimeZone')->getName()
        );
        $this->assertSame(
            'UTC',
            DateUtility::getApplicationTimeZone(2)->getName()
        );
        $this->assertSame(
            '2024-07-15 10:00:00',
            DateUtility::convertLocalDateTimeToUtc(
                '2024-07-15 10:00:00',
                'Invalid/TimeZone'
            )
        );
    }

    function testUtcPeriodBoundariesApplyDaylightSavingRules()
    {
        $boundaries = DateUtility::getUtcPeriodBoundaries(
            TIME_PERIOD_TODAY,
            'Europe/Berlin',
            '2024-03-31 12:00:00'
        );

        $this->assertSame('2024-03-30 23:00:00', $boundaries['start']);
        $this->assertSame('2024-03-31 22:00:00', $boundaries['end']);
    }

    /* Tests for getStartingWeekday(). */
    function testGetStartingWeekday()
    {
        $this->assertSame(
            DateUtility::getStartingWeekday(CALENDAR_MONTH_MARCH, 2006),
            CALENDAR_DAY_WEDNSDAY
            );

        $this->assertSame(
            DateUtility::getStartingWeekday(CALENDAR_MONTH_MARCH, 1987),
            CALENDAR_DAY_SUNDAY
            );
        $this->assertSame(
            DateUtility::getStartingWeekday(CALENDAR_MONTH_APRIL, 1987),
            CALENDAR_DAY_WEDNSDAY
            );
    }

    /* Tests for getStartingWeekday(). */
    function testGetDaysInMonth()
    {
        $this->assertSame(
            DateUtility::getDaysInMonth(CALENDAR_MONTH_MARCH, 2006),
            31
            );

        $this->assertSame(
            DateUtility::getDaysInMonth(CALENDAR_MONTH_MARCH, 1987),
            DateUtility::getDaysInMonth(CALENDAR_MONTH_MARCH, 2006)
            );

        $this->assertSame(
            DateUtility::getDaysInMonth(CALENDAR_MONTH_APRIL, 1987),
            30
            );

        /* Leap years... */
        $this->assertSame(
            DateUtility::getDaysInMonth(CALENDAR_MONTH_FEBRUARY, 2008),
            29
            );
        $this->assertSame(
            DateUtility::getDaysInMonth(CALENDAR_MONTH_FEBRUARY, 2006),
            28
            );
    }

    /* Tests for getMonthName(). */
    function testGetMonthName()
    {
        $this->assertSame(
            DateUtility::getMonthName(CALENDAR_MONTH_JANUARY),
            'January'
            );
        $this->assertSame(
            DateUtility::getMonthName(CALENDAR_MONTH_FEBRUARY),
            'February'
            );
        $this->assertSame(
            DateUtility::getMonthName(CALENDAR_MONTH_MARCH),
            'March'
            );
        $this->assertSame(
            DateUtility::getMonthName(CALENDAR_MONTH_APRIL),
            'April'
            );
        $this->assertSame(
            DateUtility::getMonthName(CALENDAR_MONTH_MAY),
            'May'
            );
        $this->assertSame(
            DateUtility::getMonthName(CALENDAR_MONTH_JUNE),
            'June'
            );
        $this->assertSame(
            DateUtility::getMonthName(CALENDAR_MONTH_JULY),
            'July'
            );
        $this->assertSame(
            DateUtility::getMonthName(CALENDAR_MONTH_AUGUST),
            'August'
            );
        $this->assertSame(
            DateUtility::getMonthName(CALENDAR_MONTH_SEPTEMBER),
            'September'
            );
        $this->assertSame(
            DateUtility::getMonthName(CALENDAR_MONTH_OCTOBER),
            'October'
            );
        $this->assertSame(
            DateUtility::getMonthName(CALENDAR_MONTH_NOVEMBER),
            'November'
            );
        $this->assertSame(
            DateUtility::getMonthName(CALENDAR_MONTH_DECEMBER),
            'December'
            );
    }

    /* Tests for validate(). */
    function testValidate()
    {
        $validDates = array(
            array('/', '01/01/01', DATE_FORMAT_MMDDYY),
            array('/', '02/27/05', DATE_FORMAT_MMDDYY),
            array('/', '02/28/05', DATE_FORMAT_MMDDYY),
            array('/', '02/29/04', DATE_FORMAT_MMDDYY),
            array('/', '02/29/00', DATE_FORMAT_MMDDYY),
            array('/', '12/31/05', DATE_FORMAT_MMDDYY),
            array('-', '12-31-05', DATE_FORMAT_MMDDYY),
            array('-', '02-29-00', DATE_FORMAT_MMDDYY),
            array('-', '22-02-07', DATE_FORMAT_DDMMYY),
            array('-', '29-02-04', DATE_FORMAT_DDMMYY),
            array('-', '2007-03-25', DATE_FORMAT_YYYYMMDD)
        );

        $invalidDates = array(
            array('/', '00/00/00', DATE_FORMAT_MMDDYY),
            array('/', '02/29/05', DATE_FORMAT_MMDDYY),
            array('/', '02/31/05', DATE_FORMAT_MMDDYY),
            array('/', '13/01/05', DATE_FORMAT_MMDDYY),
            array('/', '00/01/05', DATE_FORMAT_MMDDYY),
            array('/', '12-01-05', DATE_FORMAT_MMDDYY),
            array('-', '00/01/05', DATE_FORMAT_MMDDYY),
            array('-', '00-01-05', DATE_FORMAT_MMDDYY),
            array('/', '00/01/2005', DATE_FORMAT_MMDDYY),
            array('-', '00/01/2005', DATE_FORMAT_MMDDYY),
            array('-', '00-01-2005', DATE_FORMAT_MMDDYY),
            array('-', '000105', DATE_FORMAT_MMDDYY),
            array('-', 'Test!', DATE_FORMAT_MMDDYY),
            array('-', '02-29-07', DATE_FORMAT_DDMMYY),
            array('-', '29-02-05', DATE_FORMAT_DDMMYY),
            array('-', '31-04-05', DATE_FORMAT_DDMMYY),
            array('-', '2007-03-40', DATE_FORMAT_YYYYMMDD),
            array('-', 'This sentence contains 12-01-05.', DATE_FORMAT_MMDDYY)
        );

        foreach ($validDates as $key => $value)
        {
            $this->assertTrue(
                DateUtility::validate($value[0], $value[1], $value[2]),
                $value[1] . ' (Separator: ' . $value[0] . ')'
                );
        }

        foreach ($invalidDates as $key => $value)
        {
            $this->assertFalse(
                DateUtility::validate($value[0], $value[1], $value[2]),
                $value[1] . ' (Separator: ' . $value[0] . ')'
                );
        }
    }

    /* Tests for convert(). */
    function testConvert()
    {
        $dates = array(
            array('/', '01/01/01', DATE_FORMAT_MMDDYY, DATE_FORMAT_YYYYMMDD, '2001/01/01'),
            array('/', '02/27/01', DATE_FORMAT_MMDDYY, DATE_FORMAT_YYYYMMDD, '2001/02/27'),
            array('-', '01-01-01', DATE_FORMAT_MMDDYY, DATE_FORMAT_YYYYMMDD, '2001-01-01'),
            array('-', '02-27-01', DATE_FORMAT_MMDDYY, DATE_FORMAT_YYYYMMDD, '2001-02-27'),
            array('/', '22/02/07', DATE_FORMAT_DDMMYY, DATE_FORMAT_YYYYMMDD, '2007/02/22'),
            array('-', '22-02-07', DATE_FORMAT_DDMMYY, DATE_FORMAT_YYYYMMDD, '2007-02-22'),
            array('-', '2002-01-30', DATE_FORMAT_YYYYMMDD, DATE_FORMAT_MMDDYY, '01-30-02'),
            array('-', '2007-02-22', DATE_FORMAT_YYYYMMDD, DATE_FORMAT_DDMMYY, '22-02-07'),
            array('-', '31-12-05', DATE_FORMAT_DDMMYY, DATE_FORMAT_MMDDYY, '12-31-05'),
            array('-', '12-31-05', DATE_FORMAT_MMDDYY, DATE_FORMAT_DDMMYY, '31-12-05'),
            array('-', 'invalid', DATE_FORMAT_DDMMYY, DATE_FORMAT_YYYYMMDD, '0000-00-00'),
            array('-', 'invalid', DATE_FORMAT_DDMMYY, DATE_FORMAT_MMDDYY, '00-00-00'),
        );

        foreach ($dates as $key => $value)
        {
            $this->assertSame(
                DateUtility::convert($value[0], $value[1], $value[2], $value[3]),
                $value[4]
                );
        }
    }
}

?>
