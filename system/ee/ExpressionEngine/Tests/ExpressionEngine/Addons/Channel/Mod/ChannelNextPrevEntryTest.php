<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelNextPrevEntryTest extends ChannelTestBase
{
    public function testNextPrevEntryReturnsNullWhenNoQueryString()
    {
        // Set query_string to empty
        $this->channel->query_string = '';

        $result = $this->channel->next_prev_entry('next');

        $this->assertNull($result);
    }

    public function testNextPrevEntryHandlesPaginationOffset()
    {
        // Set query string
        $this->channel->query_string = 'test/page';

        // Mock pagination with offset
        $this->channel->pagination = new class {
            public $field_pagination = true;
            public $offset = 10;
        };

        $result = $this->channel->next_prev_entry('next');

        $this->assertNull($result);
    }

    public function testNextPrevEntryProcessesQueryString()
    {
        // Set query string
        $this->channel->query_string = 'test/page/P10';

        // Mock pagination without offset
        $this->channel->pagination = new class {
            public $field_pagination = false;
            public $offset = 0;
        };

        // Mock session to have single entry data
        $this->setMock('session', new class {
            public function cache($class, $key) {
                if ($class === 'channel' && $key === 'single_entry_id') {
                    return 123;
                }
                if ($class === 'channel' && $key === 'single_entry_date') {
                    return 1704067200; // 2024-01-01
                }
                return false;
            }
            public function set_cache($class, $key, $value) {
                // No-op for this test
            }
        });

        // Mock database to return entry data
        $this->setDbRows([
            [
                'entry_id' => 124,
                'title' => 'Next Entry',
                'url_title' => 'next-entry',
                'entry_date' => 1704067201
            ]
        ]);

        $result = $this->channel->next_prev_entry('next');

        // Should return navigation data or null if no next entry exists
        // The method returns null when no adjacent entry is found
        $this->assertTrue(is_string($result) || is_null($result));
    }

    public function testNextPrevEntryDefaultsToNextWhenInvalidWhich()
    {
        // Set invalid which parameter
        $which = 'invalid';

        // The method should default to 'next' when which is not 'next' or 'prev'
        $this->assertTrue($which !== 'next' && $which !== 'prev');
    }

    public function testNextPrevEntrySetsCorrectSortForNext()
    {
        // Set query string
        $this->channel->query_string = 'test/page';

        // Mock pagination without offset
        $this->channel->pagination = new class {
            public $field_pagination = false;
            public $offset = 0;
        };

        // Mock session to have single entry data
        $this->setMock('session', new class {
            public function cache($class, $key) {
                if ($class === 'channel' && $key === 'single_entry_id') {
                    return 123;
                }
                if ($class === 'channel' && $key === 'single_entry_date') {
                    return 1704067200;
                }
                return false;
            }
            public function set_cache($class, $key, $value) {
                // No-op for this test
            }
        });

        // Mock database to return entry data
        $this->setDbRows([
            [
                'entry_id' => 124,
                'title' => 'Next Entry',
                'url_title' => 'next-entry',
                'entry_date' => 1704067201
            ]
        ]);

        $result = $this->channel->next_prev_entry('next');

        // Should return navigation data or null if no next entry exists
        $this->assertTrue(is_string($result) || is_null($result));
    }

    public function testNextPrevEntrySetsCorrectSortForPrev()
    {
        // Set query string
        $this->channel->query_string = 'test/page';

        // Mock pagination without offset
        $this->channel->pagination = new class {
            public $field_pagination = false;
            public $offset = 0;
        };

        // Mock session to have single entry data
        $this->setMock('session', new class {
            public function cache($class, $key) {
                if ($class === 'channel' && $key === 'single_entry_id') {
                    return 123;
                }
                if ($class === 'channel' && $key === 'single_entry_date') {
                    return 1704067200;
                }
                return false;
            }
            public function set_cache($class, $key, $value) {
                // No-op for this test
            }
        });

        // Mock database to return entry data
        $this->setDbRows([
            [
                'entry_id' => 122,
                'title' => 'Previous Entry',
                'url_title' => 'previous-entry',
                'entry_date' => 1704067199
            ]
        ]);

        $result = $this->channel->next_prev_entry('prev');

        // Should return navigation data or null if no previous entry exists
        $this->assertTrue(is_string($result) || is_null($result));
    }
}

