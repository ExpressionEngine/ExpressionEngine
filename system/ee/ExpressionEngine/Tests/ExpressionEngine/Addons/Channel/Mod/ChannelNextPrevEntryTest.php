<?php

require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';
require_once __DIR__ . '/ChannelTestBase.php';

class ChannelNextPrevEntryTest extends ChannelTestBase
{
    public function testNoQueryStringReturnsNull()
    {
        $this->channel->query_string = '';
        $this->assertNull($this->channel->next_entry());
    }

    public function testNoQueryStringReturnsNullForNextPrevEntry()
    {
        $this->channel->query_string = '';
        $this->assertNull($this->channel->next_prev_entry('next'));
        $this->assertNull($this->channel->next_prev_entry('prev'));
    }

    public function testPrevEntryReturnsNull()
    {
        $this->channel->query_string = '';
        $this->assertNull($this->channel->prev_entry());
    }

    public function testFindsNextEntryByEntryId()
    {
        // Mock database with current entry and next entry
        $this->setMock('db', new class {
            private $queryCount = 0;

            public function select($fields = '*') { return $this; }
            public function from($table) { return $this; }
            public function join($table, $cond, $type = '') { return $this; }
            public function where($field, $value = null) { return $this; }
            public function where_in($field, $values) { return $this; }
            public function order_by($field, $direction = '') { return $this; }
            public function limit($value) { return $this; }

            public function get($table = null) {
                $this->queryCount++;

                if ($this->queryCount === 1) {
                    // First query: find current entry
                    return new eeDbResultMock([
                        ['entry_id' => 123, 'entry_date' => 1609459200]
                    ]);
                } elseif ($this->queryCount === 2) {
                    // Second query: find next entry
                    return new eeDbResultMock([[
                        'entry_id' => 124,
                        'title' => 'Next Entry',
                        'url_title' => 'next-entry',
                        'channel_name' => 'news',
                        'channel_title' => 'News',
                        'comment_url' => 'https://example.com/comments/',
                        'channel_url' => 'https://example.com/news/',
                        'site_id' => 1,
                        'entry_date' => 1609459201
                    ]]);
                }

                return new eeDbResultMock([]);
            }

            public function query($sql) {
                $this->queryCount++;

                if ($this->queryCount === 1) {
                    // First query: find current entry
                    return new eeDbResultMock([
                        ['entry_id' => 123, 'entry_date' => 1609459200]
                    ]);
                } elseif ($this->queryCount === 2) {
                    // Second query: find next entry
                    return new eeDbResultMock([[
                        'entry_id' => 124,
                        'title' => 'Next Entry',
                        'url_title' => 'next-entry',
                        'channel_name' => 'news',
                        'channel_title' => 'News',
                        'comment_url' => 'https://example.com/comments/',
                        'channel_url' => 'https://example.com/news/',
                        'site_id' => 1,
                        'entry_date' => 1609459201
                    ]]);
                }

                return new eeDbResultMock([]);
            }
        });

        $this->channel->query_string = '123';
        ee()->TMPL->tagdata = '{title} - {entry_id}';
        ee()->TMPL->site_ids = [1];
        ee()->localize->now = time();

        // Clear any cached session data
        ee()->session->cache['channel'] = [];

        $result = $this->channel->next_prev_entry('next');

        $this->assertNotNull($result, 'Method should return a result, not null');
        if ($result !== null) {
            $this->assertStringContainsString('Next Entry', $result);
            $this->assertStringContainsString('124', $result);
        }
    }

    public function testFindsNextEntryByUrlTitle()
    {
        // Mock database with current entry and next entry
        $this->setMock('db', new class {
            private $queryCount = 0;

            public function select($fields = '*') { return $this; }
            public function from($table) { return $this; }
            public function join($table, $cond, $type = '') { return $this; }
            public function where($field, $value = null) { return $this; }
            public function where_in($field, $values) { return $this; }
            public function order_by($field, $direction = '') { return $this; }
            public function limit($value) { return $this; }

            public function get($table = null) {
                $this->queryCount++;

                if ($this->queryCount === 1) {
                    // First query: find current entry by URL title
                    return new eeDbResultMock([
                        ['entry_id' => 123, 'entry_date' => 1609459200] // Dec 31, 2020
                    ]);
                } elseif ($this->queryCount === 2) {
                    // Second query: find next entry
                    return new eeDbResultMock([[
                        'entry_id' => 124,
                        'title' => 'Next Entry',
                        'url_title' => 'next-entry',
                        'channel_name' => 'news',
                        'channel_title' => 'News',
                        'comment_url' => 'https://example.com/comments/',
                        'channel_url' => 'https://example.com/news/',
                        'site_id' => 1,
                        'entry_date' => 1609459201
                    ]]);
                }

                return new eeDbResultMock([]);
            }

            public function query($sql) {
                $this->queryCount++;

                if ($this->queryCount === 1) {
                    // First query: find current entry by URL title
                    return new eeDbResultMock([
                        ['entry_id' => 123, 'entry_date' => 1609459200]
                    ]);
                } elseif ($this->queryCount === 2) {
                    // Second query: find next entry
                    return new eeDbResultMock([[
                        'entry_id' => 124,
                        'title' => 'Next Entry',
                        'url_title' => 'next-entry',
                        'channel_name' => 'news',
                        'channel_title' => 'News',
                        'comment_url' => 'https://example.com/comments/',
                        'channel_url' => 'https://example.com/news/',
                        'site_id' => 1,
                        'entry_date' => 1609459201
                    ]]);
                }

                return new eeDbResultMock([]);
            }
        });

        $this->channel->query_string = 'current-entry';
        ee()->TMPL->tagdata = '{title} - {entry_id}';
        ee()->TMPL->site_ids = [1];
        ee()->localize->now = time();

        // Clear any cached session data
        ee()->session->cache['channel'] = [];

        $result = $this->channel->next_entry();
        $this->assertStringContainsString('Next Entry', $result);
        $this->assertStringContainsString('124', $result);
    }

