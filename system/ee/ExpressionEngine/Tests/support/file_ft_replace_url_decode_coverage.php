<?php

require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../ExpressionEngine/Addons/file/FT/FileFtTestBase.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$target = realpath(PATH_ADDONS . 'file/ft.file.php');
$method = new ReflectionMethod('File_ft', 'replace_url_decode');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

/**
 * Reset EE mocks and seed the shared File_ft replace_url_decode() doubles.
 *
 * @param mixed $returnValue
 * @return void
 */
function file_ft_replace_url_decode_bootstrap($returnValue): void
{
    ee()->resetMocks();
    ee()->setMock('load', new FileFtLoadRecorder());
    ee()->setMock('Format', new class($returnValue) {
        /** @var mixed */
        private $returnValue;

        /**
         * Store the formatter result returned from urlDecode().
         *
         * @param mixed $returnValue
         * @return void
         */
        public function __construct($returnValue)
        {
            $this->returnValue = $returnValue;
        }

        /**
         * Return the text formatter used by ModifiableTrait::replace_url_decode().
         *
         * @param string $type
         * @param mixed $value
         * @return object
         */
        public function make($type, $value)
        {
            return new class($this->returnValue) {
                /** @var mixed */
                private $returnValue;

                /**
                 * Store the formatter result returned from urlDecode().
                 *
                 * @param mixed $returnValue
                 * @return void
                 */
                public function __construct($returnValue)
                {
                    $this->returnValue = $returnValue;
                }

                /**
                 * Return the configured formatter result.
                 *
                 * @param array<string, mixed> $params
                 * @return mixed
                 */
                public function urlDecode($params = [])
                {
                    return $this->returnValue;
                }
            };
        }
    });
}

/**
 * Build a File_ft instance for replace_url_decode() coverage runs.
 *
 * @return File_ft
 */
function file_ft_replace_url_decode_make_fieldtype(): File_ft
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

file_ft_replace_url_decode_bootstrap('decoded::payload');
file_ft_replace_url_decode_make_fieldtype()->replace_url_decode([
    'url' => '{filedir_8}Spec+Sheet%20%26%20Notes.pdf%3Fdl%3DY',
    'raw_output' => '{filedir_8}ignored.pdf',
    'filename' => 'ignored.pdf',
], [
    'plus_encoded_spaces' => 'yes',
], '{ignored-tagdata}');

file_ft_replace_url_decode_bootstrap(0);
file_ft_replace_url_decode_make_fieldtype()->replace_url_decode([
    'url' => '',
    'raw_output' => '{filedir_3}fallback.pdf',
]);

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['File_ft->replace_url_decode'] ?? ['branches' => [], 'paths' => []];
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
