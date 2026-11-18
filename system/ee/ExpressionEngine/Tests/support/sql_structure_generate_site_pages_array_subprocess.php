<?php

require __DIR__ . '/../bootstrap.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

$mode = 'aggregate';
$outputFile = null;

foreach (array_slice($argv, 1) as $arg) {
    if (strpos($arg, '--mode=') === 0) {
        $mode = substr($arg, 7);
        continue;
    }

    if ($outputFile === null) {
        $outputFile = $arg;
    }
}

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

class SqlStructureGenerateSitePagesArraySubprocessFixture extends Sql_structure
{
    public $sitePagesFixture = [];
    public $retrievedTitles = [];

    /**
     * Avoid constructor side effects in isolated coverage runs.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Return the configured site_pages payload for the current scenario.
     *
     * @param bool $cache_bust
     * @param bool $override_slash
     * @return array
     */
    public function get_site_pages($cache_bust = false, $override_slash = false)
    {
        return $this->sitePagesFixture;
    }

    /**
     * Return the configured parent title lookup for generated parent URIs.
     *
     * @param int|string $entry_id
     * @return string
     */
    public function retrieve_structure_url_title($entry_id)
    {
        return $this->retrievedTitles[$entry_id] ?? '';
    }
}

/**
 * Build a lightweight CI-style DB result fixture.
 *
 * @param array $rows
 * @param int|null $numRows
 * @return object
 */