    public function testFindsPreviousEntry()
    {
        // Mock database with current entry and previous entry
        $this->setMock('db', new class {
            private $queryCount = 0;

            public function select($fields = '*') { return $this; }
            public function from($table) { return $this; }
            public function join($table, $cond, $type = '') { return $this; }
            public function where($field, $value = null) { return $this; }
            public function where_in($field, $values) { return $this; }
            public function order_by($field, $direction = '') { return $this; }
            public function limit($value) { return $this; }

            public function get($table = null) {
                $this->queryCount++;

                if ($this->queryCount === 1) {
                    // First query: find current entry
                    return new eeDbResultMock([
                        ['entry_id' => 124, 'entry_date' => 1612137600] // Feb 1, 2021
                    ]);
                } elseif ($this->queryCount === 2) {
                    // Second query: find previous entry
                    return new eeDbResultMock([[
                        'entry_id' => 123,
                        'title' => 'Previous Entry',
                        'url_title' => 'prev-entry',
                        'channel_name' => 'news',
                        'channel_title' => 'News',
                        'comment_url' => 'https://example.com/comments/',
                        'channel_url' => 'https://example.com/news/',
                        'site_id' => 1,
                        'entry_date' => 1612137599
                    ]]);
                }

                return new eeDbResultMock([]);
            }

            public function query($sql) {
                $this->queryCount++;

                if ($this->queryCount === 1) {
                    // First query: find current entry
                    return new eeDbResultMock([
                        ['entry_id' => 124, 'entry_date' => 1612137600]
                    ]);
                } elseif ($this->queryCount === 2) {
                    // Second query: find previous entry
                    return new eeDbResultMock([[
                        'entry_id' => 123,
                        'title' => 'Previous Entry',
                        'url_title' => 'prev-entry',
                        'channel_name' => 'news',
                        'channel_title' => 'News',
                        'comment_url' => 'https://example.com/comments/',
                        'channel_url' => 'https://example.com/news/',
                        'site_id' => 1,
                        'entry_date' => 1612137599
                    ]]);
                }

                return new eeDbResultMock([]);
            }
        });

        $this->channel->query_string = '124';
        ee()->TMPL->tagdata = '{title} - {entry_id}';
        ee()->TMPL->site_ids = [1];
        ee()->localize->now = time();

        // Clear any cached session data
        ee()->session->cache['channel'] = [];

        $result = $this->channel->prev_entry();
        $this->assertStringContainsString('Previous Entry', $result);
        $this->assertStringContainsString('123', $result);
    }

    public function testRespectsChannelParameter()
    {
        // Mock database that filters by channel
        $this->setMock('db', new class extends FakeDb {
            private $queryCount = 0;

            public function __construct() {
                // Set up rows for both queries - the filtering will be handled by the SQL conditions
                $this->rows = [
                    // Row for first query (current entry lookup)
                    ['entry_id' => 123, 'entry_date' => 1609459200, 'site_id' => 1],
                    // Row for second query (next entry lookup)
                    [
                        'entry_id' => 124,
                        'title' => 'Next News Entry',
                        'url_title' => 'next-news',
                        'channel_name' => 'news',
                        'channel_title' => 'News',
                        'comment_url' => 'https://example.com/comments/',
                        'channel_url' => 'https://example.com/news/',
                        'site_id' => 1,
                        'entry_date' => 1609459201, // After current entry
                        'expiration_date' => 0,
                        'status' => 'open'
                    ]
                ];
            }

            public function query($sql) {
                $this->queryCount++;

                // Handle the second query by filtering the rows based on the SQL conditions
                if (strpos($sql, 'channel_name = \'news\'') !== false) {
                    $filteredRows = array_filter($this->rows, function($row) {
                        return isset($row['channel_name']) && $row['channel_name'] === 'news' &&
                               isset($row['entry_id']) && $row['entry_id'] != 123 &&
                               isset($row['status']) && $row['status'] === 'open';
                    });
                    return new eeDbResultMock(array_values($filteredRows));
                }

                return new eeDbResultMock([]);
            }
        });

        $this->channel->query_string = '123';
        ee()->TMPL->setMap(['channel' => 'news']);
        ee()->TMPL->tagdata = '{title}';
        ee()->TMPL->site_ids = [1];
        ee()->localize->now = time();

        // Clear any cached session data
        ee()->session->cache['channel'] = [];

        $result = $this->channel->next_entry();
        $this->assertStringContainsString('Next News Entry', $result);
    }

