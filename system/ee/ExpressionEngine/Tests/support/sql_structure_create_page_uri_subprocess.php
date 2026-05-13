<?php

require __DIR__ . '/../bootstrap.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$target = realpath(PATH_ADDONS . 'structure/sql.structure.php');
$method = new ReflectionMethod('Sql_structure', 'create_page_uri');
$methodStartLine = $method->getStartLine();
$methodEndLine = $method->getEndLine();
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

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

$sql = (new ReflectionClass('Sql_structure'))->newInstanceWithoutConstructor();
$sql->site_id = 1;
$sql->cache = [];

$happyResult = $sql->create_page_uri('/parent', 'child');
$sanitizedResult = $sql->create_page_uri('/parent section/', 'child?x=1#frag');
$unicodeResult = $sql->create_page_uri('/ümlaut', 'niño');
$rootResult = $sql->create_page_uri('', '');

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['Sql_structure->create_page_uri'] ?? ['branches' => [], 'paths' => []];
$coveredLines = [];

foreach (($fileCoverage['lines'] ?? []) as $line => $hitCount) {
    if ($line < $methodStartLine || $line > $methodEndLine) {
        continue;
    }

    if ($hitCount === -2) {
        continue;
    }

    $coveredLines[$line] = ($hitCount > 0);
}

ksort($coveredLines);

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
    'happy_result' => $happyResult,
    'sanitized_result' => $sanitizedResult,
    'unicode_result' => $unicodeResult,
    'root_result' => $rootResult,
    'xdebug_available' => $xdebugAvailable,
    'lines' => array_intersect_key($fileCoverage['lines'] ?? [], $coveredLines),
    'method_start_line' => $methodStartLine,
    'method_end_line' => $methodEndLine,
    'branches' => $functionCoverage['branches'] ?? [],
    'paths' => $functionCoverage['paths'] ?? [],
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
