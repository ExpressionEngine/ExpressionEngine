<?php

require __DIR__ . '/../bootstrap.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';
require_once PATH_ADDONS . 'structure/Conduit/StaticCache.php';

use ExpressionEngine\Structure\Conduit\StaticCache;

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

class SqlStructureGetListingChannelSubprocessFixture extends Sql_structure
{
    public $listingEntryIds = [];
    public $parentIds = [];
    public $parentCalls = [];

    /**
     * Avoid constructor side effects in isolated coverage runs.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Return the configured listing entry IDs for the scenario.
     *
     * @return mixed
     */
    public function get_listing_entry_ids()
    {
        return $this->listingEntryIds;
    }

    /**
     * Return the configured parent ID and capture the lookup.
     *
     * @param int $entry_id
     * @param string $default
     * @return int|false
     */
    public function get_parent_id($entry_id, $default = 'home')
    {
        $this->parentCalls[$entry_id][] = [
            'entry_id' => $entry_id,
            'default' => $default,
        ];

        return $this->parentIds[$entry_id] ?? false;
    }
}

$target = realpath(PATH_ADDONS . 'structure/sql.structure.php');
$method = new ReflectionMethod('Sql_structure', 'get_listing_channel');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');
$captured = (object) [
    'sql_calls' => [],
    'channel_where' => [],
    'updates' => [],
];

ee()->resetMocks();
StaticCache::clear();
ee()->setMock('sql_helper', new class($captured) {
    private $captured;

    /**
     * Store SQL capture state and route scenario results.
     *
     * @param object $captured
     * @return void
     */
    public function __construct(object $captured)
    {
        $this->captured = $captured;
    }

    /**
     * Return the queued row for the current entry lookup.
     *
     * @param string $sql
     * @return array|null
     */
    public function row($sql)
    {
        $this->captured->sql_calls[] = $sql;

        if (strpos($sql, 'WHERE entry_id = 17') !== false) {
            return ['listing_cid' => 0];
        }

        if (strpos($sql, 'WHERE entry_id = 20') !== false) {
            return ['listing_cid' => 8];
        }

        if (strpos($sql, 'WHERE entry_id = 5') !== false) {
            return ['listing_cid' => 8];
        }

        if (strpos($sql, 'WHERE entry_id = 44') !== false) {
            return null;
        }

        return null;
    }
});
ee()->setMock('db', new class($captured) {
    private $captured;
    private $where = [];

    /**
     * Store SQL side-effect capture state.
     *
     * @param object $captured
     * @return void
     */
    public function __construct(object $captured)
    {
        $this->captured = $captured;
    }

    /**
     * Return channel existence rows for the exercised scenarios.
     *
     * @param string $table
     * @param array|null $where
     * @param int|null $limit
     * @return object
     */
    public function get_where($table, $where = null, $limit = null): object
    {
        $this->captured->channel_where[] = $where;

        $rows = [['channel_id' => 8]];

        if ($where === ['channel_id' => 8] && count($this->captured->channel_where) > 1) {
            $rows = [];
        }

        return new class($rows) {
            private $rows;

            /**
             * Store the configured channel rows.
             *
             * @param array $rows
             * @return void
             */
            public function __construct(array $rows)
            {
                $this->rows = $rows;
            }

            /**
             * Return the configured channel rows.
             *
             * @return array
             */
            public function result_array(): array
            {
                return $this->rows;
            }
        };
    }

    /**
     * Capture the cleanup filters.
     *
     * @param string $field
     * @param mixed $value
     * @return object
     */
    public function where($field, $value = null): object
    {
        $this->where[$field] = $value;

        return $this;
    }

    /**
     * Capture the stale-channel cleanup update.
     *
     * @param string $table
     * @param array $data
     * @return bool
     */
    public function update($table, $data = []): bool
    {
        $this->captured->updates[] = [
            'table' => $table,
            'where' => $this->where,
            'data' => $data,
        ];
        $this->where = [];

        return true;
    }
});

