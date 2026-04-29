<?php

require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../ExpressionEngine/Addons/file/FT/FileFtTestBase.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$childMode = getenv('FILE_FT_DISPLAY_FIELD_MODE') ?: null;
$target = realpath(PATH_ADDONS . 'file/ft.file.php');
$method = new ReflectionMethod('File_ft', 'display_field');

class FileFtDisplayFieldCoverageSpy extends File_ft
{
    /** @var int */
    public $frontendJsCalls = 0;

    /**
     * Record front-end JavaScript loading without executing the helper body.
     *
     * @return void
     */
    protected function _frontend_js()
    {
        $this->frontendJsCalls++;
    }
}

/**
 * Reset EE mocks and seed the shared File_ft display-field doubles.
 *
 * @return array<string, mixed>
 */
function file_ft_display_field_bootstrap(): array
{
    ee()->resetMocks();

    $load = new FileFtLoadRecorder();
    $session = new FileFtSessionStub();
    $fileField = new FileFtFileFieldStub();
    $model = new FileFtModelServiceStub();
    $javascript = new FileFtJavascriptStub();
    $cp = new FileFtCpStub();
    $cpUrl = new FileFtCpUrlFactoryStub();

    ee()->setMock('load', $load);
    ee()->setMock('session', $session);
    ee()->setMock('file_field', $fileField);
    ee()->setMock('Model', $model);
    ee()->setMock('javascript', $javascript);
    ee()->setMock('cp', $cp);
    ee()->setMock('CP/URL', $cpUrl);

    return [
        'load' => $load,
        'session' => $session,
        'file_field' => $fileField,
        'model' => $model,
        'javascript' => $javascript,
        'cp' => $cp,
        'cp_url' => $cpUrl,
    ];
}

/**
 * Build a File_ft instance with the requested display-field context.
 *
 * @param class-string<File_ft> $fieldtypeClass
 * @param array<string, mixed> $settings
 * @param string $fieldName
 * @return File_ft
 */
function file_ft_display_field_make_fieldtype(string $fieldtypeClass, array $settings = [], string $fieldName = 'file_field'): File_ft
{
    $fieldtype = new $fieldtypeClass();
    $fieldtype->settings = array_merge([
        'field_required' => 'n',
    ], $settings);
    $fieldtype->field_name = $fieldName;

    return $fieldtype;
}

/**
 * Start Xdebug coverage with branch tracking when available.
 *
 * @return bool
 */
function file_ft_display_field_start_coverage(): bool
{
    if (! function_exists('xdebug_start_code_coverage') || ! function_exists('xdebug_get_code_coverage')) {
        return false;
    }

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

    return true;
}

/**
 * Convert raw Xdebug coverage into display_field-specific line and branch maps.
 *
 * @param string $target
 * @param ReflectionMethod $method
 * @param bool $xdebugAvailable
 * @return array<string, mixed>
 */
function file_ft_display_field_collect_coverage(string $target, ReflectionMethod $method, bool $xdebugAvailable): array
{
    $coverage = [];

    if ($xdebugAvailable) {
        $coverage = xdebug_get_code_coverage();
    }

    $fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
    $functionCoverage = $fileCoverage['functions']['File_ft->display_field'] ?? ['branches' => [], 'paths' => []];
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

    foreach (($functionCoverage['branches'] ?? []) as $branchId => $branch) {
        if (count($branch['out_hit'] ?? []) <= 1) {
            continue;
        }

        foreach (($branch['out_hit'] ?? []) as $edgeIndex => $hitCount) {
            $coveredBranches[$branchId . ':' . $edgeIndex] = ($hitCount > 0);
        }
    }

    return [
        'real_module_path' => $target,
        'xdebug_available' => $xdebugAvailable,
        'lines' => $coveredLines,
        'branches' => $functionCoverage['branches'] ?? [],
        'paths' => $functionCoverage['paths'] ?? [],
        'covered_branches' => $coveredBranches,
    ];
}

/**
 * Merge child-process coverage payloads and compute final percentages.
 *
 * @param string $target
 * @param array<int, array<string, mixed>> $payloads
 * @return array<string, mixed>
 */
