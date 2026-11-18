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
$linesToTrack = [164, 165, 166, 168, 169, 170, 174];
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
                    ['status' => 'open', 'highlight' => '#fff'],
                    ['status' => 'closed', 'highlight' => '#000'],
                ],
            ],
            [
                'num_rows' => 1,
                'rows' => [],
            ],
            [
                'num_rows' => 0,
                'rows' => [
                    ['status' => 'ignored', 'highlight' => '#111'],
                ],
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
            }

            /**
             * Return the configured row count.
             *
             * @return int
             */
            public function num_rows(): int
            {
                return $this->response['num_rows'];
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
$sql->site_id = 1;
$sql->cache = [];

$populatedResult = $sql->get_status_colors();
$positiveEmptyResult = $sql->get_status_colors();
$emptyResult = $sql->get_status_colors();

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['Sql_structure->get_status_colors'] ?? ['branches' => [], 'paths' => []];
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
    'populated_result' => $populatedResult,
    'positive_empty_result' => $positiveEmptyResult,
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
