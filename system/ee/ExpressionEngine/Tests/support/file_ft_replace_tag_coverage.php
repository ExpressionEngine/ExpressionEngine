<?php

require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../ExpressionEngine/Addons/file/FT/FileFtTestBase.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$target = realpath(PATH_ADDONS . 'file/ft.file.php');
$method = new ReflectionMethod('File_ft', 'replace_tag');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

/**
 * Reset EE mocks and seed the shared File_ft replace_tag doubles.
 *
 * @return array<string, mixed>
 */
function file_ft_replace_tag_bootstrap(): array
{
    ee()->resetMocks();

    $load = new FileFtLoadRecorder();
    $fileField = new FileFtFileFieldStub();
    $template = new FileFtTemplateStub();

    ee()->setMock('load', $load);
    ee()->setMock('file_field', $fileField);
    ee()->setMock('TMPL', $template);

    return [
        'load' => $load,
        'file_field' => $fileField,
        'template' => $template,
    ];
}

/**
 * Build a File_ft instance for replace_tag() coverage runs.
 *
 * @return File_ft
 */
function file_ft_replace_tag_make_fieldtype(): File_ft
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

$env = file_ft_replace_tag_bootstrap();
file_ft_replace_tag_make_fieldtype()->replace_tag(false, [], '{file:url}');

$env = file_ft_replace_tag_bootstrap();
file_ft_replace_tag_make_fieldtype()->replace_tag([
    'raw_output' => '{"file_id":42}',
    'url' => '{filedir_4}ignored.pdf',
], [
    'raw_output' => 'yes',
]);

$env = file_ft_replace_tag_bootstrap();
$env['template']->parseVariablesReturn = 'parsed-tag-pair';
file_ft_replace_tag_make_fieldtype()->replace_tag([
    'path' => 'https://assets.example.com/files/',
    'filename' => 'banner',
    'extension' => 'png',
    'file_id' => 42,
], [], '{url:thumbs}|{id_path}');

$env = file_ft_replace_tag_bootstrap();
$env['file_field']->parseStringReturn = 'https://cdn.example.com/files/manual.pdf';
file_ft_replace_tag_make_fieldtype()->replace_tag([
    'url' => '{filedir_3}manual.pdf',
]);

$env = file_ft_replace_tag_bootstrap();
$env['file_field']->parseStringReturn = 'https://cdn.example.com/files/spec-sheet.pdf';
file_ft_replace_tag_make_fieldtype()->replace_tag([
    'url' => '{filedir_5}spec-sheet.pdf',
], [
    'wrap' => 'plain',
]);

$env = file_ft_replace_tag_bootstrap();
file_ft_replace_tag_make_fieldtype()->replace_tag([
    'path' => '/uploads/files/',
    'filename' => 'guide',
    'extension' => 'pdf',
    'file_id' => 77,
]);

$env = file_ft_replace_tag_bootstrap();
file_ft_replace_tag_make_fieldtype()->replace_tag([
    'path' => '/uploads/files/',
    'filename' => 'brochure',
    'extension' => 'pdf',
    'file_id' => 88,
], [
    'wrap' => 'plain',
]);

$env = file_ft_replace_tag_bootstrap();
file_ft_replace_tag_make_fieldtype()->replace_tag([]);

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['File_ft->replace_tag'] ?? ['branches' => [], 'paths' => []];
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
], JSON_PRETTY_PRINT));
