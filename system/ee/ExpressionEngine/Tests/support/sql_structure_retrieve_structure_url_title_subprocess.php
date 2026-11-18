<?php

require __DIR__ . '/../bootstrap.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

class SqlStructureRetrieveStructureUrlTitleSubprocessFixture extends Sql_structure
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
$method = new ReflectionMethod('Sql_structure', 'retrieve_structure_url_title');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');
$captured = (object) [
    'select_calls' => [],
    'from_calls' => [],
    'where_calls' => [],
    'join_calls' => [],
    'limit_calls' => [],
    'lookups' => [],
];

ee()->resetMocks();
ee()->setMock('db', new class($captured) {
    private $captured;
    private $entryId;
    private $rows;

    /**
     * Store query capture state and queue lookup rows.
     *
     * @param object $captured
     * @return void
     */
    public function __construct(object $captured)
    {
        $this->captured = $captured;
        $this->rows = [
            '3' => ['structure_url_title' => 'child', 'parent_id' => '2'],
            '2' => ['structure_url_title' => 'parent', 'parent_id' => '1'],
            '1' => ['structure_url_title' => 'root', 'parent_id' => '0'],
            '4' => ['structure_url_title' => 'leaf', 'parent_id' => '0'],
        ];
    }

    /**
     * Capture the selected fields.
     *
     * @param string $fields
     * @return self
     */
    public function select($fields = '*')
    {
        $this->captured->select_calls[] = $fields;

        return $this;
    }

    /**
     * Capture the table source.
     *
     * @param string $table
     * @return self
     */
    public function from($table)
    {
        $this->captured->from_calls[] = $table;

        return $this;
    }

    /**
     * Capture query constraints and track the current entry lookup.
     *
     * @param string $field
     * @param mixed $value
     * @return self
     */
    public function where($field, $value = null)
    {
        $this->captured->where_calls[] = [$field, $value];

        if ($field === 'structure.entry_id') {
            $this->entryId = (string) $value;
        }

        return $this;
    }

    /**
     * Capture the channel titles join.
     *
     * @param string $table
     * @param string $condition
     * @param string $type
     * @return self
     */
    public function join($table, $condition, $type = '')
    {
        $this->captured->join_calls[] = [$table, $condition, $type];

        return $this;
    }

    /**
     * Capture the applied result limit.
     *
     * @param int $count
     * @return self
     */
    public function limit($count)
    {
        $this->captured->limit_calls[] = $count;

        return $this;
    }

    /**
     * Return the queued row for the current entry lookup.
     *
     * @return object
     */
    public function get()
    {
        $this->captured->lookups[] = $this->entryId;

        return new class($this->rows[$this->entryId] ?? []) {
            private $row;

            /**
             * Store the row payload for the lookup.
             *
             * @param array $row
             * @return void
             */
            public function __construct(array $row)
            {
                $this->row = $row;
            }

            /**
             * Return the current row as an array.
             *
             * @return array
             */
            public function row_array(): array
            {
                return $this->row;
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

$sql = new SqlStructureRetrieveStructureUrlTitleSubprocessFixture();
$sql->site_id = 6;
$sql->cache = [];

$recursiveResult = $sql->retrieve_structure_url_title(3);
$leafResult = $sql->retrieve_structure_url_title(4);
$missingResult = $sql->retrieve_structure_url_title(999);

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['Sql_structure->retrieve_structure_url_title'] ?? ['branches' => [], 'paths' => []];
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
    'recursive_result' => $recursiveResult,
    'leaf_result' => $leafResult,
    'missing_result' => $missingResult,
    'select_calls' => $captured->select_calls,
    'from_calls' => $captured->from_calls,
    'where_calls' => $captured->where_calls,
    'join_calls' => $captured->join_calls,
    'limit_calls' => $captured->limit_calls,
    'lookups' => $captured->lookups,
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
