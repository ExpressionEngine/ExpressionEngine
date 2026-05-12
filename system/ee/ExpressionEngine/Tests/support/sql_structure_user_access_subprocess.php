<?php

require __DIR__ . '/../bootstrap.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

class SqlStructureUserAccessSubprocessFixture extends Sql_structure
{
    /**
     * Avoid constructor side effects in isolated coverage runs.
     *
     * @return void
     */
    public function __construct()
    {
    }
}

$target = realpath(PATH_ADDONS . 'structure/sql.structure.php');
$method = new ReflectionMethod('Sql_structure', 'user_access');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');
$captured = (object) [
    'config_calls' => [],
    'db_calls' => [],
];

ee()->resetMocks();
ee()->setMock('config', new class($captured) {
    private $captured;

    /**
     * Store shared capture state for config lookups.
     *
     * @param object $captured
     * @return void
     */
    public function __construct(object $captured)
    {
        $this->captured = $captured;
    }

    /**
     * Return the configured site ID while capturing each lookup.
     *
     * @param string $key
     * @return int
     */
    public function item($key): int
    {
        $this->captured->config_calls[] = $key;

        return 14;
    }
});
ee()->setMock('db', new class($captured) {
    private $captured;
    public $rows = 1;

    /**
     * Store shared capture state for the fluent DB mock.
     *
     * @param object $captured
     * @return void
     */
    public function __construct(object $captured)
    {
        $this->captured = $captured;
    }

    /**
     * Capture the selected field.
     *
     * @param string $field
     * @return object
     */
    public function select($field): object
    {
        $this->captured->db_calls[] = [
            'method' => 'select',
            'field' => $field,
        ];

        return $this;
    }

    /**
     * Capture the queried table.
     *
     * @param string $table
     * @return object
     */
    public function from($table): object
    {
        $this->captured->db_calls[] = [
            'method' => 'from',
            'table' => $table,
        ];

        return $this;
    }

    /**
     * Capture the admin permission lookup.
     *
     * @param string $field
     * @param string $value
     * @return object
     */
    public function where($field, $value): object
    {
        $this->captured->db_calls[] = [
            'method' => 'where',
            'field' => $field,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Capture the specific permission lookup.
     *
     * @param string $field
     * @param string $value
     * @return object
     */
    public function or_where($field, $value): object
    {
        $this->captured->db_calls[] = [
            'method' => 'or_where',
            'field' => $field,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Return the configured row count while capturing the call.
     *
     * @return int
     */
    public function num_rows(): int
    {
        $this->captured->db_calls[] = [
            'method' => 'num_rows',
            'rows' => $this->rows,
        ];

        return $this->rows;
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

$sql = new SqlStructureUserAccessSubprocessFixture();
$sql->cache = [];

ee()->setMock('session', (object) ['userdata' => ['group_id' => 1]]);
$superAdminDeleteResult = $sql->user_access('perm_delete');
$superAdminPublishResult = $sql->user_access('perm_publish');

ee()->setMock('session', (object) ['userdata' => ['group_id' => 4]]);
$settingsYesResult = $sql->user_access('perm_reorder', ['perm_reorder_4' => 'y']);
$settingsNoResult = $sql->user_access('perm_reorder', ['perm_reorder_4' => 'n']);
$settingsMissingResult = $sql->user_access('perm_reorder', ['perm_publish_4' => 'y']);

ee()->db->rows = 1;
$dbDeleteResult = $sql->user_access('perm_delete');
$dbPublishResult = $sql->user_access('perm_publish');

ee()->db->rows = 0;
$dbMissingResult = $sql->user_access('perm_publish');

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['Sql_structure->user_access'] ?? ['branches' => [], 'paths' => []];
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
    'super_admin_delete_result' => $superAdminDeleteResult,
    'super_admin_publish_result' => $superAdminPublishResult,
    'settings_yes_result' => $settingsYesResult,
    'settings_no_result' => $settingsNoResult,
    'settings_missing_result' => $settingsMissingResult,
    'db_delete_result' => $dbDeleteResult,
    'db_publish_result' => $dbPublishResult,
    'db_missing_result' => $dbMissingResult,
    'config_calls' => $captured->config_calls,
    'db_calls' => $captured->db_calls,
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