function file_ft_display_field_merge_coverage(string $target, array $payloads): array
{
    $mergedLines = [];
    $mergedBranches = [];
    $mergedPaths = [];
    $coveredBranches = [];
    $xdebugAvailable = false;

    foreach ($payloads as $payload) {
        $xdebugAvailable = $xdebugAvailable || ! empty($payload['xdebug_available']);

        foreach (($payload['lines'] ?? []) as $line => $covered) {
            $mergedLines[$line] = ($mergedLines[$line] ?? false) || $covered;
        }

        foreach (($payload['branches'] ?? []) as $branchId => $branch) {
            if (! isset($mergedBranches[$branchId])) {
                $mergedBranches[$branchId] = $branch;
                continue;
            }

            foreach (($branch['out_hit'] ?? []) as $edgeIndex => $hitCount) {
                $mergedBranches[$branchId]['out_hit'][$edgeIndex] = max(
                    $mergedBranches[$branchId]['out_hit'][$edgeIndex] ?? 0,
                    $hitCount
                );
            }
        }

        $mergedPaths = $payload['paths'] ?? $mergedPaths;

        foreach (($payload['covered_branches'] ?? []) as $branchKey => $covered) {
            $coveredBranches[$branchKey] = ($coveredBranches[$branchKey] ?? false) || $covered;
        }
    }

    $totalBranches = count($coveredBranches);
    $linePercentage = count($mergedLines) > 0
        ? count(array_filter($mergedLines)) / count($mergedLines) * 100
        : 0.0;
    $branchPercentage = $totalBranches > 0
        ? count(array_filter($coveredBranches)) / $totalBranches * 100
        : 0.0;

    return [
        'real_module_path' => $target,
        'xdebug_available' => $xdebugAvailable,
        'line_percentage' => $linePercentage,
        'branch_percentage' => $branchPercentage,
        'lines' => $mergedLines,
        'branches' => $mergedBranches,
        'paths' => $mergedPaths,
        'covered_branches' => $coveredBranches,
        'uncovered_lines' => array_values(array_keys(array_filter($mergedLines, function ($covered) {
            return ! $covered;
        }))),
        'uncovered_branches' => array_values(array_keys(array_filter($coveredBranches, function ($covered) {
            return ! $covered;
        }))),
    ];
}

/**
 * Execute the child coverage pass for the requested request mode.
 *
 * @param string $mode
 * @param string $outputFile
 * @return void
 */
function file_ft_display_field_run_child(string $mode, string $outputFile): void
{
    $command = sprintf(
        'FILE_FT_DISPLAY_FIELD_MODE=%s %s %s %s 2>&1',
        escapeshellarg($mode),
        escapeshellarg(PHP_BINARY),
        escapeshellarg(__FILE__),
        escapeshellarg($outputFile)
    );

    exec($command, $commandOutput, $exitCode);

    if ($exitCode === 0) {
        return;
    }

    fwrite(STDERR, implode(PHP_EOL, $commandOutput) . PHP_EOL);
    exit($exitCode);
}

if ($childMode) {
    if (! defined('REQ')) {
        define('REQ', $childMode);
    }

    $xdebugAvailable = file_ft_display_field_start_coverage();

    if ($childMode === 'CP') {
        $env = file_ft_display_field_bootstrap();
        $env['file_field']->dragAndDropReturn = '<cp picker>';
        file_ft_display_field_make_fieldtype(
            File_ft::class,
            [
                'allowed_directories' => [3, 8],
                'field_content_type' => 'images',
                'num_existing' => 4,
                'show_existing' => 'y',
            ],
            'hero_image'
        )->display_field('{filedir_3}banner.png');
    }

    if ($childMode === 'PAGE') {
        $env = file_ft_display_field_bootstrap();
        $env['file_field']->fieldReturn = '<frontend picker>';
        file_ft_display_field_make_fieldtype(FileFtDisplayFieldCoverageSpy::class, [], 'feature_file')
            ->display_field('{filedir_1}brochure.pdf');

        $env = file_ft_display_field_bootstrap();
        $env['file_field']->fieldReturn = '<frontend with existing>';
        file_ft_display_field_make_fieldtype(FileFtDisplayFieldCoverageSpy::class, [
            'allowed_directories' => [5],
            'field_content_type' => 'all',
            'num_existing' => 9,
            'show_existing' => 'y',
        ], 'asset_file')->display_field('{filedir_5}logo.svg');
    }

    file_put_contents(
        $outputFile,
        json_encode(
            file_ft_display_field_collect_coverage($target, $method, $xdebugAvailable),
            JSON_PRETTY_PRINT
        )
    );

    return;
}

$cpOutput = tempnam(sys_get_temp_dir(), 'file-ft-display-cp-');
$pageOutput = tempnam(sys_get_temp_dir(), 'file-ft-display-page-');

file_ft_display_field_run_child('CP', $cpOutput);
file_ft_display_field_run_child('PAGE', $pageOutput);

$mergedCoverage = file_ft_display_field_merge_coverage($target, [
    json_decode((string) file_get_contents($cpOutput), true) ?: [],
    json_decode((string) file_get_contents($pageOutput), true) ?: [],
]);

@unlink($cpOutput);
@unlink($pageOutput);

file_put_contents($outputFile, json_encode($mergedCoverage, JSON_PRETTY_PRINT));