if ($xdebugAvailable) {
    $coverageFlags = 0;

    if (defined('XDEBUG_CC_UNUSED')) {
        $coverageFlags |= XDEBUG_CC_UNUSED;
    }

    if (defined('XDEBUG_CC_DEAD_CODE')) {
        $coverageFlags |= XDEBUG_CC_DEAD_CODE;
    }

    if (defined('XDEBUG_CC_BRANCH_CHECK')) {
        $coverageFlags |= XDEBUG_CC_BRANCH_CHECK;
    }

    if ($coverageFlags > 0) {
        xdebug_start_code_coverage($coverageFlags);
    }

    if ($coverageFlags === 0) {
        xdebug_start_code_coverage();
    }
}

$sql = new SqlStructureGetListingChannelSubprocessFixture();
$sql->site_id = 6;
$sql->cache = [];

$nonNumericResult = $sql->get_listing_channel('abc');

StaticCache::clear();
$sql->listingEntryIds = false;
$nonArrayListingIdsResult = $sql->get_listing_channel(17);

StaticCache::clear();
$sql->listingEntryIds = [20 => 20];
$sql->parentIds = [20 => 0];
$falsyParentResult = $sql->get_listing_channel(20);
$cachedResult = $sql->get_listing_channel(20);

StaticCache::clear();
$sql->listingEntryIds = [30 => 30];
$sql->parentIds = [20 => 0, 30 => 5];
$staleChannelResult = $sql->get_listing_channel(30);

StaticCache::clear();
$sql->listingEntryIds = [];
$missingRowResult = $sql->get_listing_channel(44);

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['Sql_structure->get_listing_channel'] ?? ['branches' => [], 'paths' => []];
$coveredLines = [];
$sourceLines = file($target, FILE_IGNORE_NEW_LINES) ?: [];

foreach (range($method->getStartLine(), $method->getEndLine()) as $line) {
    if (! array_key_exists($line, $fileCoverage['lines'] ?? [])) {
        continue;
    }

    $sourceLine = trim($sourceLines[$line - 1] ?? '');

    if ($sourceLine === '{' || $sourceLine === '}') {
        continue;
    }

    $coveredLines[$line] = (($fileCoverage['lines'][$line] ?? 0) > 0);
}

$coveredBranches = [];
$totalBranches = 0;

foreach (($functionCoverage['branches'] ?? []) as $branchId => $branch) {
    if (count($branch['out_hit'] ?? []) <= 1) {
        continue;
    }

    foreach (($branch['out_hit'] ?? []) as $edgeIndex => $hitCount) {
        $totalBranches++;
        $coveredBranches[$branchId . ':' . $edgeIndex] = ($hitCount > 0);
    }
}

$linePercentage = count($coveredLines) > 0
    ? count(array_filter($coveredLines)) / count($coveredLines) * 100
    : 0.0;
$branchPercentage = $totalBranches > 0
    ? count(array_filter($coveredBranches)) / $totalBranches * 100
    : 0.0;

file_put_contents($outputFile, json_encode([
    'real_module_path' => $target,
    'non_numeric_result' => $nonNumericResult,
    'non_array_listing_ids_result' => $nonArrayListingIdsResult,
    'falsy_parent_result' => $falsyParentResult,
    'cached_result' => $cachedResult,
    'stale_channel_result' => $staleChannelResult,
    'missing_row_result' => $missingRowResult,
    'sql_calls' => $captured->sql_calls,
    'channel_where' => $captured->channel_where,
    'parent_calls' => $sql->parentCalls,
    'stale_channel_update' => $captured->updates[0] ?? null,
    'xdebug_available' => $xdebugAvailable,
    'lines' => $coveredLines,
    'branches' => $functionCoverage['branches'] ?? [],
    'paths' => $functionCoverage['paths'] ?? [],
    'line_percentage' => $linePercentage,
    'branch_percentage' => $branchPercentage,
    'covered_branches' => $coveredBranches,
    'uncovered_lines' => array_values(array_keys(array_filter($coveredLines, function ($covered) {
        return ! $covered;
    }))),
    'uncovered_branches' => array_values(array_keys(array_filter($coveredBranches, function ($covered) {
        return ! $covered;
    }))),
], JSON_PRETTY_PRINT));
