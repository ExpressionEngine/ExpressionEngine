<?php

require __DIR__ . '/../bootstrap.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

class SqlStructureIsValidTemplateSubprocessFixture extends Sql_structure
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
$method = new ReflectionMethod('Sql_structure', 'is_valid_template');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');
$captured = (object) [
    'calls' => [],
];

ee()->resetMocks();
ee()->setMock('db', new class($captured) {
    private $captured;

    /**
     * Store database call capture state.
     *
     * @param object $captured
     * @return void
     */
    public function __construct(object $captured)
    {
        $this->captured = $captured;
    }

    /**
     * Record template lookups and route the fixture responses by ID.
     *
     * @param string $table
     * @param array|null $where
     * @param int|null $limit
     * @param int|null $offset
     * @return eeDbResultMock
     */
    public function get_where($table, $where = null, $limit = null, $offset = null): eeDbResultMock
    {
        $this->captured->calls[] = [
            'table' => $table,
            'where' => $where,
            'limit' => $limit,
            'offset' => $offset,
        ];

        $rows = [];

        if ($table === 'templates' && $where === ['template_id' => '9']) {
            $rows = [['template_id' => 9]];
        }

        return new eeDbResultMock($rows);
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

$sql = new SqlStructureIsValidTemplateSubprocessFixture();

$nonNumericResult = $sql->is_valid_template('template-nine');
$nonNumericCalls = $captured->calls;

$numericStringResult = $sql->is_valid_template('9');
$missingNumericResult = $sql->is_valid_template(999);
$lookupCalls = $captured->calls;

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['Sql_structure->is_valid_template'] ?? ['branches' => []];
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
    'non_numeric_calls' => $nonNumericCalls,
    'numeric_string_result' => $numericStringResult,
    'missing_numeric_result' => $missingNumericResult,
    'lookup_calls' => $lookupCalls,
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
