<?php
use PHPUnit\Framework\TestCase;

if( !defined('LEGACY_ROOT') )
{
    define('LEGACY_ROOT', '.');
}

include_once(LEGACY_ROOT . '/lib/ArrayUtility.php');

class ArrayUtilityTest extends TestCase
{
    /* Tests for implodeRange(). */
    function testImplodeRange()
    {
        $pieces = array(
            'Zero',
            'One',
            'Two',
            'Three',
            'Four',
            'Five'
        );

        $result = (new ArrayUtility())->implodeRange(' ', $pieces, 0, 5);
        $this->assertSame($result, 'Zero One Two Three Four Five');

        $result = (new ArrayUtility())->implodeRange(' ', $pieces, 0, 4);
        $this->assertSame($result, 'Zero One Two Three Four');

        $result = (new ArrayUtility())->implodeRange(' ', $pieces, 1, 4);
        $this->assertSame($result, 'One Two Three Four');

        $result = (new ArrayUtility())->implodeRange(' ', $pieces, 1, 3);
        $this->assertSame($result, 'One Two Three');

        $result = (new ArrayUtility())->implodeRange(' ', $pieces, 2, 3);
        $this->assertSame($result, 'Two Three');

        $result = (new ArrayUtility())->implodeRange(' ', $pieces, 2, 2);
        $this->assertSame($result, 'Two');

        $result = (new ArrayUtility())->implodeRange(' ', $pieces, 0, 6);
        $this->assertSame($result, 'Zero One Two Three Four Five');

        $result = (new ArrayUtility())->implodeRange(' ', $pieces, -500, 500);
        $this->assertSame($result, 'Zero One Two Three Four Five');

        $result = (new ArrayUtility())->implodeRange(', ', $pieces, -500, 500);
        $this->assertSame($result, 'Zero, One, Two, Three, Four, Five');
    }
}