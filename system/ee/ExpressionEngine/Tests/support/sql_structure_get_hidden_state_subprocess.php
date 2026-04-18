<?php

require __DIR__ . '/../bootstrap.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

class SqlStructureGetHiddenStateSubprocessFixture extends Sql_structure
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
$method = new ReflectionMethod('Sql_structure', 'get_hidden_state');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');
$captured = (object) [
    'calls' => [],
];

ee()->resetMocks();
ee()->setMock('db', new class($captured) {
    private $captured;
    private $currentEntryId;

    /**
     * Store the shared capture state for the fake structure lookups.
     *
     * @param object $captured
     * @return void
     */
    public function __construct(object $captured)
    {
        $this->captured = $captured;
    }

    /**
     * Capture the selected column for each hidden-state lookup.
     *
     * @param string $field
     * @return object
     */
    public function select($field): object
    {
        $this->captured->calls[] = [
            'method' => 'select',
            'field' => $field,
        ];

        return $this;
    }

    /**
     * Capture the queried table for each hidden-state lookup.
     *
     * @param string $table
     * @return object
     */
    public function from($table): object
    {
        $this->captured->calls[] = [
            'method' => 'from',
            'table' => $table,
        ];

        return $this;
    }

    /**
     * Capture the lookup filters and route responses by entry ID.
     *
     * @param array|string $field
     * @param mixed|null $value
     * @return object
     */
    public function where($field, $value = null): object
    {
        $this->captured->calls[] = [
            'method' => 'where',
            'field' => $field,
            'value' => $value,
        ];

        if (is_array($field)) {
            $this->currentEntryId = $field['entry_id'] ?? null;
        }

        return $this;
    }

    /**
     * Return a populated row once and an empty result set for the missing path.
     *
     * @return object
     */
    public function get(): object
    {
        $this->captured->calls[] = [
            'method' => 'get',
        ];

        $rows = [];
        $numRows = 0;

        if ($this->currentEntryId === 10) {
            $rows = [['hidden' => 'y']];
            $numRows = 1;
        }

        return new class($rows, $numRows) {
            private $rows;
            public $num_rows;

            /**
             * Store the configured rows and row count.
             *
             * @param array $rows
             * @param int $numRows
             * @return void
             */
            public function __construct(array $rows, int $numRows)
            {
                $this->rows = $rows;
                $this->num_rows = $numRows;
            }

            /**
             * Return the configured row count.
             *
             * @return int
             */
            public function num_rows(): int
            {
                return $this->num_rows;
            }

            /**
             * Return the first row as an object, matching CI DB behavior.
             *
             * @return object
             */
            public function row(): object
            {
                return (object) ($this->rows[0] ?? []);
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

$sql = new SqlStructureGetHiddenStateSubprocessFixture();
$sql->site_id = 4;
$sql->cache = [];

$storedResult = $sql->get_hidden_state(10);
$missingResult = $sql->get_hidden_state(11);

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['Sql_structure->get_hidden_state'] ?? ['branches' => [], 'paths' => []];
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
    'stored_result' => $storedResult,
    'missing_result' => $missingResult,
    'calls' => $captured->calls,
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
