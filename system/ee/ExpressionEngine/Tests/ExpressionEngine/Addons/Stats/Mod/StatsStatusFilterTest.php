<?php

use PHPUnit\Framework\TestCase;

require_once PATH_ADDONS . 'stats/mod.stats.php';

class StatsStatusFilterTest extends TestCase
{
    private $db;

    protected function tearDown(): void
    {
        ee()->resetMocks();

        parent::tearDown();
    }

    public function testChannelOnlyUsesCachedChannelStats()
    {
        $stats = $this->makeStats([
            'channel' => 'catalogue',
        ], [
            [
                'total_entries' => 5,
                'total_comments' => 2,
                'last_entry_date' => 800,
                'last_comment_date' => 700,
            ],
        ]);

        $this->assertSame('5:2', $stats->return_data);
        $this->assertStringContainsString('FROM exp_channels', $this->db->queries[0]);
        $this->assertStringNotContainsString('exp_channel_titles.status', $this->db->queries[0]);
    }

    public function testStatusFiltersUseEntryStatsAndKeepClosedOutByDefault()
    {
        $stats = $this->makeStats([
            'channel' => 'catalogue',
            'status' => 'open',
        ], [
            [
                'total_entries' => 3,
                'total_comments' => 7,
                'last_entry_date' => 900,
                'last_comment_date' => 800,
            ],
        ]);

        $this->assertSame('3:7', $stats->return_data);
        $this->assertStringContainsString('FROM exp_channel_titles', $this->db->queries[0]);
        $this->assertStringContainsString("exp_channels.channel_name = 'catalogue'", $this->db->queries[0]);
        $this->assertStringContainsString("exp_channel_titles.status = 'open'", $this->db->queries[0]);
        $this->assertStringContainsString("exp_channel_titles.status != 'closed'", $this->db->queries[0]);
    }

    /**
     * Verify status-filtered stats include entries published at the current timestamp.
     *
     * @return void
     */
    public function testStatusFiltersIncludeEntriesPublishedAtCurrentTimestamp()
    {
        $this->makeStats([
            'status' => 'open',
        ], [
            [
                'total_entries' => 1,
                'total_comments' => 0,
                'last_entry_date' => 1000,
                'last_comment_date' => 0,
            ],
        ]);

        $this->assertStringContainsString('exp_channel_titles.entry_date <= 1000', $this->db->queries[0]);
    }

    public function testExplicitClosedStatusCanBeCounted()
    {
        $this->makeStats([
            'status' => 'closed',
        ], [
            [
                'total_entries' => 1,
                'total_comments' => 0,
                'last_entry_date' => 900,
                'last_comment_date' => 0,
            ],
        ]);

        $this->assertStringContainsString("exp_channel_titles.status = 'closed'", $this->db->queries[0]);
        $this->assertStringNotContainsString("exp_channel_titles.status != 'closed'", $this->db->queries[0]);
    }

    private function makeStats(array $params, array $rows)
    {
        ee()->resetMocks();

        $this->db = new StatsStatusFilterDbMock($rows);

        ee()->setMock('db', $this->db);
        ee()->setMock('stats', new StatsStatusFilterStatsMock());
        ee()->setMock('TMPL', new StatsStatusFilterTemplateMock($params));
        ee()->setMock('functions', new StatsStatusFilterFunctionsMock());
        ee()->setMock('localize', new class {
            public $now = 1000;
        });

        return new Stats();
    }
}

class StatsStatusFilterTemplateMock
{
    public $site_ids = [1];
    public $tagdata = '{total_entries}:{total_comments}';
    public $var_single = [
        'total_entries' => '',
        'total_comments' => '',
    ];
    private $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function fetch_param($key, $default = false)
    {
        return array_key_exists($key, $this->params) ? $this->params[$key] : $default;
    }

    public function swap_var_single($variable, $replacement, $source)
    {
        return str_replace('{' . $variable . '}', $replacement, $source);
    }
}

class StatsStatusFilterStatsMock
{
    public $stats_cache = [];
    private $data = [
        'current_names' => false,
        'total_comments' => 0,
        'total_entries' => 0,
    ];

    public function load_stats()
    {
    }

    public function set_statdata($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function statdata($key = null)
    {
        if (! $key) {
            return $this->data;
        }

        return $this->data[$key] ?? false;
    }
}

class StatsStatusFilterDbMock
{
    public $queries = [];
    private $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function query($sql)
    {
        $this->queries[] = $sql;

        return new StatsStatusFilterDbResultMock($this->rows);
    }
}

class StatsStatusFilterDbResultMock
{
    private $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function num_rows()
    {
        return count($this->rows);
    }

    public function result_array()
    {
        return $this->rows;
    }

    public function row_array()
    {
        return $this->rows[0] ?? [];
    }
}

class StatsStatusFilterFunctionsMock
{
    public function prep_conditionals($str, $vars = [])
    {
        return $str;
    }

    public function sql_andor_string($str, $field)
    {
        if (strncasecmp($str, 'not ', 4) == 0) {
            return "AND {$field} != '" . trim(substr($str, 4)) . "'";
        }

        if (strpos($str, '|') !== false) {
            return "AND {$field} IN ('" . str_replace('|', "','", $str) . "')";
        }

        return "AND {$field} = '{$str}'";
    }
}