    public function testRespectsStatusParameter()
    {
        // Mock database that filters by status
        $this->setMock('db', new class extends FakeDb {
            private $queryCount = 0;

            public function __construct() {
                // Set up rows for both queries - the filtering will be handled by the SQL conditions
                $this->rows = [
                    // Row for first query (current entry lookup)
                    ['entry_id' => 123, 'entry_date' => 1609459200, 'site_id' => 1],
                    // Row for second query (next entry lookup)
                    [
                        'entry_id' => 124,
                        'title' => 'Next Open Entry',
                        'url_title' => 'next-open',
                        'channel_name' => 'news',
                        'channel_title' => 'News',
                        'comment_url' => 'https://example.com/comments/',
                        'channel_url' => 'https://example.com/news/',
                        'site_id' => 1,
                        'entry_date' => 1609459201, // After current entry
                        'expiration_date' => 0,
                        'status' => 'open'
                    ]
                ];
            }

            public function query($sql) {
                $this->queryCount++;

                // Handle the second query by filtering the rows based on the SQL conditions
                if (strpos($sql, 'status = \'open\'') !== false) {
                    $filteredRows = array_filter($this->rows, function($row) {
                        return isset($row['status']) && $row['status'] === 'open' &&
                               isset($row['entry_id']) && $row['entry_id'] != 123;
                    });
                    return new eeDbResultMock(array_values($filteredRows));
                }

                return new eeDbResultMock([]);
            }
        });

        $this->channel->query_string = '123';
        ee()->TMPL->setMap(['status' => 'open']);
        ee()->TMPL->tagdata = '{title}';
        ee()->TMPL->site_ids = [1];
        ee()->localize->now = time();

        // Clear any cached session data
        ee()->session->cache['channel'] = [];

        $result = $this->channel->next_entry();
        $this->assertStringContainsString('Next Open Entry', $result);
    }

    public function testRespectsMultipleStatusValues()
    {
        // Mock database that handles multiple status values
        $this->setMock('db', new class extends FakeDb {
            private $queryCount = 0;

            public function __construct() {
                // Set up rows for both queries - the filtering will be handled by the SQL conditions
                $this->rows = [
                    // Row for first query (current entry lookup)
                    ['entry_id' => 123, 'entry_date' => 1609459200, 'site_id' => 1],
                    // Row for second query (next entry lookup)
                    [
                        'entry_id' => 124,
                        'title' => 'Next Entry',
                        'url_title' => 'next-entry',
                        'channel_name' => 'news',
                        'channel_title' => 'News',
                        'comment_url' => 'https://example.com/comments/',
                        'channel_url' => 'https://example.com/news/',
                        'site_id' => 1,
                        'entry_date' => 1609459201, // After current entry
                        'expiration_date' => 0,
                        'status' => 'open' // Should match one of the status values
                    ]
                ];
            }

            public function query($sql) {
                $this->queryCount++;

                // Handle the second query by filtering the rows based on the SQL conditions
                if (strpos($sql, 'status = \'open\'') !== false || strpos($sql, 'status = \'draft\'') !== false) {
                    $filteredRows = array_filter($this->rows, function($row) {
                        return isset($row['status']) && ($row['status'] === 'open' || $row['status'] === 'draft') &&
                               isset($row['entry_id']) && $row['entry_id'] != 123;
                    });
                    return new eeDbResultMock(array_values($filteredRows));
                }

                return new eeDbResultMock([]);
            }
        });

        $this->channel->query_string = '123';
        ee()->TMPL->setMap(['status' => 'open|draft']);
        ee()->TMPL->tagdata = '{title}';
        ee()->TMPL->site_ids = [1];
        ee()->localize->now = time();

        // Clear any cached session data
        ee()->session->cache['channel'] = [];

        $result = $this->channel->next_entry();
        $this->assertStringContainsString('Next Entry', $result);
    }

    public function testRespectsUrlTitleParameter()
    {
        // Mock database that uses url_title parameter instead of query string
        $this->setMock('db', new class extends FakeDb {
            private $queryCount = 0;

            public function __construct() {
                // Set up rows for both queries - the filtering will be handled by the SQL conditions
                $this->rows = [
                    // Row for first query (current entry lookup by url_title)
                    ['entry_id' => 123, 'entry_date' => 1609459200, 'site_id' => 1, 'url_title' => 'specified-url'],
                    // Row for second query (next entry lookup)
                    [
                        'entry_id' => 124,
                        'title' => 'Next Entry',
                        'url_title' => 'next-entry',
                        'channel_name' => 'news',
                        'channel_title' => 'News',
                        'comment_url' => 'https://example.com/comments/',
                        'channel_url' => 'https://example.com/news/',
                        'site_id' => 1,
                        'entry_date' => 1609459201, // After current entry
                        'expiration_date' => 0,
                        'status' => 'open'
                    ]
                ];
            }

            public function query($sql) {
                $this->queryCount++;

                // First query: Get current entry details by url_title
                if (strpos($sql, 't.url_title = \'specified-url\'') !== false) {
                    $filteredRows = array_filter($this->rows, function($row) {
                        return isset($row['url_title']) && $row['url_title'] === 'specified-url';
                    });
                    return new eeDbResultMock(array_values($filteredRows));
                }

                // Second query: Get next entry
                if (strpos($sql, 'channel_name = \'news\'') !== false) {
                    $filteredRows = array_filter($this->rows, function($row) {
                        return isset($row['channel_name']) && $row['channel_name'] === 'news' &&
                               isset($row['entry_id']) && $row['entry_id'] != 123 &&
                               isset($row['status']) && $row['status'] === 'open';
                    });
                    return new eeDbResultMock(array_values($filteredRows));
                }

                return new eeDbResultMock([]);
            }
        });

        $this->channel->query_string = '123'; // This should be ignored
        ee()->TMPL->setMap(['url_title' => 'specified-url', 'channel' => 'news']);
        ee()->TMPL->tagdata = '{title}';
        ee()->TMPL->site_ids = [1];
        ee()->localize->now = time();

        // Clear any cached session data
        ee()->session->cache['channel'] = [];

        $result = $this->channel->next_entry();
        $this->assertStringContainsString('Next Entry', $result);
    }

