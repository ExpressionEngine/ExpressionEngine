<?php

require __DIR__ . '/../bootstrap.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

class SqlStructureGetListingChannelShortNameSubprocessFixture extends Sql_structure
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
$method = new ReflectionMethod('Sql_structure', 'get_listing_channel_short_name');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');
$captured = (object) [
    'sql_calls' => [],
];

ee()->resetMocks();
ee()->setMock('sql_helper', new class($captured) {
    private $captured;

    /**
     * Store SQL capture state and route scenario results.
     *
     * @param object $captured
     * @return void
     */
    public function __construct(object $captured)
    {
        $this->captured = $captured;
    }

    /**
     * Return the queued row for the current channel lookup.
     *
     * @param string $sql
     * @return array|null
     */
    public function row($sql)
    {
        $this->captured->sql_calls[] = $sql;

        if (strpos($sql, 'WHERE channel_id = 10') !== false) {
            return ['channel_name' => 'blog'];
        }

        if (strpos($sql, 'WHERE channel_id = 11') !== false) {
            return ['channel_name' => '0'];
        }

        if (strpos($sql, 'WHERE channel_id = 12') !== false) {
            return null;
        }

        return null;
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

$sql = new SqlStructureGetListingChannelShortNameSubprocessFixture();
$sql->site_id = 6;
$sql->cache = [];

$namedResult = $sql->get_listing_channel_short_name(10);
$zeroStringResult = $sql->get_listing_channel_short_name(11);
$missingResult = $sql->get_listing_channel_short_name(12);

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['Sql_structure->get_listing_channel_short_name'] ?? ['branches' => [], 'paths' => []];
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
    'named_result' => $namedResult,
    'zero_string_result' => $zeroStringResult,
    'missing_result' => $missingResult,
    'sql_calls' => $captured->sql_calls,
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
