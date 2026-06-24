<?php
use PHPUnit\Framework\TestCase;

include_once(LEGACY_ROOT . '/constants.php');
include_once(LEGACY_ROOT . '/lib/StringUtility.php');
include_once(LEGACY_ROOT . '/lib/DateUtility.php');
include_once(LEGACY_ROOT . '/lib/Session.php');
include_once(LEGACY_ROOT . '/lib/Calendar.php');

class TimezoneOffsetTest extends TestCase
{
    private $_session;

    protected function setUp(): void
    {
        date_default_timezone_set('UTC');
        $this->_session = new CATSSession();

        /* Mark the session as logged in so that getTimeZoneOffsetMinutes()
         * returns computed values instead of the not-logged-in default of 0. */
        $rc = new ReflectionClass('CATSSession');
        $prop = $rc->getProperty('_isLoggedIn');
        $prop->setAccessible(true);
        $prop->setValue($this->_session, true);
    }

    function testBerlinOffsetMinutes()
    {
        $this->_session->setTimeDateLocalization(false, 'Europe/Berlin');

        $minutes = $this->_session->getTimeZoneOffsetMinutes();

        /* Europe/Berlin is UTC+1 in winter and UTC+2 in summer.
         * The offset must always be expressed in minutes. */
        $this->assertTrue(
            $minutes === 60 || $minutes === 120,
            'Europe/Berlin offset should be 60 or 120 minutes, got ' . $minutes
        );
    }

    function testBerlinOffsetHoursTruncates()
    {
        $this->_session->setTimeDateLocalization(false, 'Europe/Berlin');

        $hours = $this->_session->getTimeZoneOffsetHours();

        $this->assertTrue(
            $hours === 1 || $hours === 2,
            'Europe/Berlin hours should be 1 or 2, got ' . $hours
        );
    }

    function testBerlinLegacyGetTimeZoneOffsetReturnsHours()
    {
        $this->_session->setTimeDateLocalization(false, 'Europe/Berlin');

        $legacy = $this->_session->getTimeZoneOffset();
        $hours  = $this->_session->getTimeZoneOffsetHours();

        $this->assertSame($hours, $legacy,
            'getTimeZoneOffset() must match getTimeZoneOffsetHours()');
        $this->assertTrue(
            $legacy === 1 || $legacy === 2,
            'Legacy offset should be 1 or 2 hours, got ' . $legacy
        );
    }

    function testKolkataOffsetMinutes()
    {
        $this->_session->setTimeDateLocalization(false, 'Asia/Kolkata');

        $offset = $this->_session->getTimeZoneOffsetMinutes();

        $this->assertSame(330, $offset, 'Asia/Kolkata should be 330 minutes');
    }

    function testKolkataOffsetHoursTruncatesToFive()
    {
        $this->_session->setTimeDateLocalization(false, 'Asia/Kolkata');

        $hours = $this->_session->getTimeZoneOffsetHours();

        $this->assertSame(5, $hours, 'Asia/Kolkata truncated hours should be 5');
    }

    function testKolkataLegacyGetTimeZoneOffsetReturnsFive()
    {
        $this->_session->setTimeDateLocalization(false, 'Asia/Kolkata');

        $this->assertSame(5, $this->_session->getTimeZoneOffset(),
            'Asia/Kolkata legacy offset should be 5 hours');
    }

    function testNegativeOffsetHoursTruncation()
    {
        $this->_session->setTimeDateLocalization(false, 'Pacific/Marquesas');

        $hours = $this->_session->getTimeZoneOffsetHours();

        /* Pacific/Marquesas is UTC-9:30. Truncation toward zero = -9. */
        $this->assertSame(-9, $hours);

        $minutes = $this->_session->getTimeZoneOffsetMinutes();
        $this->assertSame(-570, $minutes);
    }

