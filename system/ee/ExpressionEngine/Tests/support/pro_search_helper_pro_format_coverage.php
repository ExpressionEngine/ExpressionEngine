<?php

if (!defined('SYSPATH')) {
    define('SYSPATH', realpath(__DIR__ . '/../../../..') . '/');
}

if (!defined('BASEPATH')) {
    define('BASEPATH', SYSPATH . 'ee/legacy/');
}

require_once __DIR__ . '/../eeObjectMock.php';
require_once __DIR__ . '/../../Addons/pro_search/helpers/pro_search_helper.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$target = realpath(__DIR__ . '/../../Addons/pro_search/helpers/pro_search_helper.php');
$function = new ReflectionFunction('pro_format');
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

pro_format('A & <B> {x} "q"');
pro_format('A & <B> {x} "q"', 'url');
pro_format('A & <B> {x} "q"', 'html');
pro_format('A & <B> {x} "q"', 'ee-encode');
pro_format('A &amp; &lt;B&gt; &#123;x&#125; &quot;q&quot;', 'ee-decode');
pro_format('A & <B> {x} "q"', 'raw');

ee()->setMock('load', new class {
    /**
     * Stub library loading for clean-format coverage.
     *
     * @param string $name
     * @return void
     */
    public function library(string $name): void
    {
    }
});
ee()->setMock('pro_search_words', new class {
    /**
     * Return a deterministic cleaned value.
     *
     * @param string $str
     * @param bool $ignore
     * @return string
     */
    public function clean(string $str, bool $ignore): string
    {
        return 'cleaned cafe search';
    }

    /**
     * Return a deterministic diacritic-stripped value.
     *
     * @param string $str
     * @return string
     */
    public function remove_diacritics(string $str): string
    {
        return $str;
    }
});
pro_format(' Cafe Search ', 'clean');

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['pro_format'] ?? ['branches' => [], 'paths' => []];
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
], JSON_PRETTY_PRINT));
