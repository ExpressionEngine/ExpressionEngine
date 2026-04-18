<?php

require __DIR__ . '/../bootstrap.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$captured = (object) [
    'queries' => [],
    'result_array_calls' => 0,
];
$target = realpath(PATH_ADDONS . 'structure/sql.structure.php');
$linesToTrack = [1978, 1979, 1982, 1983, 1985, 1989, 1990, 1991, 1994];
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

ee()->setMock('db', new class($captured) {
    private $captured;
    private $responses;

    /**
     * Prepare queued responses for the supported result-shape paths.
     *
     * @param object $captured
     * @return void
     */
    public function __construct(object $captured)
    {
        $this->captured = $captured;
        $this->responses = [
            [
                'num_rows' => 2,
                'rows' => [
                    ['entry_id' => 101],
                    ['entry_id' => 205],
                ],
            ],
            [
                'num_rows' => 0,
                'rows' => [],
            ],
        ];
    }

    /**
     * Return the next queued query result for the method under test.
     *
     * @param string $sql
     * @return object
     */
    public function query($sql): object
    {
        $this->captured->queries[] = $sql;
        $response = array_shift($this->responses);

        return new class($this->captured, $response) {
            private $captured;
            private $response;
            public $num_rows;

            /**
             * Store the queued result payload.
             *
             * @param object $captured
             * @param array $response
             * @return void
             */
            public function __construct(object $captured, array $response)
            {
                $this->captured = $captured;
                $this->response = $response;
                $this->num_rows = $response['num_rows'];
            }

            /**
             * Return the configured row count.
             *
             * @return int
             */
            public function num_rows(): int
            {
                return $this->num_rows;
            }

            /**
             * Return the configured rows and record hydration.
             *
             * @return array
             */
            public function result_array(): array
            {
                $this->captured->result_array_calls++;

                return $this->response['rows'];
            }
        };
    }
});

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
$sql->site_id = 7;
$sql->cache = [];

$invalidResult = $sql->get_entries_by_channel('bad-channel');
$numericStringResult = $sql->get_entries_by_channel('4');
$emptyResult = $sql->get_entries_by_channel(0);

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['Sql_structure->get_entries_by_channel'] ?? ['branches' => [], 'paths' => []];
$coveredLines = [];

foreach ($linesToTrack as $line) {
    $coveredLines[$line] = (($fileCoverage['lines'][$line] ?? 0) > 0);
}

$coveredPaths = [];

foreach (($functionCoverage['paths'] ?? []) as $index => $path) {
    $coveredPaths[$index] = (($path['hit'] ?? 0) > 0);
}

$linePercentage = count($coveredLines) > 0
    ? count(array_filter($coveredLines)) / count($coveredLines) * 100
    : 0.0;
$branchPercentage = count($coveredPaths) > 0
    ? count(array_filter($coveredPaths)) / count($coveredPaths) * 100
    : 0.0;

file_put_contents($outputFile, json_encode([
    'real_module_path' => $target,
    'queries' => $captured->queries,
    'result_array_calls' => $captured->result_array_calls,
    'invalid_result' => $invalidResult,
    'numeric_string_result' => $numericStringResult,
    'empty_result' => $emptyResult,
    'xdebug_available' => $xdebugAvailable,
    'lines' => array_intersect_key($fileCoverage['lines'] ?? [], array_flip($linesToTrack)),
    'branches' => $functionCoverage['branches'] ?? [],
    'paths' => $functionCoverage['paths'] ?? [],
    'line_percentage' => $linePercentage,
    'branch_percentage' => $branchPercentage,
    'covered_lines' => $coveredLines,
    'covered_paths' => $coveredPaths,
    'uncovered_lines' => array_values(array_keys(array_filter($coveredLines, function ($covered) {
        return ! $covered;
    }))),
    'uncovered_paths' => array_values(array_keys(array_filter($coveredPaths, function ($covered) {
        return ! $covered;
    }))),
], JSON_PRETTY_PRINT));