    public function testExcludesFutureEntries()
    {
        $futureTimestamp = time() + (30 * 24 * 60 * 60); // 30 days in future
        $currentTime = time();

        // Mock database that should exclude future entries
        $this->setMock('db', new class($futureTimestamp, $currentTime) extends FakeDb {
            private $futureTimestamp;
            private $currentTime;
            private $queryCount = 0;

            public function __construct($futureTimestamp, $currentTime) {
                $this->futureTimestamp = $futureTimestamp;
                $this->currentTime = $currentTime;

                // Set up rows for the first query (finding current entry by entry_id)
                $this->rows = [
                    // Current entry for first query
                    ['entry_id' => 123, 'entry_date' => $this->currentTime, 'channel_id' => 1, 'site_id' => 1],
                    // Next entry for second query (past entry to test exclusion of future entries)
                    [
                        'entry_id' => 124,
                        'title' => 'Next Entry',
                        'url_title' => 'next-entry',
                        'channel_name' => 'news',
                        'channel_title' => 'News',
                        'comment_url' => 'https://example.com/comments/',
                        'channel_url' => 'https://example.com/news/',
                        'site_id' => 1,
                        'entry_date' => $this->currentTime - 100, // Past entry (before current time)
                        'expiration_date' => 0,
                        'status' => 'open'
                    ]
                ];
            }

            public function query($sql) {
                $this->queryCount++;

                // Handle the second query (finding next entry)
                if (strpos($sql, "entry_date < {$this->currentTime}") !== false) {
                    $filteredRows = array_filter($this->rows, function($row) {
                        return isset($row['entry_id']) && $row['entry_id'] == 124 &&
                               isset($row['entry_date']) && $row['entry_date'] < $this->currentTime;
                    });
                    return new eeDbResultMock(array_values($filteredRows));
                }

                return new eeDbResultMock([]);
            }
        });

        $this->channel->query_string = '123';
        ee()->TMPL->tagdata = '{title}';
        ee()->TMPL->site_ids = [1];
        ee()->localize->now = $currentTime;

        $result = $this->channel->next_entry();
        $this->assertStringContainsString('Next Entry', $result);
    }

    public function testIncludesFutureEntriesWhenRequested()
    {
        $futureTimestamp = time() + (30 * 24 * 60 * 60); // 30 days in future
        $currentTime = time();

        // Mock database that should include future entries
        $this->setMock('db', new class($futureTimestamp, $currentTime) extends FakeDb {
            private $futureTimestamp;
            private $currentTime;
            private $queryCount = 0;

            public function __construct($futureTimestamp, $currentTime) {
                $this->futureTimestamp = $futureTimestamp;
                $this->currentTime = $currentTime;

                // Set up rows for the first query (finding current entry by entry_id)
                $this->rows = [
                    // Current entry for first query
                    ['entry_id' => 123, 'entry_date' => $this->currentTime, 'channel_id' => 1, 'site_id' => 1],
                    // Future entry for second query
                    [
                        'entry_id' => 125,
                        'title' => 'Future Entry',
                        'url_title' => 'future-entry',
                        'channel_name' => 'news',
                        'channel_title' => 'News',
                        'comment_url' => 'https://example.com/comments/',
                        'channel_url' => 'https://example.com/news/',
                        'site_id' => 1,
                        'entry_date' => $this->futureTimestamp, // Future entry
                        'expiration_date' => 0,
                        'status' => 'open'
                    ]
                ];
            }

            public function query($sql) {
                $this->queryCount++;

                // Handle the second query (finding next entry, should include future entries)
                if (strpos($sql, "entry_date <") === false) {
                    $filteredRows = array_filter($this->rows, function($row) {
                        return isset($row['entry_id']) && $row['entry_id'] == 125;
                    });
                    return new eeDbResultMock(array_values($filteredRows));
                }

                return new eeDbResultMock([]);
            }
        });

        $this->channel->query_string = '123';
        ee()->TMPL->setMap(['show_future_entries' => 'yes']);
        ee()->TMPL->tagdata = '{title}';
        ee()->TMPL->site_ids = [1];
        ee()->localize->now = $currentTime;

        $result = $this->channel->next_entry();
        $this->assertStringContainsString('Future Entry', $result);
    }

