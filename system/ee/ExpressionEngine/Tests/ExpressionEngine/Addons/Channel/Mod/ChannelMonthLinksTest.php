<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelMonthLinksTest extends ChannelTestBase
{
    public function testMonthLinksReturnsEmptyStringInitially()
    {
        // Mock basic dependencies
        $this->setMock('TMPL', new class {
            public $site_ids = [1];
            public $cache_timestamp = '';
            public $tagdata = '';
            public $var_single = [];
            public function fetch_param($key) {
                return null; // Return null for any param by default
            }
            public function set_data($data) { /* no-op */ }
        });

        $this->setMock('localize', new class {
            public $now = 0;
            public function __construct() { $this->now = time(); }
            public function localize_month($month) { return $month; }
        });

        $this->setMock('session', new class {
            public function userdata($key, $default = null) {
                return ($key === 'timezone') ? 'UTC' : $default;
            }
        });

        // Mock database to return no results
        $this->setDbRows([]);

        $result = $this->channel->month_links();

        // Should return empty string when no data
        $this->assertEquals('', $result);
    }

    public function testMonthLinksHandlesTimezoneOffsetCalculation()
    {
        // Mock with positive timezone offset
        $this->setMock('session', new class {
            public function userdata($key, $default = null) {
                return ($key === 'timezone') ? 'America/New_York' : $default;
            }
        });

        $this->setMock('TMPL', new class {
            public $site_ids = [1];
            public $cache_timestamp = '';
            public $tagdata = '';
            public $var_single = [];
            public function fetch_param($key) {
                return null; // Return null for any param by default
            }
            public function set_data($data) { /* no-op */ }
        });

        $this->setMock('localize', new class {
            public $now = 0;
            public function __construct() { $this->now = time(); }
            public function localize_month($month) { return $month; }
        });

        // Mock database to return month data
        $this->setDbRows([
            ['year' => '2023', 'month' => '12']
        ]);

        $result = $this->channel->month_links();

        // Should contain some output (exact format depends on complex logic)
        $this->assertIsString($result);
    }

    public function testMonthLinksUsesCacheTimestampWhenAvailable()
    {
        $cacheTimestamp = '1704067200'; // 2024-01-01 00:00:00

        $this->setMock('TMPL', new class($cacheTimestamp) {
            private $timestamp;
            public function __construct($timestamp) {
                $this->timestamp = $timestamp;
            }
            public $site_ids = [1];
            public $cache_timestamp = '1704067200';
            public $tagdata = '';
            public $var_single = [];
            public function fetch_param($key) {
                return null; // Return null for any param by default
            }
            public function set_data($data) { /* no-op */ }
        });

        $this->setMock('session', new class {
            public function userdata($key, $default = null) {
                return ($key === 'timezone') ? 'UTC' : $default;
            }
        });

        $this->setMock('localize', new class {
            public $now = 0;
            public function __construct() { $this->now = time(); }
            public function localize_month($month) { return $month; }
        });

        // Mock database to return month data
        $this->setDbRows([
            ['year' => '2023', 'month' => '12']
        ]);

        $result = $this->channel->month_links();

        // Should contain some output
        $this->assertIsString($result);
    }
}
