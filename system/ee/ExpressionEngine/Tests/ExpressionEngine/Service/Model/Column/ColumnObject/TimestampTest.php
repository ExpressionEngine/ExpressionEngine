<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Model\Column\ColumnObject;

use DateTime;
use ExpressionEngine\Service\Model\Column\ColumnObject\Timestamp;
use PHPUnit\Framework\TestCase;

class TimestampTest extends TestCase
{
    public function testUnserialize()
    {
        $date = Timestamp::unserialize(1700000000);

        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame(1700000000, $date->getTimestamp());
        $this->assertNull(Timestamp::unserialize(null));
    }

    public function testSerialize()
    {
        $date = new DateTime('@1700000000');
        $this->assertSame(1700000000, Timestamp::serialize($date));
        $this->assertSame(123, Timestamp::serialize(123));
        $this->assertNull(Timestamp::serialize(null));
        $this->assertSame(456, Timestamp::serialize('456'));

        $date_string = '2024-01-02 03:04:05 UTC';
        $this->assertSame(strtotime($date_string), Timestamp::serialize($date_string));
    }
}

// EOF