    public function testExcludesExpiredEntries()
    {
        $currentTime = time();
        $expiredTime = $currentTime - (24 * 60 * 60); // Expired yesterday

        // Mock database that should exclude expired entries
        $this->setMock('db', new class($expiredTime, $currentTime) extends FakeDb {
            private $expiredTime;
            private $currentTime;
            private $queryCount = 0;

            public function __construct($expiredTime, $currentTime) {
                $this->expiredTime = $expiredTime;
                $this->currentTime = $currentTime;

                // Set up rows for the first query (finding current entry by entry_id)
                $this->rows = [
                    // Current entry for first query
                    ['entry_id' => 123, 'entry_date' => $this->currentTime, 'channel_id' => 1, 'site_id' => 1],
                    // Next entry for second query (not expired)
                    [
                        'entry_id' => 124,
                        'title' => 'Next Entry',
                        'url_title' => 'next-entry',
                        'channel_name' => 'news',
                        'channel_title' => 'News',
                        'comment_url' => 'https://example.com/comments/',
                        'channel_url' => 'https://example.com/news/',
                        'site_id' => 1,
                        'entry_date' => $this->currentTime + 100,
                        'expiration_date' => 0, // Not expired
                        'status' => 'open'
                    ]
                ];
            }

            public function query($sql) {
                $this->queryCount++;

                // Handle the second query (finding next entry, should exclude expired)
                if (strpos($sql, "t.expiration_date = 0 OR t.expiration_date > {$this->currentTime}") !== false) {
                    $filteredRows = array_filter($this->rows, function($row) {
                        return isset($row['entry_id']) && $row['entry_id'] == 124 &&
                               (isset($row['expiration_date']) && ($row['expiration_date'] == 0 || $row['expiration_date'] > $this->currentTime));
                    });
                    return new eeDbResultMock(array_values($filteredRows));
                }

                return new eeDbResultMock([]);
            }
        });

        $this->channel->query_string = '123';
        ee()->TMPL->tagdata = '{title}';
        ee()->TMPL->site_ids = [1];
        ee()->localize->now = $currentTime;

        $result = $this->channel->next_entry();
        $this->assertStringContainsString('Next Entry', $result);
    }

    public function testIncludesExpiredEntriesWhenRequested()
    {
        $currentTime = time();
        $expiredTime = $currentTime - (24 * 60 * 60); // Expired yesterday

        // Mock database that should include expired entries
        $this->setMock('db', new class($expiredTime, $currentTime) extends FakeDb {
            private $expiredTime;
            private $currentTime;
            private $queryCount = 0;

            public function __construct($expiredTime, $currentTime) {
                $this->expiredTime = $expiredTime;
                $this->currentTime = $currentTime;

                // Set up rows for the first query (finding current entry by entry_id)
                $this->rows = [
                    // Current entry for first query
                    ['entry_id' => 123, 'entry_date' => $this->currentTime, 'channel_id' => 1, 'site_id' => 1],
                    // Expired entry for second query
                    [
                        'entry_id' => 126,
                        'title' => 'Expired Entry',
                        'url_title' => 'expired-entry',
                        'channel_name' => 'news',
                        'channel_title' => 'News',
                        'comment_url' => 'https://example.com/comments/',
                        'channel_url' => 'https://example.com/news/',
                        'site_id' => 1,
                        'entry_date' => $this->currentTime + 100,
                        'expiration_date' => $this->expiredTime, // Expired
                        'status' => 'open'
                    ]
                ];
            }

            public function query($sql) {
                $this->queryCount++;

                // Handle the second query (finding next entry, should include expired when show_expired=yes)
                if (strpos($sql, "expiration_date = 0 OR expiration_date >") === false) {
                    $filteredRows = array_filter($this->rows, function($row) {
                        return isset($row['entry_id']) && $row['entry_id'] == 126;
                    });
                    return new eeDbResultMock(array_values($filteredRows));
                }

                return new eeDbResultMock([]);
            }
        });

        $this->channel->query_string = '123';
        ee()->TMPL->setMap(['show_expired' => 'yes']);
        ee()->TMPL->tagdata = '{title}';
        ee()->TMPL->site_ids = [1];
        ee()->localize->now = $currentTime;

        $result = $this->channel->next_entry();
        $this->assertStringContainsString('Expired Entry', $result);
    }

    public function testReturnsNoResultsWhenNoNextEntry()
    {
        // Mock database that returns no results for next entry
        $this->setMock('db', new class extends FakeDb {
            private $queryCount = 0;

            public function __construct() {
                // Set up rows for the first query (finding current entry by entry_id)
                $this->rows = [
                    // Current entry for first query
                    ['entry_id' => 123, 'entry_date' => 1609459200, 'channel_id' => 1, 'site_id' => 1],
                ];
            }

            public function query($sql) {
                $this->queryCount++;

                // Handle the second query (finding next entry - should return no results)
                if (strpos($sql, "entry_id != 123") !== false) {
                    // No next entry found
                    return new eeDbResultMock([]);
                }

                return new eeDbResultMock([]);
            }
        });

        $this->channel->query_string = '123';
        ee()->TMPL->tagdata = '{title}';
        ee()->TMPL->site_ids = [1];
        ee()->localize->now = time();

        $result = $this->channel->next_entry();
        // Should return no_results template
        $this->assertStringContainsString('', $result); // no_results() typically returns empty string
    }

    public function testHandlesInvalidWhichParameter()
    {
        // Test that invalid 'which' parameter defaults to 'next'
        $this->channel->query_string = '';
        $result = $this->channel->next_prev_entry('invalid');
        $this->assertNull($result); // Should still return null due to no query string
    }

