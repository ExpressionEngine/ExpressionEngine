<?php

require __DIR__ . '/../bootstrap.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

class SqlStructureGetListingChannelByIdSubprocessFixture extends Sql_structure
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
$method = new ReflectionMethod('Sql_structure', 'get_listing_channel_by_id');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');
$captured = (object) [
    'calls' => [],
];

ee()->resetMocks();
ee()->setMock('db', new class($captured) {
    private $captured;

    /**
     * Store shared capture state for the fake structure lookups.
     *
     * @param object $captured
     * @return void
     */
    public function __construct(object $captured)
    {
        $this->captured = $captured;
    }

    /**
     * Return queued structure rows for each exercised branch.
     *
     * @param string $table
     * @param array|null $where
     * @return object
     */
    public function get_where($table, $where = null): object
    {
        $this->captured->calls[] = [
            'table' => $table,
            'where' => $where,
        ];

        $rows = [];
        $numRows = 0;

        if ($where === ['entry_id' => 10, 'site_id' => 4]) {
            $rows = [['listing_cid' => 8]];
            $numRows = 1;
        }

        if ($where === ['entry_id' => 11, 'site_id' => 4]) {
            $rows = [['listing_cid' => '0']];
            $numRows = 1;
        }

        return new class($rows, $numRows) {
            private $rows;
            private $numRows;

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
                $this->numRows = $numRows;
            }

            /**
             * Return the configured row count.
             *
             * @return int
             */
            public function num_rows(): int
            {
                return $this->numRows;
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

$sql = new SqlStructureGetListingChannelByIdSubprocessFixture();
$sql->site_id = 4;
$sql->cache = [];

$populatedResult = $sql->get_listing_channel_by_id(10);
$zeroResult = $sql->get_listing_channel_by_id(11);
$missingResult = $sql->get_listing_channel_by_id(12);

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['Sql_structure->get_listing_channel_by_id'] ?? ['branches' => [], 'paths' => []];
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
    'populated_result' => $populatedResult,
    'zero_result' => $zeroResult,
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
