<?php

require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

class SqlStructureRestoreSitePagesFromStructureSubprocessFixture extends Sql_structure
{
    public $sitePagesFixture = [];
    public $structureChannelsFixture = [];

    /**
     * Avoid constructor side effects in isolated coverage runs.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Return the configured site_pages fixture.
     *
     * @param bool $cache_bust
     * @param bool $override_slash
     * @return array
     */
    public function get_site_pages($cache_bust = false, $override_slash = false): array
    {
        return $this->sitePagesFixture;
    }

    /**
     * Return the configured Structure channel defaults.
     *
     * @param string $type
     * @param string $channel_id
     * @param string $order
     * @param bool $selector
     * @return array
     */
    public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false): array
    {
        return $this->structureChannelsFixture;
    }
}

class SqlStructureRestoreSitePagesFromStructureFunctionsMock
{
    private $siteUrl;

    /**
     * Store the site URL returned during payload generation.
     *
     * @param string $siteUrl
     * @return void
     */
    public function __construct(string $siteUrl)
    {
        $this->siteUrl = $siteUrl;
    }

    /**
     * Return the configured site URL for the serialized payload.
     *
     * @param int $includeIndex
     * @param int $includeQuery
     * @return string
     */
    public function fetch_site_index($includeIndex = 1, $includeQuery = 0): string
    {
        return $this->siteUrl;
    }
}

class SqlStructureRestoreSitePagesFromStructureSubprocessDbMock
{
    private $captured;
    private $channelIdsByEntryId;
    private $failOnQuery;

    /**
     * Store shared query capture state for the restore-site-pages subprocess.
     *
     * @param object $captured
     * @param array $channelIdsByEntryId
     * @param bool $failOnQuery
     * @return void
     */
    public function __construct(object $captured, array $channelIdsByEntryId = [], bool $failOnQuery = false)
    {
        $this->captured = $captured;
        $this->channelIdsByEntryId = $channelIdsByEntryId;
        $this->failOnQuery = $failOnQuery;
    }

    /**
     * Return the configured channel lookup result for a missing-template entry.
     *
     * @param string $sql
     * @return eeDbResultMock
     */
    public function query($sql): eeDbResultMock
    {
        $this->captured->queries[] = $sql;

        if ($this->failOnQuery) {
            throw new RuntimeException('restore_site_pages_from_structure() should not query channel titles when templates are already present.');
        }

        preg_match("/entry_id = '([^']+)'/", $sql, $matches);
        $entryId = $matches[1] ?? null;
        $channelId = null;

        if ($entryId !== null && array_key_exists($entryId, $this->channelIdsByEntryId)) {
            $channelId = $this->channelIdsByEntryId[$entryId];
        }

        if ($channelId === null) {
            return new eeDbResultMock([]);
        }

        return new eeDbResultMock([['channel_id' => $channelId]]);
    }

    /**
     * Capture the site scoping applied before the update.
     *
     * @param string $field
     * @param mixed|null $value
     * @return self
     */
    public function where($field, $value = null): self
    {
        $this->captured->where_calls[] = [$field, $value];

        return $this;
    }

    /**
     * Capture the serialized site_pages payload written to the sites table.
     *
     * @param string $table
     * @param array|null $data
     * @param mixed|null $where
     * @return bool
     */
    public function update($table, $data = null, $where = null): bool
    {
        $this->captured->updates[] = [
            'table' => $table,
            'data' => $data,
            'where' => $where,
        ];

        return true;
    }
}

/**
 * Execute one restore-site-pages scenario and return the captured side effects.
 *
 * @param array $sitePagesFixture
 * @param array $structureChannelsFixture
 * @param int $siteId
 * @param string $siteUrl
 * @param array $channelIdsByEntryId
 * @param bool $failOnQuery
 * @return array
 */
function run_restore_site_pages_from_structure_scenario(
    array $sitePagesFixture,
    array $structureChannelsFixture,
    int $siteId,
    string $siteUrl,
    array $channelIdsByEntryId = [],
    bool $failOnQuery = false
): array {
    $captured = (object) [
        'queries' => [],
        'where_calls' => [],
        'updates' => [],
    ];

    ee()->resetMocks();
    ee()->setMock('functions', new SqlStructureRestoreSitePagesFromStructureFunctionsMock($siteUrl));
    ee()->setMock('db', new SqlStructureRestoreSitePagesFromStructureSubprocessDbMock($captured, $channelIdsByEntryId, $failOnQuery));

    $sql = new SqlStructureRestoreSitePagesFromStructureSubprocessFixture();
    $sql->sitePagesFixture = $sitePagesFixture;
    $sql->structureChannelsFixture = $structureChannelsFixture;
    $sql->site_id = $siteId;
    $sql->cache = [];

    $sql->restore_site_pages_from_structure();

    return [
        'captured' => [
            'queries' => $captured->queries,
            'where_calls' => $captured->where_calls,
            'updates' => $captured->updates,
        ],
        'decoded' => unserialize(base64_decode($captured->updates[0]['data']['site_pages'])),
    ];
}

$target = realpath(PATH_ADDONS . 'structure/sql.structure.php');
$method = new ReflectionMethod('Sql_structure', 'restore_site_pages_from_structure');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

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

$missingTemplateScenario = run_restore_site_pages_from_structure_scenario(
    [
        'uris' => [10 => '/existing/', 11 => '/needs-template/'],
        'templates' => [10 => 2],
    ],
    [
        9 => ['template_id' => 7],
    ],
    3,
    'https://example.com/',
    ['11' => 9]
);
$existingTemplateScenario = run_restore_site_pages_from_structure_scenario(
    [
        'uris' => [22 => '/already-mapped/'],
        'templates' => [22 => 33],
    ],
    [
        99 => ['template_id' => 1000],
    ],
    5,
    'https://stable.example.com/',
    [],
    true
);
$emptyScenario = run_restore_site_pages_from_structure_scenario(
    [
        'uris' => [],
        'templates' => [],
    ],
    [],
    8,
    'https://empty.example.com/',
    [],
    true
);

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['Sql_structure->restore_site_pages_from_structure'] ?? ['branches' => [], 'paths' => []];
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
    'missing_template_result' => $missingTemplateScenario['decoded'],
    'missing_template_queries' => $missingTemplateScenario['captured']['queries'],
    'missing_template_where_calls' => $missingTemplateScenario['captured']['where_calls'],
    'existing_template_result' => $existingTemplateScenario['decoded'],
    'existing_template_queries' => $existingTemplateScenario['captured']['queries'],
    'existing_template_where_calls' => $existingTemplateScenario['captured']['where_calls'],
    'empty_result' => $emptyScenario['decoded'],
    'empty_queries' => $emptyScenario['captured']['queries'],
    'empty_where_calls' => $emptyScenario['captured']['where_calls'],
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