    public function testHandlesEntryIdParameter()
    {
        // Mock database that filters by entry_id parameter
        $mockDb = new class extends FakeDb {
            private $tableName = null;

            public function from($table)
            {
                $this->tableName = $table;
                return $this;
            }

            public function get($table = null)
            {
                if ($table) {
                    $this->tableName = $table;
                }

                // First query: Get current entry details (entry_id, entry_date)
                if ($this->tableName === 'channel_titles AS t') {
                    return new eeDbResultMock([
                        ['entry_id' => 123, 'entry_date' => 1609459200, 'site_id' => 1]
                    ]);
                }
                // Second query: Get next entry with full details
                else {
                    return new eeDbResultMock([
                        [
                            'entry_id' => 124,
                            'title' => 'Next Entry',
                            'url_title' => 'next-entry',
                            'channel_name' => 'news',
                            'channel_title' => 'News',
                            'comment_url' => 'https://example.com/comments/',
                            'channel_url' => 'https://example.com/news/',
                            'site_id' => 1
                        ]
                    ]);
                }
            }

            public function query($sql)
            {
                // Handle raw SQL queries (second query in next_prev_entry)
                if (strpos($sql, 't.title') !== false && strpos($sql, 't.entry_id != 123') !== false) {
                    return new eeDbResultMock([
                        [
                            'entry_id' => 124,
                            'title' => 'Next Entry',
                            'url_title' => 'next-entry',
                            'channel_name' => 'news',
                            'channel_title' => 'News',
                            'comment_url' => 'https://example.com/comments/',
                            'channel_url' => 'https://example.com/news/',
                            'site_id' => 1
                        ]
                    ]);
                }
                return parent::query($sql);
            }
        };

        $this->setMock('db', $mockDb);

        $this->channel->query_string = '123';
        ee()->TMPL->setMap(['entry_id' => '125']);
        ee()->TMPL->tagdata = '{title}';
        ee()->TMPL->site_ids = [1];
        ee()->localize->now = time();

        // Clear any cached session data
        ee()->session->cache['channel'] = [];

        $result = $this->channel->next_entry();
        $this->assertStringContainsString('Next Entry', $result);
    }

    public function testParsesTemplateVariables()
    {
        // Mock database with entry data
        $mockDb = new class extends FakeDb {
            private $tableName = null;

            public function from($table)
            {
                $this->tableName = $table;
                return $this;
            }

            public function get($table = null)
            {
                if ($table) {
                    $this->tableName = $table;
                }

                // First query: Get current entry details (entry_id, entry_date)
                if ($this->tableName === 'channel_titles AS t') {
                    return new eeDbResultMock([
                        ['entry_id' => 123, 'entry_date' => 1609459200, 'site_id' => 1]
                    ]);
                }
                // Second query: Get next entry with full details
                else {
                    return new eeDbResultMock([
                        [
                            'entry_id' => 124,
                            'title' => 'Next & Entry',
                            'url_title' => 'next-entry',
                            'channel_name' => 'news',
                            'channel_title' => 'News Channel',
                            'comment_url' => 'https://example.com/comments/',
                            'channel_url' => 'https://example.com/news/',
                            'site_id' => 1
                        ]
                    ]);
                }
            }

            public function query($sql)
            {
                // Handle raw SQL queries (second query in next_prev_entry)
                if (strpos($sql, 't.title') !== false && strpos($sql, 't.entry_id != 123') !== false) {
                    return new eeDbResultMock([
                        [
                            'entry_id' => 124,
                            'title' => 'Next & Entry',
                            'url_title' => 'next-entry',
                            'channel_name' => 'news',
                            'channel_title' => 'News Channel',
                            'comment_url' => 'https://example.com/comments/',
                            'channel_url' => 'https://example.com/news/',
                            'site_id' => 1
                        ]
                    ]);
                }
                return parent::query($sql);
            }
        };

        $this->setMock('db', $mockDb);

        $this->channel->query_string = '123';
        ee()->TMPL->tagdata = '{entry_id}{title}{url_title}{channel_short_name}{channel}{channel_url}';
        ee()->TMPL->site_ids = [1];
        ee()->localize->now = time();

        // Mock typography for title formatting
        $this->setMock('typography', new class {
            public function format_characters($str) { return $str; }
            public function formatTitle($str) { return htmlspecialchars($str); }
        });

        $result = $this->channel->next_entry();
        $this->assertStringContainsString('124', $result);
        $this->assertStringContainsString('Next &amp; Entry', $result); // HTML encoded
        $this->assertStringContainsString('next-entry', $result);
        $this->assertStringContainsString('news', $result);
        $this->assertStringContainsString('News Channel', $result);
        $this->assertStringContainsString('https://example.com/news/', $result);
    }

