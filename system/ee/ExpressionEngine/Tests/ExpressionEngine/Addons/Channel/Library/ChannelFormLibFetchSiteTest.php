<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibFetchSiteTest extends ChannelFormLibTestBase
{
    public function testFetchSiteWithSiteName()
    {
        // Mock the database to return a site
        $mockDbResult = new class {
            public $row_data = ['site_id' => 5];
            public function num_rows() { return 1; }
            public function row($key = null) {
                if ($key) return $this->row_data[$key];
                return (object) $this->row_data;
            }
        };

        $mockDb = new class($mockDbResult) {
            private $result;
            public function __construct($result) { $this->result = $result; }
            public function select($fields) { return $this; }
            public function from($table) { return $this; }
            public function where($field, $value) { return $this; }
            public function limit($limit) { return $this; }
            public function get() { return $this->result; }
        };

        $this->setMock('db', $mockDb);

        $this->channelFormLib->fetch_site('test_site');

        $this->assertEquals(5, $this->channelFormLib->site_id);
    }

    public function testFetchSiteWithSiteId()
    {
        $this->channelFormLib->fetch_site(false, 10);
        $this->assertEquals(10, $this->channelFormLib->site_id);
    }

    public function testFetchSiteWithInvalidSiteName()
    {
        // Mock the database to return no results
        $mockDbResult = new class {
            public function num_rows() { return 0; }
        };

        $mockDb = new class($mockDbResult) {
            private $result;
            public function __construct($result) { $this->result = $result; }
            public function select($fields) { return $this; }
            public function from($table) { return $this; }
            public function where($field, $value) { return $this; }
            public function limit($limit) { return $this; }
            public function get() { return $this->result; }
        };

        $this->setMock('db', $mockDb);

        // Should fall back to default site_id from config
        $this->channelFormLib->fetch_site('invalid_site');
        $this->assertEquals(1, $this->channelFormLib->site_id); // From our test config
    }

    public function testFetchSiteWithNullParameters()
    {
        $this->channelFormLib->fetch_site(null, null);
        $this->assertEquals(1, $this->channelFormLib->site_id); // From our test config
    }

    public function testFetchSiteWithEmptyString()
    {
        $this->channelFormLib->fetch_site('', null);
        $this->assertEquals(1, $this->channelFormLib->site_id); // From our test config
    }

    public function testFetchSiteWithFalseParameters()
    {
        $this->channelFormLib->fetch_site(false, false);
        $this->assertEquals(1, $this->channelFormLib->site_id); // From our test config
    }
}
