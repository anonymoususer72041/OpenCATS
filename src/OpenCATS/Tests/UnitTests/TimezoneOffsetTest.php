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

    function testCalendarLocalToUtcSummerDST()
    {
        /* A July event in Europe/Berlin (UTC+2 in summer) should be
         * stored 2 hours earlier in UTC. */
        $localDate = '2024-07-15 14:00:00';

        $rc = new ReflectionClass('Calendar');
        $method = $rc->getMethod('_localToUtc');
        $method->setAccessible(true);

        $utcDate = $method->invoke(null, $localDate, 'Europe/Berlin');

        $this->assertSame('2024-07-15 12:00:00', $utcDate);
    }

    function testCalendarLocalToUtcWinterDST()
    {
        /* A January event in Europe/Berlin (UTC+1 in winter) should be
         * stored 1 hour earlier in UTC. */
        $localDate = '2024-01-15 14:00:00';

        $rc = new ReflectionClass('Calendar');
        $method = $rc->getMethod('_localToUtc');
        $method->setAccessible(true);

        $utcDate = $method->invoke(null, $localDate, 'Europe/Berlin');

        $this->assertSame('2024-01-15 13:00:00', $utcDate);
    }

    function testCalendarLocalToUtcKolkata()
    {
        /* Asia/Kolkata is UTC+5:30 year-round (no DST). */
        $localDate = '2024-06-15 14:30:00';

        $rc = new ReflectionClass('Calendar');
        $method = $rc->getMethod('_localToUtc');
        $method->setAccessible(true);

        $utcDate = $method->invoke(null, $localDate, 'Asia/Kolkata');

        $this->assertSame('2024-06-15 09:00:00', $utcDate);
    }

    function testCalendarLocalToUtcFallbackOnInvalidTimezone()
    {
        $localDate = '2024-06-15 14:00:00';

        $rc = new ReflectionClass('Calendar');
        $method = $rc->getMethod('_localToUtc');
        $method->setAccessible(true);

        $utcDate = $method->invoke(null, $localDate, 'Invalid/Timezone');

        $this->assertSame($localDate, $utcDate);
    }

    function testCalendarLocalToUtcRejectsInvalidDay()
    {
        $rc = new ReflectionClass('Calendar');
        $method = $rc->getMethod('_localToUtc');
        $method->setAccessible(true);

        $input = '2024-02-31 10:00:00';
        $this->assertSame($input, $method->invoke(null, $input, 'Europe/Berlin'));
    }

    function testCalendarLocalToUtcRejectsInvalidMonth()
    {
        $rc = new ReflectionClass('Calendar');
        $method = $rc->getMethod('_localToUtc');
        $method->setAccessible(true);

        $input = '2024-13-01 10:00:00';
        $this->assertSame($input, $method->invoke(null, $input, 'Europe/Berlin'));
    }

    function testCalendarLocalToUtcRejectsDateOnly()
    {
        $rc = new ReflectionClass('Calendar');
        $method = $rc->getMethod('_localToUtc');
        $method->setAccessible(true);

        $input = '2024-01-01';
        $this->assertSame($input, $method->invoke(null, $input, 'Europe/Berlin'));
    }

    function testCalendarLocalToUtcRejectsNonDateString()
    {
        $rc = new ReflectionClass('Calendar');
        $method = $rc->getMethod('_localToUtc');
        $method->setAccessible(true);

        $input = 'not-a-date';
        $this->assertSame($input, $method->invoke(null, $input, 'Europe/Berlin'));
    }
}

?>
