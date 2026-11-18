<?php

require __DIR__ . '/../bootstrap.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

class SqlStructureSetListingDataSubprocessFixture extends Sql_structure
{
    public $captured;
    public $sitePagesFixture = [];
    public $settingsFixture = ['add_trailing_slash' => 'y'];

    /**
     * Store capture state without running constructor side effects.
     *
     * @param object $captured
     * @return void
     */
    public function __construct(object $captured)
    {
        $this->captured = $captured;
    }

    /**
     * Return the configured site pages fixture and track arguments.
     *
     * @param bool $cache_bust
     * @param bool $override_slash
     * @return array
     */
    public function get_site_pages($cache_bust = false, $override_slash = false)
    {
        $this->captured->getSitePagesCalls[] = [$cache_bust, $override_slash];

        return $this->sitePagesFixture;
    }

    /**
     * Return the configured Structure settings fixture.
     *
     * @return array
     */
    public function get_settings()
    {
        return $this->settingsFixture;
    }

    /**
     * Capture site_pages writes triggered by the target method.
     *
     * @param mixed $site_id
     * @param array $site_pages
     * @return void
     */
    public function set_site_pages($site_id, $site_pages)
    {
        $this->captured->setSitePagesCalls[] = [
            'site_id' => $site_id,
            'site_pages' => $site_pages,
        ];
    }

    /**
     * Track root-node refreshes.
     *
     * @return void
     */
    public function update_root_node()
    {
        $this->captured->updateRootNodeCalls++;
    }
}

$target = realpath(PATH_ADDONS . 'structure/sql.structure.php');
$method = new ReflectionMethod('Sql_structure', 'set_listing_data');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

ee()->resetMocks();
ee()->setMock('config', new FakeConfig());
ee()->config->items = [
    'site_id' => 4,
];

$captured = (object) [
    'queries' => [],
    'getWhereCalls' => 0,
    'updateTable' => null,
    'updateData' => null,
    'updateWhere' => null,
    'insertTable' => null,
    'insertData' => null,
];

ee()->setMock('db', new class($captured) extends FakeDb {
    private $captured;
    public $listingExists = true;

    /**
     * Store subprocess capture state.
     *
     * @param object $captured
     * @return void
     */
    public function __construct(object $captured)
    {
        $this->captured = $captured;
    }

    /**
     * Return one configured structure_listings lookup result.
     *
     * @param string $table
     * @param array|null $where
     * @param int|null $limit
     * @param int|null $offset
     * @return eeDbResultMock
     */
    public function get_where($table, $where = null, $limit = null, $offset = null)
    {
        $this->captured->getWhereCalls++;

        if ($table === 'structure_listings' && $this->listingExists) {
            return new eeDbResultMock([['entry_id' => $where['entry_id']]]);
        }

        return new eeDbResultMock([]);
    }

    /**
     * Capture the update branch payload.
     *
     * @param string $table
     * @param array $data
     * @param string $where
     * @return string
     */
    public function update_string($table, $data, $where)
    {
        $this->captured->updateTable = $table;
        $this->captured->updateData = $data;
        $this->captured->updateWhere = $where;

        return 'UPDATE_SQL';
    }

    /**
     * Capture the insert branch payload.
     *
     * @param string $table
     * @param array $data
     * @return string
     */
    public function insert_string($table, $data)
    {
        $this->captured->insertTable = $table;
        $this->captured->insertData = $data;

        return 'INSERT_SQL';
    }

    /**
     * Record the final SQL statement executed by the target method.
     *
     * @param string $sql
     * @return eeDbResultMock
     */
    public function query($sql)
    {
        $this->captured->queries[] = $sql;

        return new eeDbResultMock([]);
    }
});

$fetchedCapture = (object) [
    'getSitePagesCalls' => [],
    'setSitePagesCalls' => [],
    'updateRootNodeCalls' => 0,
];
$fetchedSql = new SqlStructureSetListingDataSubprocessFixture($fetchedCapture);
$fetchedSql->sitePagesFixture = [
    'url' => '/',
    'uris' => [10 => '/existing-parent/'],
    'templates' => [10 => 8],
];
$fetchedSql->site_id = 4;
$fetchedSql->cache = [];

$providedCapture = (object) [
    'getSitePagesCalls' => [],
    'setSitePagesCalls' => [],
    'updateRootNodeCalls' => 0,
];
$providedSql = new SqlStructureSetListingDataSubprocessFixture($providedCapture);
$providedSql->sitePagesFixture = [
    'url' => '/',
    'uris' => [999 => '/unused/'],
    'templates' => [999 => 2],
];
$providedSql->site_id = 4;
$providedSql->cache = [];

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

ee()->db->listingExists = true;
$fetchedSql->set_listing_data([
    'site_id' => 4,
    'entry_id' => 55,
    'channel_id' => 7,
    'parent_id' => 10,
    'parent_uri' => '/parent/',
    'uri' => '/child/',
    'template_id' => 22,
    'listing_cid' => 3,
    'hidden' => 'n',
]);

ee()->db->listingExists = false;
$providedSql->set_listing_data([
    'site_id' => 4,
    'entry_id' => 99,
    'channel_id' => 7,
    'parent_id' => 11,
    'parent_uri' => '/provided-parent/',
    'uri' => '/branch/',
    'template_id' => 15,
    'listing_cid' => 6,
    'hidden' => 'y',
], [
    'url' => '/',
    'uris' => [11 => '/provided-parent/'],
    'templates' => [11 => 9],
]);

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['Sql_structure->set_listing_data'] ?? ['branches' => [], 'paths' => []];
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
    : 100.0;

file_put_contents($outputFile, json_encode([
    'real_module_path' => $target,
    'xdebug_available' => $xdebugAvailable,
    'fetched_get_site_pages_calls' => $fetchedCapture->getSitePagesCalls,
    'provided_get_site_pages_calls' => $providedCapture->getSitePagesCalls,
    'fetched_site_pages' => $fetchedCapture->setSitePagesCalls[0]['site_pages'],
    'provided_site_pages' => $providedCapture->setSitePagesCalls[0]['site_pages'],
    'updated_row' => $captured->updateData,
    'updated_where' => $captured->updateWhere,
    'inserted_row' => $captured->insertData,
    'queries' => $captured->queries,
    'line_percentage' => $linePercentage,
    'branch_percentage' => $branchPercentage,
    'covered_lines' => $coveredLines,
    'covered_branches' => $coveredBranches,
    'uncovered_lines' => array_values(array_keys(array_filter($coveredLines, function ($covered) {
        return ! $covered;
    }))),
    'uncovered_branches' => array_values(array_keys(array_filter($coveredBranches, function ($covered) {
        return ! $covered;
    }))),
], JSON_PRETTY_PRINT));
