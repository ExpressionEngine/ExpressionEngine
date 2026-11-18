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
$method = new ReflectionMethod('File_field', 'cache_data');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

/**
 * Reset mocks and return a fresh subject with collaborators.
 *
 * @return array<string, mixed>
 */
function file_field_cache_data_bootstrap(): array
{
    ee()->resetMocks();
    ee()->config->resetConfig();

    $load = new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldLoadMock();
    $modelService = new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldModelServiceMock();

    ee()->setMock('load', $load);
    ee()->setMock('Model', $modelService);

    $subject = new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldFieldHarness();

    return [
        'load' => $load,
        'model' => $modelService,
        'subject' => $subject,
    ];
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

$env = file_field_cache_data_bootstrap();
$env['subject']->cache_data([]);

$env = file_field_cache_data_bootstrap();
$env['subject']->_files = [
    ['file_id' => 999, 'file_name' => 'existing.png'],
];
$env['subject']->_file_names = ['already-cached.jpg'];
$env['subject']->_file_ids = ['12'];

$nameFilters = [
    ['field' => 'file_name', 'operator' => 'IN', 'value' => ['diagram.jpg']],
    ['field' => 'upload_location_id', 'operator' => 'IN', 'value' => ['3']],
];
$idFilters = [
    ['field' => 'file_id', 'operator' => 'IN', 'value' => ['44']],
];
$env['model']->fileResultsAllByFilterKey[$env['model']->buildFileFilterKey($nameFilters)] = [
    new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldCachedModelRecordMock([
        'file_id' => 301,
        'file_name' => 'diagram.jpg',
        'upload_location_id' => 3,
    ]),
];
$env['model']->fileResultsAllByFilterKey[$env['model']->buildFileFilterKey($idFilters)] = [
    new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldCachedModelRecordMock([
        'file_id' => 44,
        'file_name' => 'manual.pdf',
        'upload_location_id' => 0,
    ]),
];
$env['subject']->cache_data([
    '{filedir_3}diagram.jpg',
    '44',
    '{file:12:url}',
    '{filedir_3}diagram.jpg',
    'manual-text',
    '',
    '0',
]);

$env = file_field_cache_data_bootstrap();
$env['subject']->cache_data([
    '77',
]);

$env = file_field_cache_data_bootstrap();
$env['subject']->_files = [
    ['file_id' => 44, 'file_name' => 'diagram.jpg'],
];
$env['subject']->_file_names = ['diagram.jpg'];
$env['subject']->_file_ids = ['44'];
$env['subject']->cache_data([
    '{filedir_3}diagram.jpg',
    '44',
    '',
    'manual-entry',
    '0',
]);

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['File_field->cache_data'] ?? ['branches' => [], 'paths' => []];
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
    'xdebug_available' => $xdebugAvailable,
    'line_percentage' => $linePercentage,
    'branch_percentage' => $branchPercentage,
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
], JSON_PRETTY_PRINT));
