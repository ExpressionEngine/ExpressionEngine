<?php

require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../ExpressionEngine/Addons/file/FT/FileFtTestBase.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$target = realpath(PATH_ADDONS . 'file/ft.file.php');
$method = new ReflectionMethod('File_ft', 'var_replace_tag');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

class FileFtVarReplaceTagCoverageSpy extends File_ft
{
    /** @var mixed */
    public $preProcessReturn = [];

    /**
     * Return the seeded pre-processed payload.
     *
     * @param mixed $data
     * @return mixed
     */
    public function pre_process($data)
    {
        return $this->preProcessReturn;
    }

    /**
     * Return a fixed tag fallback result.
     *
     * @param mixed $data
     * @param array<string, mixed> $params
     * @param mixed $tagdata
     * @return string
     */
    public function replace_tag($data, $params = array(), $tagdata = false)
    {
        return 'tag-output';
    }

    /**
     * Return a fixed resize result.
     *
     * @param mixed $data
     * @param array<string, mixed> $params
     * @param mixed $tagdata
     * @return string
     */
    public function replace_resize($data, $params = array(), $tagdata = false)
    {
        return 'resize-output';
    }
}

/**
 * Reset EE mocks and seed the shared File_ft var_replace_tag doubles.
 *
 * @return FileFtTemplateStub
 */
function file_ft_var_replace_tag_bootstrap()
{
    ee()->resetMocks();

    $template = new FileFtTemplateStub();
    ee()->setMock('TMPL', $template);

    return $template;
}

/**
 * Build a coverage spy for var_replace_tag().
 *
 * @param mixed $preProcessReturn
 * @return FileFtVarReplaceTagCoverageSpy
 */
function file_ft_var_replace_tag_make_fieldtype($preProcessReturn)
{
    $fieldtype = new FileFtVarReplaceTagCoverageSpy();
    $fieldtype->preProcessReturn = $preProcessReturn;

    return $fieldtype;
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

$template = file_ft_var_replace_tag_bootstrap();
$template->fetchParamMap['modifier'] = 'resize';
file_ft_var_replace_tag_make_fieldtype(['file_id' => 9])->var_replace_tag('{filedir_2}hero.jpg', ['width' => '1200'], '{file:url}');

$template = file_ft_var_replace_tag_bootstrap();
$template->fetchParamMap['modifier'] = 'resize';
file_ft_var_replace_tag_make_fieldtype(['file_id' => 12])->var_replace_tag('{filedir_7}manual.pdf', ['wrap' => 'plain'], '');

$template = file_ft_var_replace_tag_bootstrap();
$template->fetchParamMap['modifier'] = 'unknown';
file_ft_var_replace_tag_make_fieldtype(false)->var_replace_tag('{filedir_5}fallback.pdf', ['raw_output' => 'yes'], '{tagdata}');

$template = file_ft_var_replace_tag_bootstrap();
file_ft_var_replace_tag_make_fieldtype(['file_id' => 44])->var_replace_tag('{filedir_1}default.pdf', ['wrap' => 'plain']);

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['File_ft->var_replace_tag'] ?? ['branches' => [], 'paths' => []];
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
