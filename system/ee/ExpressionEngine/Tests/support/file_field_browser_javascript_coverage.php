<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../ExpressionEngine/legacy/Libraries/FileFieldFieldTest.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$target = realpath(SYSPATH . 'ee/legacy/libraries/File_field.php');
$method = new ReflectionMethod('File_field', '_browser_javascript');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

\ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldFieldTest::setUpBeforeClass();

/**
 * Build a browser harness with deterministic collaborators.
 *
 * @return \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldFieldHarness
 */
function build_file_field_browser_subject()
{
    ee()->resetMocks();
    ee()->config->resetConfig();
    ee()->setMock('load', new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldLoadMock());
    ee()->setMock('view', new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldLegacyViewMock());
    ee()->setMock('cp', new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldCpMock());
    ee()->setMock('javascript', new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldJavascriptMock());
    ee()->setMock('lang', new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldLangMock());
    ee()->setMock('functions', new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldFunctionsMock());

    return new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldFieldHarness();
}

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

$subject = build_file_field_browser_subject();
$subject->browser();

$subject = build_file_field_browser_subject();
$subject->browser([
    'trigger' => '#browser-trigger',
    'callback' => 'function(file, field) { return file; }',
], 'custom/filepicker/modal');

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['File_field->_browser_javascript'] ?? ['branches' => [], 'paths' => []];
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
    : 100.0;

file_put_contents($outputFile, json_encode([
    'real_module_path' => $target,
    'xdebug_available' => $xdebugAvailable,
    'line_percentage' => $linePercentage,
    'branch_percentage' => $branchPercentage,
    'lines' => $coveredLines,
    'branches' => $functionCoverage['branches'] ?? [],
    'paths' => $functionCoverage['paths'] ?? [],
    'covered_branches' => $coveredBranches,
    'total_branch_edges' => $totalBranches,
    'uncovered_lines' => array_values(array_keys(array_filter($coveredLines, function ($covered) {
        return ! $covered;
    }))),
    'uncovered_branches' => array_values(array_keys(array_filter($coveredBranches, function ($covered) {
        return ! $covered;
    }))),
], JSON_PRETTY_PRINT));
