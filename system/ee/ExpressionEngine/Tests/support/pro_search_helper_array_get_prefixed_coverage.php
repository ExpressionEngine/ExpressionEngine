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
$function = new ReflectionFunction('pro_array_get_prefixed');
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

$params = [
    'form_id' => '10',
    'form_class' => 'primary',
    'not_form_id' => '20',
    'Form_id' => 'wrong-case',
    'form_empty' => '',
    'form_zero' => 0,
    'form_false' => false,
    'form_null' => null,
];
$stripParams = [
    'form_id' => '10',
    'form_' => 'empty-key',
    'form_class' => 'primary',
    'not_form_id' => '20',
];

$originalKeys = pro_array_get_prefixed($params, 'form_');
$strippedKeys = pro_array_get_prefixed($stripParams, 'form_', true);
$truthyStripKeys = pro_array_get_prefixed($stripParams, 'form_', 1);
$emptyPrefix = pro_array_get_prefixed(['form_id' => '10'], '');
$nullPrefix = @pro_array_get_prefixed(['form_id' => '10'], null);
$falsePrefix = @pro_array_get_prefixed(['form_id' => '10'], false);
$zeroStringPrefix = pro_array_get_prefixed(['0id' => '10'], '0');
$emptyArray = pro_array_get_prefixed([], 'form_');
$nullArray = @pro_array_get_prefixed(null, 'form_');
$falseArray = @pro_array_get_prefixed(false, 'form_');
$stringArray = @pro_array_get_prefixed('form_id=10', 'form_');

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['pro_array_get_prefixed'] ?? ['branches' => [], 'paths' => []];
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
    'original_keys' => $originalKeys,
    'stripped_keys' => $strippedKeys,
    'truthy_strip_keys' => $truthyStripKeys,
    'empty_prefix' => $emptyPrefix,
    'null_prefix' => $nullPrefix,
    'false_prefix' => $falsePrefix,
    'zero_string_prefix' => $zeroStringPrefix,
    'empty_array' => $emptyArray,
    'null_array' => $nullArray,
    'false_array' => $falseArray,
    'string_array' => $stringArray,
], JSON_PRETTY_PRINT));