function sqlStructureGenerateSitePagesArrayResult(array $rows, ?int $numRows = null)
{
    return new class($rows, $numRows) {
        private $rows;
        public $num_rows;

        /**
         * Store the configured rows and row count.
         *
         * @param array $rows
         * @param int|null $numRows
         * @return void
         */
        public function __construct(array $rows, ?int $numRows)
        {
            $this->rows = $rows;
            $this->num_rows = $numRows ?? count($rows);
        }

        /**
         * Return the configured rows as arrays.
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
 * Build a fluent DB mock for generate_site_pages_array() scenarios.
 *
 * @param array $structureRows
 * @param array $listingRowsByChannelId
 * @return object
 */
function sqlStructureGenerateSitePagesArrayDb(array $structureRows, array $listingRowsByChannelId)
{
    return new class($structureRows, $listingRowsByChannelId) {
        private $structureRows;
        private $listingRowsByChannelId;

        /**
         * Store the fake query payloads for the current scenario.
         *
         * @param array $structureRows
         * @param array $listingRowsByChannelId
         * @return void
         */
        public function __construct(array $structureRows, array $listingRowsByChannelId)
        {
            $this->structureRows = $structureRows;
            $this->listingRowsByChannelId = $listingRowsByChannelId;
        }

        /**
         * Keep the CI DB fluent API intact for select calls.
         *
         * @param string $fields
         * @return object
         */
        public function select($fields = '*')
        {
            return $this;
        }

        /**
         * Keep the CI DB fluent API intact for from calls.
         *
         * @param string $table
         * @return object
         */
        public function from($table)
        {
            return $this;
        }

        /**
         * Keep the CI DB fluent API intact for where calls.
         *
         * @param array|string $field
         * @param mixed|null $value
         * @return object
         */
        public function where($field, $value = null)
        {
            return $this;
        }

        /**
         * Keep the CI DB fluent API intact for join calls.
         *
         * @param string $table
         * @param string $condition
         * @param string $type
         * @return object
         */
        public function join($table, $condition, $type = '')
        {
            return $this;
        }

        /**
         * Return the configured structure rows.
         *
         * @return object
         */
        public function get()
        {
            return sqlStructureGenerateSitePagesArrayResult($this->structureRows);
        }

        /**
         * Return the configured listing rows for the requested channel.
         *
         * @param string $table
         * @param array|null $where
         * @param int|null $limit
         * @param int|null $offset
         * @return object
         */
        public function get_where($table, $where = null, $limit = null, $offset = null)
        {
            $channelId = $where['channel_id'] ?? null;
            $rows = $this->listingRowsByChannelId[$channelId] ?? [];

            return sqlStructureGenerateSitePagesArrayResult($rows);
        }
    };
}

/**
 * Build a fixture instance without constructor side effects.
 *
 * @param array $sitePages
 * @param array $retrievedTitles
 * @return SqlStructureGenerateSitePagesArraySubprocessFixture
 */
function sqlStructureGenerateSitePagesArraySql(array $sitePages, array $retrievedTitles = []): SqlStructureGenerateSitePagesArraySubprocessFixture
{
    $sql = new SqlStructureGenerateSitePagesArraySubprocessFixture();
    $sql->site_id = 1;
    $sql->cache = [];
    $sql->sitePagesFixture = $sitePages;
    $sql->retrievedTitles = $retrievedTitles;

    return $sql;
}

/**
 * Build the executable line coverage map for the target method.
 *
 * @param ReflectionMethod $method
 * @param string $target
 * @param array $lineCoverage
 * @return array
 */
function sqlStructureGenerateSitePagesArrayCoveredLines(ReflectionMethod $method, string $target, array $lineCoverage): array
{
    $coveredLines = [];
    $sourceLines = file($target, FILE_IGNORE_NEW_LINES) ?: [];

    foreach (range($method->getStartLine(), $method->getEndLine()) as $line) {
        if (! array_key_exists($line, $lineCoverage)) {
            continue;
        }

        $sourceLine = trim($sourceLines[$line - 1] ?? '');

        if ($sourceLine === '{' || $sourceLine === '}') {
            continue;
        }

        $coveredLines[$line] = (($lineCoverage[$line] ?? 0) > 0);
    }

    return $coveredLines;
}

/**
 * Build the branch-edge coverage map for the target method.
 *
 * @param array $branches
 * @return array
 */
function sqlStructureGenerateSitePagesArrayCoveredBranches(array $branches): array
{
    $coveredBranches = [];

    foreach ($branches as $branchId => $branch) {
        if (count($branch['out_hit'] ?? []) <= 1) {
            continue;
        }

        foreach (($branch['out_hit'] ?? []) as $edgeIndex => $hitCount) {
            $coveredBranches[$branchId . ':' . $edgeIndex] = ($hitCount > 0);
        }
    }

    return $coveredBranches;
}

/**
 * Run the primary non-debug scenario.
 *
 * @return array
 */
function sqlStructureGenerateSitePagesArrayRunPrimaryScenario(): array
{
    $_GET = [];

    ee()->resetMocks();
    ee()->setMock('db', sqlStructureGenerateSitePagesArrayDb([
        ['entry_id' => 1, 'structure_url_title' => 'Parent', 'parent_id' => 0, 'channel_id' => 2, 'listing_cid' => 9, 'template_id' => 10],
        ['entry_id' => 2, 'structure_url_title' => 'Child', 'parent_id' => 1, 'channel_id' => 2, 'listing_cid' => 0, 'template_id' => 11],
        ['entry_id' => 3, 'structure_url_title' => 'Leaf', 'parent_id' => 99, 'channel_id' => 2, 'listing_cid' => 0, 'template_id' => 12],
        ['entry_id' => 4, 'structure_url_title' => '', 'parent_id' => 0, 'channel_id' => 2, 'listing_cid' => 0, 'template_id' => 13],
        ['entry_id' => 5, 'structure_url_title' => 'NoListings', 'parent_id' => 0, 'channel_id' => 2, 'listing_cid' => 7, 'template_id' => 14],
    ], [
        9 => [
            ['entry_id' => 20, 'url_title' => 'Listing'],
        ],
        7 => [],
    ]));
    return sqlStructureGenerateSitePagesArraySql([
        'url' => 'https://example.test/',
        'uris' => [999 => '/stale/'],
        'templates' => [1 => 10],
    ], [
        99 => 'generated-parent',
    ])->generate_site_pages_array();
}

/**
 * Run the self-parent and blank-listing scenario.
 *
 * @return array
 */
function sqlStructureGenerateSitePagesArrayRunSelfParentScenario(): array
{
    $_GET = [];

    ee()->resetMocks();
    ee()->setMock('db', sqlStructureGenerateSitePagesArrayDb([
        ['entry_id' => 30, 'structure_url_title' => 'SelfRoot', 'parent_id' => 30, 'channel_id' => 2, 'listing_cid' => 0, 'template_id' => 14],
        ['entry_id' => 40, 'structure_url_title' => '', 'parent_id' => 0, 'channel_id' => 2, 'listing_cid' => 8, 'template_id' => 15],
    ], [
        8 => [
            ['entry_id' => 41, 'url_title' => ''],
        ],
    ]));
    return sqlStructureGenerateSitePagesArraySql([
        'url' => 'https://example.test/',
        'uris' => [555 => '/stale/'],
        'templates' => [30 => 14, 41 => 99],
    ])->generate_site_pages_array();
}

/**
 * Run the no-entries scenario.
 *
 * @return array
 */
function sqlStructureGenerateSitePagesArrayRunNoEntriesScenario(): array
{
    $_GET = [];

    ee()->resetMocks();
    ee()->setMock('db', sqlStructureGenerateSitePagesArrayDb([], []));
    return sqlStructureGenerateSitePagesArraySql([
        'url' => 'https://example.test/',
        'uris' => [99 => '/stale/'],
        'templates' => [99 => 7],
    ])->generate_site_pages_array();
}

/**
 * Run the debug scenario that terminates through the method's real die path.
 *
 * @return void
 */
function sqlStructureGenerateSitePagesArrayRunDieScenario()
{
    $_GET = ['debug' => '1'];

    ee()->resetMocks();
    ee()->setMock('db', sqlStructureGenerateSitePagesArrayDb([
        ['entry_id' => 1, 'structure_url_title' => 'Parent', 'parent_id' => 0, 'channel_id' => 2, 'listing_cid' => 0, 'template_id' => 10],
        ['entry_id' => 2, 'structure_url_title' => 'Child', 'parent_id' => 1, 'channel_id' => 2, 'listing_cid' => 0, 'template_id' => 11],
        ['entry_id' => 3, 'structure_url_title' => 'Leaf', 'parent_id' => 99, 'channel_id' => 2, 'listing_cid' => 0, 'template_id' => 12],
    ], []));

    sqlStructureGenerateSitePagesArraySql([
        'url' => 'https://example.test/',
        'uris' => [],
        'templates' => [],
    ], [
        99 => 'generated-parent',
    ])->generate_site_pages_array();
}

$target = realpath(PATH_ADDONS . 'structure/sql.structure.php');
$method = new ReflectionMethod('Sql_structure', 'generate_site_pages_array');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

if ($mode === 'aggregate') {
    $primaryFile = sys_get_temp_dir() . '/sql-structure-generate-site-pages-array-primary-' . uniqid('', true) . '.json';
    $selfParentFile = sys_get_temp_dir() . '/sql-structure-generate-site-pages-array-self-parent-' . uniqid('', true) . '.json';
    $noEntriesFile = sys_get_temp_dir() . '/sql-structure-generate-site-pages-array-no-entries-' . uniqid('', true) . '.json';
    $dieFile = sys_get_temp_dir() . '/sql-structure-generate-site-pages-array-die-' . uniqid('', true) . '.json';
    $commands = [
        'primary' => escapeshellarg(PHP_BINARY) . ' -d xdebug.mode=coverage ' . escapeshellarg(__FILE__) . ' --mode=primary ' . escapeshellarg($primaryFile) . ' 2>&1',
        'self_parent_and_blank_listing' => escapeshellarg(PHP_BINARY) . ' -d xdebug.mode=coverage ' . escapeshellarg(__FILE__) . ' --mode=self_parent_and_blank_listing ' . escapeshellarg($selfParentFile) . ' 2>&1',
        'no_entries' => escapeshellarg(PHP_BINARY) . ' -d xdebug.mode=coverage ' . escapeshellarg(__FILE__) . ' --mode=no_entries ' . escapeshellarg($noEntriesFile) . ' 2>&1',
        'die' => escapeshellarg(PHP_BINARY) . ' -d xdebug.mode=coverage ' . escapeshellarg(__FILE__) . ' --mode=die ' . escapeshellarg($dieFile) . ' 2>&1',
    ];
    $commandResults = [];

    foreach ($commands as $commandMode => $command) {
        $commandOutput = [];
        $exitCode = 0;
        exec($command, $commandOutput, $exitCode);
        $commandResults[$commandMode] = [
            'command' => $command,
            'output' => $commandOutput,
            'exit_code' => $exitCode,
        ];
    }

    $primaryResult = json_decode(file_get_contents($primaryFile), true);
    $selfParentResult = json_decode(file_get_contents($selfParentFile), true);
    $noEntriesResult = json_decode(file_get_contents($noEntriesFile), true);
    $dieResult = json_decode(file_get_contents($dieFile), true);

    @unlink($primaryFile);
    @unlink($selfParentFile);
    @unlink($noEntriesFile);
    @unlink($dieFile);

    $coveredLines = [];
    $lineKeys = array_unique(array_merge(
        array_keys($primaryResult['lines'] ?? []),
        array_keys($selfParentResult['lines'] ?? []),
        array_keys($noEntriesResult['lines'] ?? []),
        array_keys($dieResult['lines'] ?? [])
    ));

    foreach ($lineKeys as $line) {
        $coveredLines[$line] = ($primaryResult['lines'][$line] ?? false)
            || ($selfParentResult['lines'][$line] ?? false)
            || ($noEntriesResult['lines'][$line] ?? false)
            || ($dieResult['lines'][$line] ?? false);
    }

    $coveredBranches = [];
    $branchKeys = array_unique(array_merge(
        array_keys($primaryResult['covered_branches'] ?? []),
        array_keys($selfParentResult['covered_branches'] ?? []),
        array_keys($noEntriesResult['covered_branches'] ?? []),
        array_keys($dieResult['covered_branches'] ?? [])
    ));

    foreach ($branchKeys as $branchKey) {
        $coveredBranches[$branchKey] = ($primaryResult['covered_branches'][$branchKey] ?? false)
            || ($selfParentResult['covered_branches'][$branchKey] ?? false)
            || ($noEntriesResult['covered_branches'][$branchKey] ?? false)
            || ($dieResult['covered_branches'][$branchKey] ?? false);
    }

    $linePercentage = count($coveredLines) > 0
        ? count(array_filter($coveredLines)) / count($coveredLines) * 100
        : 0.0;
    $branchPercentage = count($coveredBranches) > 0
        ? count(array_filter($coveredBranches)) / count($coveredBranches) * 100
        : 0.0;

    file_put_contents($outputFile, json_encode([
        'real_module_path' => $target,
        'xdebug_available' => ($primaryResult['xdebug_available'] ?? false)
            || ($selfParentResult['xdebug_available'] ?? false)
            || ($noEntriesResult['xdebug_available'] ?? false)
            || ($dieResult['xdebug_available'] ?? false),
        'line_percentage' => $linePercentage,
        'branch_percentage' => $branchPercentage,
        'lines' => $coveredLines,
        'covered_branches' => $coveredBranches,
        'uncovered_lines' => array_values(array_keys(array_filter($coveredLines, function ($covered) {
            return ! $covered;
        }))),
        'uncovered_branches' => array_values(array_keys(array_filter($coveredBranches, function ($covered) {
            return ! $covered;
        }))),
        'return_result' => [
            'primary' => $primaryResult['scenario_result'] ?? [],
            'self_parent_and_blank_listing' => $selfParentResult['scenario_result'] ?? [],
            'no_entries' => $noEntriesResult['scenario_result'] ?? [],
        ],
        'die_result' => [
            'output' => $dieResult['output'] ?? '',
        ],
        'commands' => $commandResults,
    ], JSON_PRETTY_PRINT));

    exit(0);
}

$scenarioResult = [];

register_shutdown_function(function () use (&$scenarioResult, $outputFile, $target, $method, $xdebugAvailable) {
    $coverage = [];

    if ($xdebugAvailable) {
        $coverage = xdebug_get_code_coverage();
    }

    $fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
    $functionCoverage = $fileCoverage['functions']['Sql_structure->generate_site_pages_array'] ?? ['branches' => [], 'paths' => []];
    $bufferedOutput = '';

    if (ob_get_level() > 0) {
        $bufferedOutput = ob_get_contents();
        ob_end_clean();
    }

    file_put_contents($outputFile, json_encode([
        'real_module_path' => $target,
        'xdebug_available' => $xdebugAvailable,
        'output' => $bufferedOutput,
        'lines' => sqlStructureGenerateSitePagesArrayCoveredLines($method, $target, $fileCoverage['lines'] ?? []),
        'covered_branches' => sqlStructureGenerateSitePagesArrayCoveredBranches($functionCoverage['branches'] ?? []),
        'scenario_result' => $scenarioResult,
    ], JSON_PRETTY_PRINT));
});

ob_start();

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

if ($mode === 'primary') {
    $scenarioResult = sqlStructureGenerateSitePagesArrayRunPrimaryScenario();
    exit(0);
}

if ($mode === 'self_parent_and_blank_listing') {
    $scenarioResult = sqlStructureGenerateSitePagesArrayRunSelfParentScenario();
    exit(0);
}

if ($mode === 'no_entries') {
    $scenarioResult = sqlStructureGenerateSitePagesArrayRunNoEntriesScenario();
    exit(0);
}

if ($mode === 'die') {
    sqlStructureGenerateSitePagesArrayRunDieScenario();
    exit(0);
}

ob_end_clean();
fwrite(STDERR, "Unsupported mode: {$mode}\n");
exit(1);
