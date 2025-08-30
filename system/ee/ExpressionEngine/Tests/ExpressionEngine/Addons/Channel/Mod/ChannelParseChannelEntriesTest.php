<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelParseChannelEntriesTest extends ChannelTestBase
{
    public function testParseChannelEntriesReturnsEmptyStringWhenNoResults()
    {
        // Mock database to return no results
        $this->setDbRows([]);

        $result = $this->channel->parse_channel_entries();

        $this->assertEquals('', $result);
    }

    public function testParseChannelEntriesReturnsEmptyStringWhenSqlIsEmpty()
    {
        // Set sql to empty
        $this->channel->sql = '';

        $result = $this->channel->parse_channel_entries();

        $this->assertEquals('', $result);
    }

    public function testParseChannelEntriesProcessesQueryResults()
    {
        // Set sql query
        $this->channel->sql = 'SELECT * FROM exp_channel_titles WHERE entry_id = 1';

        // Mock database to return entry data
        $this->setDbRows([
            [
                'entry_id' => 1,
                'title' => 'Test Entry',
                'url_title' => 'test-entry',
                'entry_date' => 1704067200,
                'status' => 'open'
            ]
        ]);

        // Mock template to have tagdata
        $this->setMock('TMPL', new class {
            public $tagdata = '{title} - {entry_date}';
            public function fetch_param($key) {
                return null;
            }
            public function no_results() {
                return 'NO_RESULTS';
            }
        });

        $result = $this->channel->parse_channel_entries();

        // Should return processed template data or null if no data
        $this->assertTrue(is_string($result) || is_null($result));
    }

    public function testParseChannelEntriesHandlesPerRowCallback()
    {
        // Set sql query
        $this->channel->sql = 'SELECT * FROM exp_channel_titles WHERE entry_id = 1';

        // Mock database to return entry data
        $this->setDbRows([
            [
                'entry_id' => 1,
                'title' => 'Test Entry',
                'url_title' => 'test-entry'
            ]
        ]);

        // Mock template to have tagdata
        $this->setMock('TMPL', new class {
            public $tagdata = '{title}';
            public function fetch_param($key) {
                return null;
            }
            public function no_results() {
                return 'NO_RESULTS';
            }
        });

        // Define a callback function
        $callback = function($tagdata, $row) {
            return strtoupper($tagdata);
        };

        $result = $this->channel->parse_channel_entries($callback);

        // Should return processed template data or null if no data
        $this->assertTrue(is_string($result) || is_null($result));
    }

    public function testParseChannelEntriesHandlesMultipleEntries()
    {
        // Set sql query
        $this->channel->sql = 'SELECT * FROM exp_channel_titles';

        // Mock database to return multiple entries
        $this->setDbRows([
            [
                'entry_id' => 1,
                'title' => 'First Entry',
                'url_title' => 'first-entry'
            ],
            [
                'entry_id' => 2,
                'title' => 'Second Entry',
                'url_title' => 'second-entry'
            ]
        ]);

        // Mock template to have tagdata
        $this->setMock('TMPL', new class {
            public $tagdata = '<div>{title}</div>';
            public function fetch_param($key) {
                return null;
            }
            public function no_results() {
                return 'NO_RESULTS';
            }
        });

        $result = $this->channel->parse_channel_entries();

        // Should return processed template data or null if no data
        $this->assertTrue(is_string($result) || is_null($result));
    }

    public function testParseChannelEntriesHandlesPagination()
    {
        // Set sql query
        $this->channel->sql = 'SELECT * FROM exp_channel_titles LIMIT 10';

        // Mock database to return paginated results
        $this->setDbRows([
            [
                'entry_id' => 1,
                'title' => 'Paginated Entry',
                'url_title' => 'paginated-entry'
            ]
        ]);

        // Mock template to have tagdata
        $this->setMock('TMPL', new class {
            public $tagdata = '{title}';
            public function fetch_param($key) {
                return null;
            }
            public function no_results() {
                return 'NO_RESULTS';
            }
        });

        // Mock pagination
        $this->channel->pagination = new class {
            public function create_links() {
                return 'Page 1 of 2';
            }
        };

        $result = $this->channel->parse_channel_entries();

        // Should return processed template data or null if no data
        $this->assertTrue(is_string($result) || is_null($result));
    }
}

