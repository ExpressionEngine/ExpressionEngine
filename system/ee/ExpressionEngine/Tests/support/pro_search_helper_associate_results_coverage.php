<?php

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/../../../legacy/');
}

require_once __DIR__ . '/../../Addons/pro_search/helpers/pro_search_helper.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$target = realpath(__DIR__ . '/../../Addons/pro_search/helpers/pro_search_helper.php');
$function = new ReflectionFunction('pro_associate_results');
$xdebugModes = function_exists('xdebug_info') ? xdebug_info('mode') : null;
$xdebugCoverageEnabled = $xdebugModes === null || in_array('coverage', (array) $xdebugModes, true);
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage') && $xdebugCoverageEnabled;

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

$uniqueResult = pro_associate_results([
    ['collection_id' => 'news', 'channel_id' => '5'],
    ['collection_id' => 'blog', 'channel_id' => '6'],
], 'collection_id');

$duplicateMissingResult = pro_associate_results([
    ['collection_id' => 'news', 'channel_id' => '5'],
    ['collection_id' => 'news', 'channel_id' => '7'],
    ['channel_id' => '8'],
    ['collection_id' => 'blog', 'channel_id' => '6'],
], 'collection_id');

$sortRows = [
    ['collection_id' => 'beta', 'channel_id' => '6'],
    ['collection_id' => 'alpha', 'channel_id' => '5'],
];
$sortedResult = pro_associate_results($sortRows, 'collection_id', true);
$strictSortResult = pro_associate_results($sortRows, 'collection_id', 1);
$emptyResult = pro_associate_results([], 'collection_id');
$nullResult = @pro_associate_results(null, 'collection_id');

$zeroIntegerRow = ['collection_id' => 0, 'label' => 'zero integer'];
$zeroStringRow = ['collection_id' => '0', 'label' => 'zero string'];
$nullRow = ['collection_id' => null, 'label' => 'null'];
$emptyStringRow = ['collection_id' => '', 'label' => 'empty string'];
$boundaryResult = pro_associate_results([
    $zeroIntegerRow,
    $zeroStringRow,
    $nullRow,
    $emptyStringRow,
], 'collection_id');

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['pro_associate_results'] ?? ['branches' => [], 'paths' => []];
$coveredLines = [];
$sourceLines = file($target, FILE_IGNORE_NEW_LINES) ?: [];

foreach (range($function->getStartLine(), $function->getEndLine()) as $line) {
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
    : 100.0;

file_put_contents($outputFile, json_encode([
    'real_module_path' => $target,
    'xdebug_available' => $xdebugAvailable,
    'line_percentage' => $linePercentage,
    'branch_percentage' => $branchPercentage,
    'total_branches' => $totalBranches,
    'lines' => $coveredLines,
    'branches' => $functionCoverage['branches'] ?? [],
    'paths' => $functionCoverage['paths'] ?? [],
    'covered_branches' => $coveredBranches,
    'uncovered_lines' => array_values(array_keys(array_filter($coveredLines, function ($covered) {
        return ! $covered;
    }))),
    'uncovered_branches' => array_values(array_keys(array_filter($coveredBranches, function ($covered) {
        return ! $covered;
    }))),
    'unique_result' => $uniqueResult,
    'duplicate_missing_result' => $duplicateMissingResult,
    'sorted_result' => $sortedResult,
    'strict_sort_result' => $strictSortResult,
    'empty_result' => $emptyResult,
    'null_result' => $nullResult,
    'boundary_result' => $boundaryResult,
], JSON_PRETTY_PRINT));
