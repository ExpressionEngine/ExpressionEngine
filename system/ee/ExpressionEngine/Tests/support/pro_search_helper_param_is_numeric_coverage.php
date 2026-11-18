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
$function = new ReflectionFunction('pro_param_is_numeric');
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

$accepted = [
    'zero' => pro_param_is_numeric('0'),
    'plain_digits' => pro_param_is_numeric('123'),
    'pipe_separated_digits' => pro_param_is_numeric('1|2|300'),
    'ampersand_separated_digits' => pro_param_is_numeric('1&2&300'),
    'mixed_separators' => pro_param_is_numeric('1|2&300'),
    'equals_prefixed_digits' => pro_param_is_numeric('=123'),
    'equals_prefixed_list' => pro_param_is_numeric('=1|2&300'),
    'not_prefixed_digits' => pro_param_is_numeric('not 123'),
    'uppercase_not_prefixed_digits' => pro_param_is_numeric('NOT 123'),
    'not_prefixed_mixed_list' => pro_param_is_numeric('not 1|2&300'),
    'not_prefix_with_tab_whitespace' => pro_param_is_numeric("not\t123"),
    'trailing_pipe_separator' => pro_param_is_numeric('1|'),
    'trailing_ampersand_separator' => pro_param_is_numeric('not 1&'),
];
$rejected = [
    'empty_string' => pro_param_is_numeric(''),
    'letters_only' => pro_param_is_numeric('alpha'),
    'digits_with_letters' => pro_param_is_numeric('123alpha'),
    'bare_not' => pro_param_is_numeric('not'),
    'not_without_whitespace' => pro_param_is_numeric('not123'),
    'not_with_multiple_spaces' => pro_param_is_numeric('not  123'),
    'negative_number' => pro_param_is_numeric('-1'),
    'not_negative_number' => pro_param_is_numeric('not -1'),
    'decimal_number' => pro_param_is_numeric('1.5'),
    'scientific_notation' => pro_param_is_numeric('1e3'),
    'comma_separated_digits' => pro_param_is_numeric('1,2'),
    'repeated_pipe_separator' => pro_param_is_numeric('1||2'),
    'repeated_ampersand_separator' => pro_param_is_numeric('1&&2'),
    'leading_pipe_separator' => pro_param_is_numeric('|1'),
    'leading_ampersand_separator' => pro_param_is_numeric('&1'),
    'separator_with_leading_space' => pro_param_is_numeric('1 |2'),
    'separator_with_trailing_space' => pro_param_is_numeric('1| 2'),
    'equals_with_whitespace' => pro_param_is_numeric('= 1'),
    'double_equals' => pro_param_is_numeric('==1'),
    'not_with_equals' => pro_param_is_numeric('not =1'),
];

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['pro_param_is_numeric'] ?? ['branches' => [], 'paths' => []];
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
    'accepted' => $accepted,
    'rejected' => $rejected,
], JSON_PRETTY_PRINT));