    function testStJohnsNegativeFractionalOffset()
    {
        $this->_session->setTimeDateLocalization(false, 'America/St_Johns');

        $minutes = $this->_session->getTimeZoneOffsetMinutes();

        /* America/St_Johns is UTC-3:30 (NST) or UTC-2:30 (NDT). */
        $this->assertTrue(
            $minutes === -210 || $minutes === -150,
            'America/St_Johns should be -210 or -150 minutes, got ' . $minutes
        );

        $hours = $this->_session->getTimeZoneOffsetHours();

        /* Truncation toward zero: -3:30 -> -3, -2:30 -> -2. */
        $this->assertTrue(
            $hours === -3 || $hours === -2,
            'America/St_Johns truncated hours should be -3 or -2, got ' . $hours
        );

        $legacy = $this->_session->getTimeZoneOffset();
        $this->assertSame($hours, $legacy,
            'getTimeZoneOffset() must match getTimeZoneOffsetHours()');
    }

    function testUtcOffsetIsZero()
    {
        $this->_session->setTimeDateLocalization(false, 'UTC');

        $this->assertSame(0, $this->_session->getTimeZoneOffsetMinutes());
        $this->assertSame(0, $this->_session->getTimeZoneOffsetHours());
        $this->assertSame(0, $this->_session->getTimeZoneOffset());
    }

    function testGetIanaTimeZone()
    {
        $this->_session->setTimeDateLocalization(false, 'Europe/Berlin');

        $this->assertSame('Europe/Berlin', $this->_session->getIanaTimeZone());
    }

    /**
     * Guard against accidentally feeding minute offsets into INTERVAL ... HOUR.
     * If getTimeZoneOffset() ever returns minutes instead of hours, this test
     * will catch values like 120 or 330 that would be wrong as HOUR intervals.
     */
    function testLegacyOffsetNeverExceedsFourteenHours()
    {
        $zones = array(
            'Pacific/Marquesas', 'America/New_York', 'UTC',
            'Europe/Berlin', 'Asia/Kolkata', 'Pacific/Auckland',
            'Pacific/Chatham',
        );

        foreach ($zones as $iana)
        {
            $this->_session->setTimeDateLocalization(false, $iana);
            $legacy = $this->_session->getTimeZoneOffset();
            $this->assertTrue(
                $legacy >= -12 && $legacy <= 14,
                $iana . ': getTimeZoneOffset() returned ' . $legacy
                    . ' which looks like minutes, not hours'
            );
        }
    }

    function testLocalToUtcSummerDST()
    {
        /* A July event in Europe/Berlin (UTC+2 in summer) should be
         * stored 2 hours earlier in UTC. */
        $utcDate = DateUtility::localDateTimeToUtc(
            '2024-07-15 14:00:00', 'Europe/Berlin'
        );

        $this->assertSame('2024-07-15 12:00:00', $utcDate);
    }

    function testLocalToUtcWinterDST()
    {
        /* A January event in Europe/Berlin (UTC+1 in winter) should be
         * stored 1 hour earlier in UTC. */
        $utcDate = DateUtility::localDateTimeToUtc(
            '2024-01-15 14:00:00', 'Europe/Berlin'
        );

        $this->assertSame('2024-01-15 13:00:00', $utcDate);
    }

    function testLocalToUtcKolkata()
    {
        /* Asia/Kolkata is UTC+5:30 year-round (no DST). */
        $utcDate = DateUtility::localDateTimeToUtc(
            '2024-06-15 14:30:00', 'Asia/Kolkata'
        );

        $this->assertSame('2024-06-15 09:00:00', $utcDate);
    }

    function testLocalToUtcFallbackOnInvalidTimezone()
    {
        $localDate = '2024-06-15 14:00:00';

        $utcDate = DateUtility::localDateTimeToUtc($localDate, 'Invalid/Timezone');

        $this->assertSame($localDate, $utcDate);
    }

    function testLocalToUtcRejectsInvalidDay()
    {
        $input = '2024-02-31 10:00:00';
        $this->assertSame(
            $input, DateUtility::localDateTimeToUtc($input, 'Europe/Berlin')
        );
    }

