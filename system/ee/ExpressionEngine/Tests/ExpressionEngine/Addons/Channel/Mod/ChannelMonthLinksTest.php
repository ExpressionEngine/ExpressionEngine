<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelMonthLinksTest extends ChannelTestBase
{
    public function testNoEntriesReturnsEmpty()
    {
        $this->setDbRows([]);
        ee()->TMPL->tagdata = '{year}/{month_num}';
        ee()->TMPL->var_single = [
            'year' => '{year}',
            'month_num' => '{month_num}'
        ];
        ee()->session->set_userdata('timezone', 'UTC');
        $this->assertSame('', $this->channel->month_links());
    }

    public function testRespectsDateBoundaries()
    {
        // Two months: Jan and Feb
        $this->setDbRows([
            ['year' => 2024, 'month' => 1],
            ['year' => 2024, 'month' => 2],
        ]);
        ee()->TMPL->tagdata = '{year}-{month_num}';
        ee()->TMPL->var_single = [
            'year' => '{year}',
            'month_num' => '{month_num}'
        ];
        ee()->session->set_userdata('timezone', 'UTC');
        $out = $this->channel->month_links();
        $this->assertStringContainsString('2024-01', $out);
        $this->assertStringContainsString('2024-02', $out);
    }

    public function testRespectsTimezoneSettings()
    {
        // Test with different timezone
        $this->setDbRows([
            ['year' => 2024, 'month' => 1],
        ]);
        ee()->TMPL->tagdata = '{year}-{month_num}';
        ee()->TMPL->var_single = [
            'year' => '{year}',
            'month_num' => '{month_num}'
        ];
        ee()->session->set_userdata('timezone', 'America/New_York'); // UTC-5
        ee()->config->items['default_site_timezone'] = 'UTC';
        $out = $this->channel->month_links();
        $this->assertStringContainsString('2024-01', $out);
    }

    public function testFiltersByChannel()
    {
        // Mock database that handles both channel lookup and month links queries
        $this->setMock('db', new class extends FakeDb {
            public $queryCount = 0;
            public $rows = [
                ['year' => 2024, 'month' => 1, 'channel_id' => 1],
                ['year' => 2024, 'month' => 2, 'channel_id' => 2],
            ];

            public function query($sql) {
                $this->queryCount++;
                if ($this->queryCount === 1) {
                    // First query is for channel lookup
                    return new eeDbResultMock([['channel_id' => 1]]);
                }
                // Second query is for month links with channel filtering
                if (strpos($sql, 'channel_id = \'1\'') !== false) {
                    $filtered = array_filter($this->rows, function($row) {
                        return $row['channel_id'] == 1;
                    });
                    return new eeDbResultMock(array_values($filtered));
                }
                return new eeDbResultMock($this->rows);
            }
        });

        ee()->TMPL->setMap(['channel' => 'news']);
        ee()->TMPL->tagdata = '{year}-{month_num}';
        ee()->TMPL->var_single = [
            'year' => '{year}',
            'month_num' => '{month_num}'
        ];
        ee()->session->set_userdata('timezone', 'UTC');
        $out = $this->channel->month_links();
        $this->assertStringContainsString('2024-01', $out);
        $this->assertStringNotContainsString('2024-02', $out);
    }

    public function testFiltersByStatus()
    {
        // Mock database that filters by status
        $this->setMock('db', new class([
            ['year' => 2024, 'month' => 1, 'status' => 'open'],
            ['year' => 2024, 'month' => 2, 'status' => 'closed'],
        ]) extends FakeDb {
            public $rows;
            public function __construct($rows) {
                $this->rows = $rows;
            }
            public function query($sql) {
                // Filter rows based on status in SQL
                if (strpos($sql, "status = 'open'") !== false || strpos($sql, "status='open'") !== false) {
                    $filtered = array_filter($this->rows, function($row) {
                        return $row['status'] === 'open';
                    });
                } else {
                    $filtered = $this->rows;
                }
                return new eeDbResultMock(array_values($filtered));
            }
        });

        ee()->TMPL->setMap(['status' => 'open']);
        ee()->TMPL->tagdata = '{year}-{month_num}';
        ee()->TMPL->var_single = [
            'year' => '{year}',
            'month_num' => '{month_num}'
        ];
        ee()->session->set_userdata('timezone', 'UTC');
        $out = $this->channel->month_links();
        $this->assertStringContainsString('2024-01', $out);
        $this->assertStringNotContainsString('2024-02', $out);
    }

    public function testRespectsSortOrderDescending()
    {
        // Mock database that returns results in descending order
        $this->setMock('db', new class extends FakeDb {
            public function query($sql) {
                // Return results in descending order for DESC sort
                if (strpos($sql, 'ORDER BY year desc, month desc') !== false) {
                    return new eeDbResultMock([
                        ['year' => 2024, 'month' => 3],
                        ['year' => 2024, 'month' => 2],
                        ['year' => 2024, 'month' => 1],
                    ]);
                }
                return new eeDbResultMock([
                    ['year' => 2024, 'month' => 1],
                    ['year' => 2024, 'month' => 2],
                    ['year' => 2024, 'month' => 3],
                ]);
            }
        });

        ee()->TMPL->setMap(['sort' => 'desc']);
        ee()->TMPL->tagdata = '{month_num}';
        ee()->TMPL->var_single = [
            'month_num' => '{month_num}'
        ];
        ee()->session->set_userdata('timezone', 'UTC');
        $out = $this->channel->month_links();
        // Should appear in descending order: 3, 2, 1
        $pos3 = strpos($out, '03');
        $pos2 = strpos($out, '02');
        $pos1 = strpos($out, '01');
        $this->assertTrue($pos3 < $pos2 && $pos2 < $pos1, 'Months should be in descending order');
    }

    public function testRespectsSortOrderAscending()
    {
        // Mock database that returns results in ascending order
        $this->setMock('db', new class extends FakeDb {
            public function query($sql) {
                // Return results in ascending order for ASC sort
                if (strpos($sql, 'ORDER BY year asc, month asc') !== false) {
                    return new eeDbResultMock([
                        ['year' => 2024, 'month' => 1],
                        ['year' => 2024, 'month' => 2],
                        ['year' => 2024, 'month' => 3],
                    ]);
                }
                return new eeDbResultMock([
                    ['year' => 2024, 'month' => 3],
                    ['year' => 2024, 'month' => 1],
                    ['year' => 2024, 'month' => 2],
                ]);
            }
        });

        ee()->TMPL->setMap(['sort' => 'asc']);
        ee()->TMPL->tagdata = '{month_num}';
        ee()->TMPL->var_single = [
            'month_num' => '{month_num}'
        ];
        ee()->session->set_userdata('timezone', 'UTC');
        $out = $this->channel->month_links();
        // Should appear in ascending order: 1, 2, 3
        $pos1 = strpos($out, '01');
        $pos2 = strpos($out, '02');
        $pos3 = strpos($out, '03');
        $this->assertTrue($pos1 < $pos2 && $pos2 < $pos3, 'Months should be in ascending order');
    }

    public function testRespectsLimit()
    {
        // Mock database that respects LIMIT clause
        $this->setMock('db', new class extends FakeDb {
            public function query($sql) {
                // Extract limit from SQL
                if (preg_match('/LIMIT (\d+)/', $sql, $matches)) {
                    $limit = (int) $matches[1];
                    $rows = [
                        ['year' => 2024, 'month' => 1],
                        ['year' => 2024, 'month' => 2],
                        ['year' => 2024, 'month' => 3],
                        ['year' => 2024, 'month' => 4],
                    ];
                    return new eeDbResultMock(array_slice($rows, 0, $limit));
                }
                return new eeDbResultMock([
                    ['year' => 2024, 'month' => 1],
                    ['year' => 2024, 'month' => 2],
                    ['year' => 2024, 'month' => 3],
                    ['year' => 2024, 'month' => 4],
                ]);
            }
        });

        ee()->TMPL->setMap(['limit' => '2']);
        ee()->TMPL->tagdata = '{month_num}';
        ee()->TMPL->var_single = [
            'month_num' => '{month_num}'
        ];
        ee()->session->set_userdata('timezone', 'UTC');
        $out = $this->channel->month_links();
        $this->assertStringContainsString('01', $out);
        $this->assertStringContainsString('02', $out);
        $this->assertStringNotContainsString('03', $out);
        $this->assertStringNotContainsString('04', $out);
    }

    public function testExcludesFutureEntries()
    {
        $futureTimestamp = time() + (30 * 24 * 60 * 60); // 30 days in future
        $currentTime = time();

        // Mock database that filters out future entries
        $this->setMock('db', new class($futureTimestamp, $currentTime) extends FakeDb {
            private $futureTimestamp;
            private $currentTime;

            public function __construct($futureTimestamp, $currentTime) {
                $this->futureTimestamp = $futureTimestamp;
                $this->currentTime = $currentTime;
            }

            public function query($sql) {
                // Filter out future entries when SQL contains entry_date condition
                if (strpos($sql, 'entry_date <') !== false) {
                    return new eeDbResultMock([
                        ['year' => 2024, 'month' => 1, 'entry_date' => $this->currentTime],
                    ]);
                }
                return new eeDbResultMock([
                    ['year' => 2024, 'month' => 1, 'entry_date' => $this->currentTime],
                    ['year' => 2024, 'month' => 2, 'entry_date' => $this->futureTimestamp],
                ]);
            }
        });

        ee()->TMPL->tagdata = '{month_num}';
        ee()->TMPL->var_single = [
            'month_num' => '{month_num}'
        ];
        ee()->session->set_userdata('timezone', 'UTC');
        ee()->localize->now = $currentTime;
        $out = $this->channel->month_links();
        $this->assertStringContainsString('01', $out);
        $this->assertStringNotContainsString('02', $out);
    }

    public function testIncludesFutureEntriesWhenRequested()
    {
        $futureTimestamp = time() + (30 * 24 * 60 * 60); // 30 days in future
        $currentTime = time();

        // Mock database that includes all entries when show_future_entries=yes
        $this->setMock('db', new class($futureTimestamp, $currentTime) extends FakeDb {
            private $futureTimestamp;
            private $currentTime;

            public function __construct($futureTimestamp, $currentTime) {
                $this->futureTimestamp = $futureTimestamp;
                $this->currentTime = $currentTime;
            }

            public function query($sql) {
                // Return all entries when no entry_date filtering
                return new eeDbResultMock([
                    ['year' => 2024, 'month' => 1, 'entry_date' => $this->currentTime],
                    ['year' => 2024, 'month' => 2, 'entry_date' => $this->futureTimestamp],
                ]);
            }
        });

        ee()->TMPL->setMap(['show_future_entries' => 'yes']);
        ee()->TMPL->tagdata = '{month_num}';
        ee()->TMPL->var_single = [
            'month_num' => '{month_num}'
        ];
        ee()->session->set_userdata('timezone', 'UTC');
        ee()->localize->now = $currentTime;
        $out = $this->channel->month_links();
        $this->assertStringContainsString('01', $out);
        $this->assertStringContainsString('02', $out);
    }

    public function testExcludesExpiredEntries()
    {
        $pastTimestamp = time() - (30 * 24 * 60 * 60); // 30 days ago
        $currentTime = time();
        $expiredTime = $currentTime - (24 * 60 * 60); // Expired

        // Mock database that filters out expired entries
        $this->setMock('db', new class($pastTimestamp, $currentTime, $expiredTime) extends FakeDb {
            private $pastTimestamp;
            private $currentTime;
            private $expiredTime;

            public function __construct($pastTimestamp, $currentTime, $expiredTime) {
                $this->pastTimestamp = $pastTimestamp;
                $this->currentTime = $currentTime;
                $this->expiredTime = $expiredTime;
            }

            public function query($sql) {
                // Filter out expired entries when SQL contains expiration_date condition
                if (strpos($sql, 'expiration_date') !== false) {
                    return new eeDbResultMock([
                        ['year' => 2024, 'month' => 1, 'entry_date' => $this->currentTime, 'expiration_date' => 0],
                    ]);
                }
                return new eeDbResultMock([
                    ['year' => 2024, 'month' => 1, 'entry_date' => $this->currentTime, 'expiration_date' => 0],
                    ['year' => 2024, 'month' => 2, 'entry_date' => $this->pastTimestamp, 'expiration_date' => $this->expiredTime],
                ]);
            }
        });

        ee()->TMPL->tagdata = '{month_num}';
        ee()->TMPL->var_single = [
            'month_num' => '{month_num}'
        ];
        ee()->session->set_userdata('timezone', 'UTC');
        ee()->localize->now = $currentTime;
        $out = $this->channel->month_links();
        $this->assertStringContainsString('01', $out);
        $this->assertStringNotContainsString('02', $out);
    }

    public function testIncludesExpiredEntriesWhenRequested()
    {
        $pastTimestamp = time() - (30 * 24 * 60 * 60); // 30 days ago
        $currentTime = time();
        $expiredTime = $currentTime - (24 * 60 * 60); // Expired

        // Mock database that includes all entries when show_expired=yes
        $this->setMock('db', new class($pastTimestamp, $currentTime, $expiredTime) extends FakeDb {
            private $pastTimestamp;
            private $currentTime;
            private $expiredTime;

            public function __construct($pastTimestamp, $currentTime, $expiredTime) {
                $this->pastTimestamp = $pastTimestamp;
                $this->currentTime = $currentTime;
                $this->expiredTime = $expiredTime;
            }

            public function query($sql) {
                // Return all entries when no expiration_date filtering
                return new eeDbResultMock([
                    ['year' => 2024, 'month' => 1, 'entry_date' => $this->currentTime, 'expiration_date' => 0],
                    ['year' => 2024, 'month' => 2, 'entry_date' => $this->pastTimestamp, 'expiration_date' => $this->expiredTime],
                ]);
            }
        });

        ee()->TMPL->setMap(['show_expired' => 'yes']);
        ee()->TMPL->tagdata = '{month_num}';
        ee()->TMPL->var_single = [
            'month_num' => '{month_num}'
        ];
        ee()->session->set_userdata('timezone', 'UTC');
        ee()->localize->now = $currentTime;
        $out = $this->channel->month_links();
        $this->assertStringContainsString('01', $out);
        $this->assertStringContainsString('02', $out);
    }

    public function testParsesTemplateVariables()
    {
        $this->setDbRows([
            ['year' => 2024, 'month' => 1],
        ]);
        ee()->TMPL->tagdata = '{year}-{month_num}-{month}-{month_short}';
        ee()->TMPL->var_single = [
            'year' => '{year}',
            'month_num' => '{month_num}',
            'month' => '{month}',
            'month_short' => '{month_short}'
        ];
        ee()->session->set_userdata('timezone', 'UTC');

        // Mock localize to return proper month name array
        $this->setMock('localize', new class {
            public $now = 0;
            public function __construct() { $this->now = time(); }
            public function localize_month($month) {
                $months = [
                    '01' => ['Jan', 'January'],
                    '02' => ['Feb', 'February'],
                    '03' => ['Mar', 'March'],
                    '04' => ['Apr', 'April'],
                    '05' => ['May', 'May'],
                    '06' => ['Jun', 'June'],
                    '07' => ['Jul', 'July'],
                    '08' => ['Aug', 'August'],
                    '09' => ['Sep', 'September'],
                    '10' => ['Oct', 'October'],
                    '11' => ['Nov', 'November'],
                    '12' => ['Dec', 'December']
                ];
                return $months[$month] ?? [$month, $month];
            }
        });

        // Mock lang to return the input as-is (month names are already localized)
        $this->setMock('lang', new class {
            public function line($key) {
                // For month names, just return the key as-is since they're already localized
                return $key;
            }
        });

        $out = $this->channel->month_links();
        $this->assertStringContainsString('2024-01', $out);
        $this->assertStringContainsString('January', $out);
        $this->assertStringContainsString('Jan', $out);
    }

    public function testHandlesYearHeadings()
    {
        $this->setDbRows([
            ['year' => 2023, 'month' => 12],
            ['year' => 2024, 'month' => 1],
            ['year' => 2024, 'month' => 2],
        ]);

        // Set up year heading variables
        ee()->TMPL->tagdata = '{if year_heading}<h2>{year}</h2>{/if}{month_num}';
        ee()->TMPL->var_pair = ['year_heading' => ['year_heading']];
        ee()->TMPL->var_single = [
            'year' => '{year}',
            'month_num' => '{month_num}'
        ];
        ee()->session->set_userdata('timezone', 'UTC');

        // Mock localize to return proper month name array
        $this->setMock('localize', new class {
            public $now = 0;
            public function __construct() { $this->now = time(); }
            public function localize_month($month) {
                $months = [
                    '01' => ['Jan', 'January'],
                    '02' => ['Feb', 'February'],
                    '03' => ['Mar', 'March'],
                    '04' => ['Apr', 'April'],
                    '05' => ['May', 'May'],
                    '06' => ['Jun', 'June'],
                    '07' => ['Jul', 'July'],
                    '08' => ['Aug', 'August'],
                    '09' => ['Sep', 'September'],
                    '10' => ['Oct', 'October'],
                    '11' => ['Nov', 'November'],
                    '12' => ['Dec', 'December']
                ];
                return $months[$month] ?? [$month, $month];
            }
        });

        $out = $this->channel->month_links();
        // Should have year headings for different years
        $this->assertStringContainsString('2023', $out);
        $this->assertStringContainsString('2024', $out);
        $this->assertStringContainsString('12', $out);
        $this->assertStringContainsString('01', $out);
        $this->assertStringContainsString('02', $out);
    }

    public function testRespectsYearLimit()
    {
        $this->setDbRows([
            ['year' => 2020, 'month' => 1],
            ['year' => 2021, 'month' => 1],
            ['year' => 2022, 'month' => 1],
            ['year' => 2023, 'month' => 1],
            ['year' => 2024, 'month' => 1],
        ]);
        ee()->TMPL->setMap(['year_limit' => '3']);
        ee()->TMPL->tagdata = '{if year_heading}<h2>{year}</h2>{/if}{month_num}';
        ee()->TMPL->var_pair = ['year_heading' => ['year_heading']];
        ee()->TMPL->var_single = [
            'year' => '{year}',
            'month_num' => '{month_num}'
        ];
        ee()->session->set_userdata('timezone', 'UTC');

        // Mock localize to return proper month name array
        $this->setMock('localize', new class {
            public $now = 0;
            public function __construct() { $this->now = time(); }
            public function localize_month($month) {
                $months = [
                    '01' => ['Jan', 'January'],
                    '02' => ['Feb', 'February'],
                    '03' => ['Mar', 'March'],
                    '04' => ['Apr', 'April'],
                    '05' => ['May', 'May'],
                    '06' => ['Jun', 'June'],
                    '07' => ['Jul', 'July'],
                    '08' => ['Aug', 'August'],
                    '09' => ['Sep', 'September'],
                    '10' => ['Oct', 'October'],
                    '11' => ['Nov', 'November'],
                    '12' => ['Dec', 'December']
                ];
                return $months[$month] ?? [$month, $month];
            }
        });

        $out = $this->channel->month_links();
        // Year limit shows the first N years (oldest to newest), not the most recent
        $this->assertStringContainsString('2020', $out);
        $this->assertStringContainsString('2021', $out);
        $this->assertStringContainsString('2022', $out);
        $this->assertStringNotContainsString('2023', $out);
        $this->assertStringNotContainsString('2024', $out);
    }

    public function testHandlesMultipleChannels()
    {
        $this->setDbRows([
            ['year' => 2024, 'month' => 1, 'channel_id' => 1],
            ['year' => 2024, 'month' => 2, 'channel_id' => 2],
        ]);

        // Mock channel query for multiple channels
        $this->setMock('db', new class extends FakeDb {
            private $queryCount = 0;
            public function query($sql) {
                $this->queryCount++;
                if ($this->queryCount === 1) {
                    // First query is for channel lookup
                    return new eeDbResultMock([
                        ['channel_id' => 1],
                        ['channel_id' => 2]
                    ]);
                }
                // Second query is for month links
                return new eeDbResultMock([
                    ['year' => 2024, 'month' => 1],
                    ['year' => 2024, 'month' => 2],
                ]);
            }
        });

        ee()->TMPL->setMap(['channel' => 'news|blog']);
        ee()->TMPL->tagdata = '{month_num}';
        ee()->TMPL->var_single = [
            'month_num' => '{month_num}'
        ];
        ee()->session->set_userdata('timezone', 'UTC');
        $out = $this->channel->month_links();
        $this->assertStringContainsString('01', $out);
        $this->assertStringContainsString('02', $out);
    }

    public function testHandlesCustomStatusValues()
    {
        $this->setDbRows([
            ['year' => 2024, 'month' => 1, 'status' => 'draft'],
            ['year' => 2024, 'month' => 2, 'status' => 'published'],
        ]);
        ee()->TMPL->setMap(['status' => 'draft|published']);
        ee()->TMPL->tagdata = '{month_num}';
        ee()->TMPL->var_single = [
            'month_num' => '{month_num}'
        ];
        ee()->session->set_userdata('timezone', 'UTC');
        $out = $this->channel->month_links();
        $this->assertStringContainsString('01', $out);
        $this->assertStringContainsString('02', $out);
    }

    public function testHandlesPathVariables()
    {
        $this->setDbRows([
            ['year' => 2024, 'month' => 1],
        ]);

        // Set up template data properly
        ee()->TMPL->tagdata = '{path:archives}';
        ee()->TMPL->var_single = [
            'path:archives' => '{path:archives}'
        ];
        ee()->TMPL->site_ids = [1];
        ee()->session->set_userdata('timezone', 'UTC');

        $out = $this->channel->month_links();

        // Should contain URL with archives path and date
        $this->assertStringContainsString('https://example.com/archives/2024/01', $out);
    }
}
