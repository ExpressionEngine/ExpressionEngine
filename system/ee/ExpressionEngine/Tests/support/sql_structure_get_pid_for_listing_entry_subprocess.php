<?php

require __DIR__ . '/../bootstrap.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

class SqlStructureGetPidForListingEntrySubprocessFixture extends Sql_structure
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

class SqlStructureGetPidForListingEntrySubprocessResult
{
    public $num_rows;
    private $rowValues;
    private $rowCalls;
    private $throwOnRowCall;

    /**
     * Store fake database result state for the subprocess run.
     *
     * @param array $rowValues
     * @param int|null $numRows
     * @param array|null $rowCalls
     * @param bool $throwOnRowCall
     * @return void
     */
    public function __construct(array $rowValues, ?int $numRows = null, ?array &$rowCalls = null, bool $throwOnRowCall = false)
    {
        $this->num_rows = $numRows ?? (empty($rowValues) ? 0 : 1);
        $this->rowValues = $rowValues;
        $this->throwOnRowCall = $throwOnRowCall;

        if ($rowCalls === null) {
            $rowCalls = [];
        }

        $this->rowCalls = &$rowCalls;
    }

    /**
     * Return the requested column value and record any access.
     *
     * @param string $column
     * @return mixed
     */
    public function row($column)
    {
        $this->rowCalls[] = $column;

        if ($this->throwOnRowCall) {
            throw new RuntimeException('row() should not be called when the parent lookup reports zero rows.');
        }

        return $this->rowValues[$column] ?? null;
    }
}

class SqlStructureGetPidForListingEntrySubprocessDb
{
    private $results;
    private $captured;
    private $calls = 0;

    /**
     * Queue fake database results for each subprocess query.
     *
     * @param array $results
     * @param object $captured
     * @return void
     */
    public function __construct(array $results, object $captured)
    {
        $this->results = $results;
        $this->captured = $captured;
    }

    /**
     * Return the next queued database result and capture the SQL.
     *
     * @param string $sql
     * @return object
     */
    public function query($sql): object
    {
        $this->captured->queries[] = preg_replace('/\s+/', ' ', trim($sql));

        if (! array_key_exists($this->calls, $this->results)) {
            throw new RuntimeException('Unexpected extra database query.');
        }

        return $this->results[$this->calls++];
    }
}

$target = realpath(PATH_ADDONS . 'structure/sql.structure.php');
$method = new ReflectionMethod('Sql_structure', 'get_pid_for_listing_entry');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');
$captured = (object) [
    'queries' => [],
];
$missingParentRowCalls = [];

ee()->resetMocks();
ee()->setMock('db', new SqlStructureGetPidForListingEntrySubprocessDb([
    new SqlStructureGetPidForListingEntrySubprocessResult(['channel_id' => 7]),
    new SqlStructureGetPidForListingEntrySubprocessResult(['entry_id' => 55]),
    new SqlStructureGetPidForListingEntrySubprocessResult(['channel_id' => ['unexpected' => true]]),
    new SqlStructureGetPidForListingEntrySubprocessResult(['channel_id' => 8]),
    new SqlStructureGetPidForListingEntrySubprocessResult(['entry_id' => 999], 0, $missingParentRowCalls, true),
], $captured));

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

$sql = new SqlStructureGetPidForListingEntrySubprocessFixture();
$sql->site_id = 1;
$sql->cache = [];

$happyPathResult = $sql->get_pid_for_listing_entry(10);
$arrayChannelResult = $sql->get_pid_for_listing_entry(11);
$missingParentResult = $sql->get_pid_for_listing_entry(12);

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['Sql_structure->get_pid_for_listing_entry'] ?? ['branches' => [], 'paths' => []];
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
    'happy_path_result' => $happyPathResult,
    'array_channel_result' => $arrayChannelResult,
    'missing_parent_result' => $missingParentResult,
    'missing_parent_row_calls' => $missingParentRowCalls,
    'queries' => $captured->queries,
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