    function testLocalToUtcRejectsInvalidMonth()
    {
        $input = '2024-13-01 10:00:00';
        $this->assertSame(
            $input, DateUtility::localDateTimeToUtc($input, 'Europe/Berlin')
        );
    }

    function testLocalToUtcRejectsDateOnly()
    {
        $input = '2024-01-01';
        $this->assertSame(
            $input, DateUtility::localDateTimeToUtc($input, 'Europe/Berlin')
        );
    }

    function testLocalToUtcRejectsNonDateString()
    {
        $input = 'not-a-date';
        $this->assertSame(
            $input, DateUtility::localDateTimeToUtc($input, 'Europe/Berlin')
        );
    }

    function testLocalToUtcRejectsEmptyTimezone()
    {
        $input = '2024-06-15 14:00:00';
        $this->assertSame(
            $input, DateUtility::localDateTimeToUtc($input, '')
        );
    }

    /* ---------------------------------------------------------------
     * utcDateTimeToLocal()
     * --------------------------------------------------------------- */

    function testUtcToLocalBerlinWinter()
    {
        $result = DateUtility::utcDateTimeToLocal(
            '2024-01-15 12:00:00', 'Europe/Berlin', 'Y-m-d H:i:s'
        );
        $this->assertSame('2024-01-15 13:00:00', $result);
    }

    function testUtcToLocalBerlinSummer()
    {
        $result = DateUtility::utcDateTimeToLocal(
            '2024-07-15 12:00:00', 'Europe/Berlin', 'Y-m-d H:i:s'
        );
        $this->assertSame('2024-07-15 14:00:00', $result);
    }

    function testUtcToLocalKolkataFractionalOffset()
    {
        $result = DateUtility::utcDateTimeToLocal(
            '2024-06-15 09:00:00', 'Asia/Kolkata', 'Y-m-d H:i:s'
        );
        $this->assertSame('2024-06-15 14:30:00', $result);
    }

    function testUtcToLocalStJohnsNegativeFractional()
    {
        /* America/St_Johns is UTC-3:30 (NST) in January. */
        $result = DateUtility::utcDateTimeToLocal(
            '2024-01-15 12:00:00', 'America/St_Johns', 'Y-m-d H:i:s'
        );
        $this->assertSame('2024-01-15 08:30:00', $result);
    }

    function testUtcToLocalMarquesasNegativeOffset()
    {
        /* Pacific/Marquesas is UTC-9:30 year-round. */
        $result = DateUtility::utcDateTimeToLocal(
            '2024-06-15 12:00:00', 'Pacific/Marquesas', 'Y-m-d H:i:s'
        );
        $this->assertSame('2024-06-15 02:30:00', $result);
    }

    function testUtcToLocalDstBoundaryDateShift()
    {
        /* UTC midnight in January in Europe/Berlin is 01:00 local,
         * so the local date is still the same.  But for a west timezone
         * the date can shift backwards. */
        $result = DateUtility::utcDateTimeToLocal(
            '2024-01-15 03:00:00', 'America/New_York', 'Y-m-d H:i:s'
        );
        /* America/New_York is UTC-5 in January. */
        $this->assertSame('2024-01-14 22:00:00', $result);
    }

    function testUtcToLocalCustomFormat()
    {
        $result = DateUtility::utcDateTimeToLocal(
            '2024-07-15 14:30:00', 'Europe/Berlin', 'm-d-y (h:i A)'
        );
        $this->assertSame('07-15-24 (04:30 PM)', $result);
    }

    function testUtcToLocalReturnsEmptyStringForEmpty()
    {
        $this->assertSame(
            '', DateUtility::utcDateTimeToLocal('', 'Europe/Berlin')
        );
    }

    function testUtcToLocalReturnsZeroDateUnchanged()
    {
        $this->assertSame(
            '0000-00-00 00:00:00',
            DateUtility::utcDateTimeToLocal(
                '0000-00-00 00:00:00', 'Europe/Berlin'
            )
        );
    }