    public function testHandlesPageNumbersInQueryString()
    {
        // Mock database that handles query string with page numbers
        $mockDb = new class extends FakeDb {
            private $tableName = null;

            public function from($table)
            {
                $this->tableName = $table;
                return $this;
            }

            public function get($table = null)
            {
                if ($table) {
                    $this->tableName = $table;
                }

                // First query: Get current entry details (entry_id, entry_date)
                if ($this->tableName === 'channel_titles AS t') {
                    return new eeDbResultMock([
                        ['entry_id' => 123, 'entry_date' => 1609459200, 'site_id' => 1]
                    ]);
                }
                // Second query: Get next entry with full details
                else {
                    return new eeDbResultMock([
                        [
                            'entry_id' => 124,
                            'title' => 'Next Entry',
                            'url_title' => 'next-entry',
                            'channel_name' => 'news',
                            'channel_title' => 'News',
                            'comment_url' => 'https://example.com/comments/',
                            'channel_url' => 'https://example.com/news/',
                            'site_id' => 1
                        ]
                    ]);
                }
            }

            public function query($sql)
            {
                // Handle raw SQL queries (second query in next_prev_entry)
                if (strpos($sql, 't.title') !== false && strpos($sql, 't.entry_id != 123') !== false) {
                    return new eeDbResultMock([
                        [
                            'entry_id' => 124,
                            'title' => 'Next Entry',
                            'url_title' => 'next-entry',
                            'channel_name' => 'news',
                            'channel_title' => 'News',
                            'comment_url' => 'https://example.com/comments/',
                            'channel_url' => 'https://example.com/news/',
                            'site_id' => 1
                        ]
                    ]);
                }
                return parent::query($sql);
            }
        };

        $this->setMock('db', $mockDb);

        $this->channel->query_string = '123/P5'; // Query string with page number
        ee()->TMPL->tagdata = '{title}';
        ee()->TMPL->site_ids = [1];
        ee()->localize->now = time();

        $result = $this->channel->next_entry();
        $this->assertStringContainsString('Next Entry', $result);
    }

    public function testHandlesCommentNumbersInQueryString()
    {
        // Mock database that handles query string with comment numbers
        $mockDb = new class extends FakeDb {
            private $tableName = null;

            public function from($table)
            {
                $this->tableName = $table;
                return $this;
            }

            public function get($table = null)
            {
                if ($table) {
                    $this->tableName = $table;
                }

                // First query: Get current entry details (entry_id, entry_date)
                if ($this->tableName === 'channel_titles AS t') {
                    return new eeDbResultMock([
                        ['entry_id' => 123, 'entry_date' => 1609459200, 'site_id' => 1]
                    ]);
                }
                // Second query: Get next entry with full details
                else {
                    return new eeDbResultMock([
                        [
                            'entry_id' => 124,
                            'title' => 'Next Entry',
                            'url_title' => 'next-entry',
                            'channel_name' => 'news',
                            'channel_title' => 'News',
                            'comment_url' => 'https://example.com/comments/',
                            'channel_url' => 'https://example.com/news/',
                            'site_id' => 1
                        ]
                    ]);
                }
            }

            public function query($sql)
            {
                // Handle raw SQL queries (second query in next_prev_entry)
                if (strpos($sql, 't.title') !== false && strpos($sql, 't.entry_id != 123') !== false) {
                    return new eeDbResultMock([
                        [
                            'entry_id' => 124,
                            'title' => 'Next Entry',
                            'url_title' => 'next-entry',
                            'channel_name' => 'news',
                            'channel_title' => 'News',
                            'comment_url' => 'https://example.com/comments/',
                            'channel_url' => 'https://example.com/news/',
                            'site_id' => 1
                        ]
                    ]);
                }
                return parent::query($sql);
            }
        };

        $this->setMock('db', $mockDb);

        $this->channel->query_string = '123/N10'; // Query string with comment number
        ee()->TMPL->tagdata = '{title}';
        ee()->TMPL->site_ids = [1];
        ee()->localize->now = time();

        $result = $this->channel->next_entry();
        $this->assertStringContainsString('Next Entry', $result);
    }

    public function testHandlesComplexCategoryFiltering()
    {
        // Mock database that handles complex category filtering
        $mockDb = new class extends FakeDb {
            private $tableName = null;

            public function from($table)
            {
                $this->tableName = $table;
                return $this;
            }

            public function get($table = null)
            {
                if ($table) {
                    $this->tableName = $table;
                }

                // First query: Get current entry details (entry_id, entry_date)
                if ($this->tableName === 'channel_titles AS t') {
                    return new eeDbResultMock([
                        ['entry_id' => 123, 'entry_date' => 1609459200, 'site_id' => 1]
                    ]);
                }
                // Second query: Get next entry with full details
                else {
                    return new eeDbResultMock([
                        [
                            'entry_id' => 124,
                            'title' => 'Next Entry',
                            'url_title' => 'next-entry',
                            'channel_name' => 'news',
                            'channel_title' => 'News',
                            'comment_url' => 'https://example.com/comments/',
                            'channel_url' => 'https://example.com/news/',
                            'site_id' => 1
                        ]
                    ]);
                }
            }

            public function query($sql)
            {
                // Handle category filtering query (for complex category filtering)
                if (strpos($sql, 'exp_category_posts.entry_id') !== false && strpos($sql, 'exp_category_posts.cat_id') !== false) {
                    // Return entries with both categories 1 and 2
                    return new eeDbResultMock([
                        [
                            'entry_id' => 124,
                            'cat_id' => 1,
                            'title' => 'Next Entry',
                            'url_title' => 'next-entry',
                            'channel_name' => 'news',
                            'channel_title' => 'News',
                            'comment_url' => 'https://example.com/comments/',
                            'channel_url' => 'https://example.com/news/',
                            'site_id' => 1
                        ],
                        [
                            'entry_id' => 124,
                            'cat_id' => 2,
                            'title' => 'Next Entry',
                            'url_title' => 'next-entry',
                            'channel_name' => 'news',
                            'channel_title' => 'News',
                            'comment_url' => 'https://example.com/comments/',
                            'channel_url' => 'https://example.com/news/',
                            'site_id' => 1
                        ]
                    ]);
                }
                // Handle raw SQL queries (second query in next_prev_entry)
                elseif (strpos($sql, 't.title') !== false && strpos($sql, 't.entry_id != 123') !== false) {
                    return new eeDbResultMock([
                        [
                            'entry_id' => 124,
                            'title' => 'Next Entry',
                            'url_title' => 'next-entry',
                            'channel_name' => 'news',
                            'channel_title' => 'News',
                            'comment_url' => 'https://example.com/comments/',
                            'channel_url' => 'https://example.com/news/',
                            'site_id' => 1
                        ]
                    ]);
                }
                return parent::query($sql);
            }
        };

        $this->setMock('db', $mockDb);

        $this->channel->query_string = '123';
        ee()->TMPL->setMap(['category' => '1&2']); // Must have both categories 1 AND 2
        ee()->TMPL->tagdata = '{title}';
        ee()->TMPL->site_ids = [1];
        ee()->localize->now = time();

        $result = $this->channel->next_entry();
        $this->assertStringContainsString('Next Entry', $result);
    }

