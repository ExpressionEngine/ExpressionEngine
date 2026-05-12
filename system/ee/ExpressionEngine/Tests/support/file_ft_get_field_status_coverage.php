<?php

require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../ExpressionEngine/Addons/file/FT/FileFtTestBase.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$target = realpath(PATH_ADDONS . 'file/ft.file.php');
$method = new ReflectionMethod('File_ft', 'get_field_status');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

class FileFtGetFieldStatusCoverageModelStub
{
    /** @var bool */
    private $existsResult;

    /**
     * Seed the existence result returned to get_field_status().
     *
     * @param bool $existsResult
     * @return void
     */
    public function __construct($existsResult)
    {
        $this->existsResult = $existsResult;
    }

    /**
     * Return the configured file-existence state.
     *
     * @return bool
     */
    public function exists()
    {
        return $this->existsResult;
    }
}

/**
 * Reset EE mocks and seed the shared File_ft status doubles.
 *
 * @return FileFtFileFieldStub
 */
function file_ft_get_field_status_bootstrap()
{
    ee()->resetMocks();

    ee()->setMock('load', new FileFtLoadRecorder());

    $fileField = new FileFtFileFieldStub();
    ee()->setMock('file_field', $fileField);

    return $fileField;
}

/**
 * Build a File_ft instance for get_field_status() coverage runs.
 *
 * @return File_ft
 */
function file_ft_get_field_status_make_fieldtype()
{
    return new File_ft();
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

$fileField = file_ft_get_field_status_bootstrap();
file_ft_get_field_status_make_fieldtype()->get_field_status('{filedir_1}unmapped.txt');

$fileField = file_ft_get_field_status_bootstrap();
$fileField->fileModel = new FileFtGetFieldStatusCoverageModelStub(true);
file_ft_get_field_status_make_fieldtype()->get_field_status('{filedir_1}existing.txt');

$fileField = file_ft_get_field_status_bootstrap();
$fileField->fileModel = new FileFtGetFieldStatusCoverageModelStub(false);
file_ft_get_field_status_make_fieldtype()->get_field_status('{filedir_1}missing.txt');

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['File_ft->get_field_status'] ?? ['branches' => [], 'paths' => []];
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