    function testUtcToLocalReturnsZeroDateOnlyUnchanged()
    {
        $this->assertSame(
            '0000-00-00',
            DateUtility::utcDateTimeToLocal('0000-00-00', 'Europe/Berlin')
        );
    }

    function testUtcToLocalReturnsOriginalOnInvalidTimezone()
    {
        $input = '2024-06-15 12:00:00';
        $this->assertSame(
            $input,
            DateUtility::utcDateTimeToLocal($input, 'Invalid/Zone')
        );
    }

    function testUtcToLocalReturnsOriginalOnEmptyTimezone()
    {
        $input = '2024-06-15 12:00:00';
        $this->assertSame(
            $input, DateUtility::utcDateTimeToLocal($input, '')
        );
    }

    function testUtcToLocalReturnsOriginalOnNonDateString()
    {
        $input = 'not-a-date';
        $this->assertSame(
            $input,
            DateUtility::utcDateTimeToLocal($input, 'Europe/Berlin')
        );
    }

    /* ---------------------------------------------------------------
     * mysqlFormatToPhp()
     * --------------------------------------------------------------- */

    function testMysqlFormatToPhpDateTimeFormat()
    {
        $this->assertSame(
            'm-d-y (h:i A)',
            DateUtility::mysqlFormatToPhp('%%m-%%d-%%y (%%h:%%i %%p)')
        );
    }

    function testMysqlFormatToPhpWithSeconds()
    {
        $this->assertSame(
            'm-d-y (h:i:s A)',
            DateUtility::mysqlFormatToPhp('%%m-%%d-%%y (%%h:%%i:%%s %%p)')
        );
    }

    function testMysqlFormatToPhpDateOnly()
    {
        $this->assertSame(
            'm-d-y',
            DateUtility::mysqlFormatToPhp('%%m-%%d-%%y')
        );
    }

    function testMysqlFormatToPhpFourDigitYear()
    {
        $this->assertSame(
            'Y-m-d',
            DateUtility::mysqlFormatToPhp('%%Y-%%m-%%d')
        );
    }

    function testMysqlFormatToPhp24HourAndMonthNoLeadingZero()
    {
        $this->assertSame(
            'H:i n',
            DateUtility::mysqlFormatToPhp('%%H:%%i %%c')
        );
    }

    function testMysqlFormatToPhpAbbreviatedMonth()
    {
        $this->assertSame(
            'M d, Y',
            DateUtility::mysqlFormatToPhp('%%b %%d, %%Y')
        );
    }

    function testMysqlFormatToPhpFullMonth()
    {
        $this->assertSame(
            'F d, Y',
            DateUtility::mysqlFormatToPhp('%%M %%d, %%Y')
        );
    }

    /* ---------------------------------------------------------------
     * formatDate()
     * --------------------------------------------------------------- */

    function testFormatDateBasic()
    {
        $this->assertSame(
            '06-15-24',
            DateUtility::formatDate('2024-06-15', 'm-d-y')
        );
    }

    function testFormatDateDMY()
    {
        $this->assertSame(
            '15-06-24',
            DateUtility::formatDate('2024-06-15', 'd-m-y')
        );
    }

    function testFormatDateWithTimeIgnored()
    {
        $this->assertSame(
            '01-15-24',
            DateUtility::formatDate('2024-01-15 13:00:00', 'm-d-y')
        );
    }

    function testFormatDateEmptyReturnsEmpty()
    {
        $this->assertSame('', DateUtility::formatDate(''));
    }

    function testFormatDateZeroDateReturnsZeroDate()
    {
        $this->assertSame(
            '0000-00-00', DateUtility::formatDate('0000-00-00')
        );
    }

    function testFormatDateZeroDatetimeReturnsZeroDatetime()
    {
        $this->assertSame(
            '0000-00-00 00:00:00',
            DateUtility::formatDate('0000-00-00 00:00:00')
        );
    }

    function testFormatDateInvalidReturnsOriginal()
    {
        $this->assertSame(
            'not-a-date',
            DateUtility::formatDate('not-a-date')
        );
    }
}

?>