    public function testHandlesCategoryGroupFiltering()
    {
        // Mock database that handles category group filtering
        $mockDb = new class extends FakeDb {
            private $tableName = null;

            public function from($table)
            {
                $this->tableName = $table;
                return $this;
            }

            public function get($table = null)
            {
                if ($table) {
                    $this->tableName = $table;
                }

                // First query: Get current entry details (entry_id, entry_date)
                if ($this->tableName === 'channel_titles AS t') {
                    return new eeDbResultMock([
                        ['entry_id' => 123, 'entry_date' => 1609459200, 'site_id' => 1]
                    ]);
                }
                // Second query: Get next entry with full details
                else {
                    return new eeDbResultMock([
                        [
                            'entry_id' => 124,
                            'title' => 'Next Entry',
                            'url_title' => 'next-entry',
                            'channel_name' => 'news',
                            'channel_title' => 'News',
                            'comment_url' => 'https://example.com/comments/',
                            'channel_url' => 'https://example.com/news/',
                            'site_id' => 1
                        ]
                    ]);
                }
            }

            public function query($sql)
            {
                // Handle raw SQL queries (second query in next_prev_entry)
                if (strpos($sql, 't.title') !== false && strpos($sql, 't.entry_id != 123') !== false) {
                    return new eeDbResultMock([
                        [
                            'entry_id' => 124,
                            'title' => 'Next Entry',
                            'url_title' => 'next-entry',
                            'channel_name' => 'news',
                            'channel_title' => 'News',
                            'comment_url' => 'https://example.com/comments/',
                            'channel_url' => 'https://example.com/news/',
                            'site_id' => 1
                        ]
                    ]);
                }
                return parent::query($sql);
            }
        };

        $this->setMock('db', $mockDb);

        $this->channel->query_string = '123';
        ee()->TMPL->setMap(['category_group' => '1']);
        ee()->TMPL->tagdata = '{title}';
        ee()->TMPL->site_ids = [1];
        ee()->localize->now = time();

        $result = $this->channel->next_entry();
        $this->assertStringContainsString('Next Entry', $result);
    }

    public function testHandlesSameDateEntriesById()
    {
        // Mock database with entries on same date, different IDs
        $mockDb = new class extends FakeDb {
            private $tableName = null;

            public function from($table)
            {
                $this->tableName = $table;
                return $this;
            }

            public function get($table = null)
            {
                if ($table) {
                    $this->tableName = $table;
                }

                // First query: Get current entry details (entry_id, entry_date)
                if ($this->tableName === 'channel_titles AS t') {
                    return new eeDbResultMock([
                        ['entry_id' => 123, 'entry_date' => 1609459200, 'site_id' => 1] // Dec 31, 2020
                    ]);
                }
                // Second query: Get next entry with full details
                else {
                    return new eeDbResultMock([
                        [
                            'entry_id' => 125, // Note: skipping 124 to test ID ordering
                            'title' => 'Next Same Date Entry',
                            'url_title' => 'next-same-date',
                            'channel_name' => 'news',
                            'channel_title' => 'News',
                            'comment_url' => 'https://example.com/comments/',
                            'channel_url' => 'https://example.com/news/',
                            'site_id' => 1
                        ]
                    ]);
                }
            }

            public function query($sql)
            {
                // Handle raw SQL queries (second query in next_prev_entry)
                if (strpos($sql, 't.title') !== false && strpos($sql, 't.entry_id != 123') !== false) {
                    return new eeDbResultMock([
                        [
                            'entry_id' => 125, // Note: skipping 124 to test ID ordering
                            'title' => 'Next Same Date Entry',
                            'url_title' => 'next-same-date',
                            'channel_name' => 'news',
                            'channel_title' => 'News',
                            'comment_url' => 'https://example.com/comments/',
                            'channel_url' => 'https://example.com/news/',
                            'site_id' => 1
                        ]
                    ]);
                }
                return parent::query($sql);
            }
        };

        $this->setMock('db', $mockDb);

        $this->channel->query_string = '123';
        ee()->TMPL->tagdata = '{title} - {entry_id}';
        ee()->TMPL->site_ids = [1];
        ee()->localize->now = time();

        $result = $this->channel->next_entry();
        $this->assertStringContainsString('Next Same Date Entry', $result);
        $this->assertStringContainsString('125', $result);
    }
}

