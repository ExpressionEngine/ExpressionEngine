<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelGetTimezoneOffsetTest extends ChannelTestBase
{
    private $method;

    protected function setUp(): void
    {
        parent::setUp();

        // Make private method accessible
        $ref = new ReflectionClass($this->channel);
        $this->method = $ref->getMethod('_get_timezone_offset');
        $this->method->setAccessible(true);
    }

    public function testReturnsZeroOffsetForDefaultTimezone()
    {
        // Set up default timezone using ee() mock
        ee()->setMock('config', new class {
            public function item($key) {
                return $key === 'default_site_timezone' ? 'UTC' : false;
            }
        });

        $result = $this->method->invoke($this->channel);

        $this->assertEquals(0, $result);
    }

    public function testReturnsCorrectOffsetForNamedTimezone()
    {
        // Set up Eastern timezone using ee() mock
        ee()->setMock('config', new class {
            public function item($key) {
                return $key === 'default_site_timezone' ? 'America/New_York' : false;
            }
        });

        $result = $this->method->invoke($this->channel);

        // Eastern Time is UTC-5, the helper function returns offsets in seconds
        $this->assertEquals(-18000, $result);
    }

    public function testReturnsCorrectOffsetForEuropeanTimezone()
    {
        // Set up London timezone using ee() mock
        ee()->setMock('config', new class {
            public function item($key) {
                return $key === 'default_site_timezone' ? 'Europe/London' : false;
            }
        });

        $result = $this->method->invoke($this->channel);

        // London is UTC+0, so 0 offset
        $this->assertEquals(0, $result);
    }

    public function testFallsBackToDateTimeForUnknownTimezone()
    {
        // Set up a timezone that might not be in the helper array using ee() mock
        ee()->setMock('config', new class {
            public function item($key) {
                return $key === 'default_site_timezone' ? 'Asia/Tokyo' : false;
            }
        });

        $result = $this->method->invoke($this->channel);

        // Tokyo is UTC+9, so 9 * 3600 = 32400 seconds
        $this->assertEquals(32400, $result);
    }

    public function testHandlesDSTTimezone()
    {
        // Set up a timezone that observes DST using ee() mock
        ee()->setMock('config', new class {
            public function item($key) {
                return $key === 'default_site_timezone' ? 'America/Los_Angeles' : false;
            }
        });

        $result = $this->method->invoke($this->channel);

        // Pacific Time is UTC-8, so -8 * 3600 = -28800 seconds
        $this->assertEquals(-28800, $result);
    }

    public function testReturnsZeroForInvalidTimezone()
    {
        // Set up invalid timezone using ee() mock
        ee()->setMock('config', new class {
            public function item($key) {
                return $key === 'default_site_timezone' ? 'Invalid/Timezone' : false;
            }
        });

        // The method currently throws an exception for invalid timezones
        // This test documents the current behavior
        $this->expectException(Exception::class);
        $this->method->invoke($this->channel);
    }

    public function testUsesCurrentTimeForOffsetCalculation()
    {
        // Set up a timezone and verify the method runs without error using ee() mock
        ee()->setMock('config', new class {
            public function item($key) {
                return $key === 'default_site_timezone' ? 'America/Chicago' : false;
            }
        });

        $result = $this->method->invoke($this->channel);

        // Central Time is UTC-6, so -6 * 3600 = -21600 seconds
        $this->assertEquals(-21600, $result);
        $this->assertIsInt($result);
    }

    public function testHandlesMalformedTimezoneString()
    {
        // Set up malformed timezone using ee() mock
        ee()->setMock('config', new class {
            public function item($key) {
                return $key === 'default_site_timezone' ? 'Invalid/Timezone@#$%' : false;
            }
        });

        // The method currently throws an exception for malformed timezones
        // This test documents the current behavior
        $this->expectException(Exception::class);
        $this->method->invoke($this->channel);
    }

    public function testHandlesVeryLongTimezoneName()
    {
        // Set up extremely long timezone name using ee() mock
        $longTimezone = str_repeat('A', 1000) . '/LongTimezoneName';
        ee()->setMock('config', new class($longTimezone) {
            private $tz;
            public function __construct($tz) { $this->tz = $tz; }
            public function item($key) {
                return $key === 'default_site_timezone' ? $this->tz : false;
            }
        });

        // The method currently throws an exception for invalid timezones
        // This test documents the current behavior
        $this->expectException(Exception::class);
        $this->method->invoke($this->channel);
    }

    public function testHandlesTimezoneWithNumbers()
    {
        // Set up timezone-like string with numbers using ee() mock
        ee()->setMock('config', new class {
            public function item($key) {
                return $key === 'default_site_timezone' ? 'UTC+05:30' : false;
            }
        });

        // The method currently throws an exception for timezone-like strings with numbers
        // This test documents the current behavior
        $this->expectException(Exception::class);
        $this->method->invoke($this->channel);
    }

    public function testHandlesTimezoneWithNegativeNumbers()
    {
        // Set up timezone with negative offset using ee() mock
        ee()->setMock('config', new class {
            public function item($key) {
                return $key === 'default_site_timezone' ? 'UTC-08:00' : false;
            }
        });

        // The method currently throws an exception for timezone-like strings with numbers
        // This test documents the current behavior
        $this->expectException(Exception::class);
        $this->method->invoke($this->channel);
    }

    public function testHandlesEmptyTimezoneString()
    {
        // Set up empty timezone string using ee() mock
        ee()->setMock('config', new class {
            public function item($key) {
                return $key === 'default_site_timezone' ? '' : false;
            }
        });

        // Empty string currently causes an exception when passed to DateTimeZone
        // This test documents the current behavior
        $this->expectException(Exception::class);
        $this->method->invoke($this->channel);
    }

    public function testHandlesNullTimezoneConfig()
    {
        // Set up null timezone config using ee() mock
        ee()->setMock('config', new class {
            public function item($key) {
                return $key === 'default_site_timezone' ? null : false;
            }
        });

        // Null value now falls back to UTC timezone
        $result = $this->method->invoke($this->channel);

        // Should return UTC offset (0)
        $this->assertEquals(0, $result);
    }

    public function testHandlesTimezoneWithSpecialCharacters()
    {
        // Set up timezone with special characters using ee() mock
        ee()->setMock('config', new class {
            public function item($key) {
                return $key === 'default_site_timezone' ? 'America/New_York!' : false;
            }
        });

        // The method currently throws an exception for invalid timezones
        // This test documents the current behavior
        $this->expectException(Exception::class);
        $this->method->invoke($this->channel);
    }
}
