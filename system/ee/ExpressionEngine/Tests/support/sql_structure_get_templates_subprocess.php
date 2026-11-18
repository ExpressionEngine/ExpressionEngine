<?php

require __DIR__ . '/../bootstrap.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

class SqlStructureGetTemplatesSubprocessFixture extends Sql_structure
{
    public $settingsFixture = [];

    /**
     * Avoid constructor side effects in isolated coverage runs.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Return the configured settings fixture for the current scenario.
     *
     * @return array
     */
    public function get_settings(): array
    {
        return $this->settingsFixture;
    }
}

$target = realpath(PATH_ADDONS . 'structure/sql.structure.php');
$method = new ReflectionMethod('Sql_structure', 'get_templates');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');
$captured = (object) [
    'queries' => [],
    'query_rows' => [],
];

ee()->resetMocks();
ee()->setMock('db', new class($captured) {
    private $captured;
    private $responses;

    /**
     * Store capture state and queue scenario rows.
     *
     * @param object $captured
     * @return void
     */
    public function __construct(object $captured)
    {
        $this->captured = $captured;
        $this->responses = [
            [
                ['group_name' => 'blog', 'template_id' => 7, 'template_name' => '.draft'],
                ['group_name' => 'blog', 'template_id' => 8, 'template_name' => 'index'],
            ],
            [
                ['group_name' => 'blog', 'template_id' => 7, 'template_name' => '.draft'],
                ['group_name' => 'blog', 'template_id' => 8, 'template_name' => 'index'],
            ],
            [
                ['group_name' => 'pages', 'template_id' => 2, 'template_name' => '.hidden'],
                ['group_name' => 'pages', 'template_id' => 3, 'template_name' => 'index'],
            ],
            [
                ['group_name' => 'pages', 'template_id' => 2, 'template_name' => '_hidden'],
                ['group_name' => 'pages', 'template_id' => 3, 'template_name' => '.visible-now'],
                ['group_name' => 'pages', 'template_id' => 4, 'template_name' => 'index'],
            ],
            [],
        ];
    }

    /**
     * Return the next queued template rows for the method under test.
     *
     * @param string $sql
     * @return object
     */
    public function query($sql): object
    {
        $rows = array_shift($this->responses) ?? [];
        $this->captured->queries[] = $sql;
        $this->captured->query_rows[] = $rows;

        return new class($rows) {
            private $rows;

            /**
             * Store the fake result rows.
             *
             * @param array $rows
             * @return void
             */
            public function __construct(array $rows)
            {
                $this->rows = $rows;
            }

            /**
             * Return the configured template rows.
             *
             * @return array
             */
            public function result_array(): array
            {
                return $this->rows;
            }
        };
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

$configCallCounts = [];

$sql = new SqlStructureGetTemplatesSubprocessFixture();
$sql->site_id = 5;
$sql->cache = [];
$sql->settingsFixture = [];
ee()->setMock('config', new class {
    public $calls = 0;

    /**
     * Record any unexpected config lookups.
     *
     * @param string $key
     * @return bool
     */
    public function item($key): bool
    {
        $this->calls++;

        return false;
    }
});
$missingSettingResult = $sql->get_templates();
$configCallCounts[] = ee()->config->calls;

$sql->settingsFixture = ['hide_hidden_templates' => 'n'];
ee()->setMock('config', new class($configCallCounts) {
    public $calls = 0;

    /**
     * Seed the call counter for the no-filter path.
     *
     * @param array $configCallCounts
     * @return void
     */
    public function __construct(array $configCallCounts)
    {
    }

    /**
     * Record any unexpected config lookups.
     *
     * @param string $key
     * @return string
     */
    public function item($key): string
    {
        $this->calls++;

        return '_';
    }
});
$unfilteredResult = $sql->get_templates();
$configCallCounts[] = ee()->config->calls;

$sql->settingsFixture = ['hide_hidden_templates' => 'y'];
ee()->setMock('config', new class {
    public $calls = 0;

    /**
     * Return a falsey indicator for the default-dot branch.
     *
     * @param string $key
     * @return bool
     */
    public function item($key): bool
    {
        $this->calls++;

        return false;
    }
});
$defaultIndicatorResult = $sql->get_templates();
$configCallCounts[] = ee()->config->calls;

ee()->setMock('config', new class {
    public $calls = 0;

    /**
     * Return a custom underscore indicator.
     *
     * @param string $key
     * @return string
     */
    public function item($key): string
    {
        $this->calls++;

        return '_';
    }
});
$customIndicatorResult = $sql->get_templates();
$configCallCounts[] = ee()->config->calls;

ee()->setMock('config', new class {
    public $calls = 0;

    /**
     * Return a falsey indicator while the query payload is empty.
     *
     * @param string $key
     * @return bool
     */
    public function item($key): bool
    {
        $this->calls++;

        return false;
    }
});
$emptyResult = $sql->get_templates();
$configCallCounts[] = ee()->config->calls;

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['Sql_structure->get_templates'] ?? ['branches' => []];
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
    'queries' => $captured->queries,
    'query_rows' => $captured->query_rows,
    'missing_setting_result' => $missingSettingResult,
    'unfiltered_result' => $unfilteredResult,
    'default_indicator_result' => $defaultIndicatorResult,
    'custom_indicator_result' => $customIndicatorResult,
    'empty_result' => $emptyResult,
    'config_call_counts' => $configCallCounts,
    'xdebug_available' => $xdebugAvailable,
    'lines' => array_intersect_key($fileCoverage['lines'] ?? [], array_flip(array_keys($coveredLines))),
    'branches' => $functionCoverage['branches'] ?? [],
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
