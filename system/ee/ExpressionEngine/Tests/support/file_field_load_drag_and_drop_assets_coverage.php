<?php

$outputFile = $argv[1] ?? null;
$req = strtoupper((string) ($argv[2] ?? 'CP'));

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

if (! in_array($req, ['CP', 'PAGE'], true)) {
    fwrite(STDERR, "Request mode must be CP or PAGE.\n");
    exit(1);
}

if (! defined('REQ')) {
    define('REQ', $req);
}

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../ExpressionEngine/legacy/Libraries/FileFieldFieldTest.php';

$target = realpath(SYSPATH . 'ee/legacy/libraries/File_field.php');
$method = new ReflectionMethod('File_field', 'loadDragAndDropAssets');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

\ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldFieldTest::setUpBeforeClass();

/**
 * Build upload preference doubles for loadDragAndDropAssets scenarios.
 *
 * @param int $id
 * @param int $siteId
 * @param int $moduleId
 * @param string $name
 * @param array<string|int, string> $dropdown
 * @return object
 */
function build_load_drag_drop_upload_pref(int $id, int $siteId, int $moduleId, string $name, array $dropdown): object
{
    return new class($id, $siteId, $moduleId, $name, $dropdown) {
        /** @var int */
        public $id;

        /** @var int */
        public $site_id;

        /** @var int */
        public $module_id;

        /** @var string */
        public $name;

        /** @var array<string|int, string> */
        private $dropdown;

        /**
         * @param int $id
         * @param int $siteId
         * @param int $moduleId
         * @param string $name
         * @param array<string|int, string> $dropdown
         */
        public function __construct(int $id, int $siteId, int $moduleId, string $name, array $dropdown)
        {
            $this->id = $id;
            $this->site_id = $siteId;
            $this->module_id = $moduleId;
            $this->name = $name;
            $this->dropdown = $dropdown;
        }

        /**
         * @return array<string|int, string>
         */
        public function getDirectoriesDropdown(): array
        {
            return $this->dropdown;
        }
    };
}

/**
 * Install dependency doubles required by loadDragAndDropAssets.
 *
 * @param bool $canAccessFiles
 * @return void
 */
function install_load_drag_drop_collaborators(bool $canAccessFiles): void
{
    ee()->setMock('Permission', new class($canAccessFiles) {
        /** @var bool */
        private $canAccessFiles;

        public function __construct(bool $canAccessFiles)
        {
            $this->canAccessFiles = $canAccessFiles;
        }

        /**
         * @param string $permission
         * @return bool
         */
        public function has(string $permission): bool
        {
            return $this->canAccessFiles;
        }
    });

    ee()->setMock('View/Helpers', new class {
        /**
         * @param array<int|string, mixed> $choices
         * @return array<int|string, mixed>
         */
        public function normalizedChoices(array $choices): array
        {
            return $choices;
        }
    });

    ee()->setMock('CP/URL', new class {
        /**
         * @param string $path
         * @return object
         */
        public function make(string $path): object
        {
            return new class($path) {
                /** @var string */
                private $path;

                public function __construct(string $path)
                {
                    $this->path = $path;
                }

                /**
                 * @return string
                 */
                public function compile(): string
                {
                    return 'compiled://' . $this->path;
                }
            };
        }
    });

    ee()->setMock('CP/FilePicker', new class {
        /**
         * @param string $allowedDirectory
         * @return object
         */
        public function make(string $allowedDirectory): object
        {
            return new class {
                /**
                 * @return object
                 */
                public function getUrl(): object
                {
                    return new class {
                        /**
                         * @return string
                         */
                        public function compile(): string
                        {
                            return 'compiled://filepicker/all';
                        }
                    };
                }
            };
        }
    });
}

/**
 * Run one deterministic loadDragAndDropAssets scenario.
 *
 * @param int $siteId
 * @param string $compatibilityMode
 * @param bool $canAccessFiles
 * @param mixed $uploadPrefs
 * @return void
 */
function run_load_drag_drop_scenario(int $siteId, string $compatibilityMode, bool $canAccessFiles, $uploadPrefs): void
{
    ee()->resetMocks();
    ee()->config->resetConfig();

    ee()->setMock('cp', new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldCpMock());
    ee()->setMock('javascript', new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldJavascriptMock());
    ee()->setMock('lang', new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldLangMock());

    ee()->config->setItem('site_id', $siteId);
    ee()->config->setItem('file_manager_compatibility_mode', $compatibilityMode);

    install_load_drag_drop_collaborators($canAccessFiles);

    $subject = new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldFieldHarness();
    $uploadPrefsProperty = new ReflectionProperty(File_field::class, '_upload_prefs');
    $uploadPrefsProperty->setAccessible(true);
    $uploadPrefsProperty->setValue($subject, $uploadPrefs);

    $subject->loadDragAndDropAssets();
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

if (REQ === 'CP') {
    run_load_drag_drop_scenario(7, 'n', true, [
        build_load_drag_drop_upload_pref(1, 0, 0, 'Global Assets', [11 => 'Global Child']),
        build_load_drag_drop_upload_pref(2, 7, 0, 'Site Assets', [12 => 'Site Child']),
        build_load_drag_drop_upload_pref(3, 9, 0, 'Other Site', [13 => 'Other Child']),
        build_load_drag_drop_upload_pref(4, 7, 2, 'Module Files', [14 => 'Module Child']),
    ]);

    run_load_drag_drop_scenario(7, 'n', false, [
        build_load_drag_drop_upload_pref(8, 7, 0, 'Member Files', [30 => 'Uploads']),
    ]);

    run_load_drag_drop_scenario(7, 'y', true, [
        build_load_drag_drop_upload_pref(10, 7, 0, 'Compatibility Files', [41 => 'Should Not Load']),
    ]);

    run_load_drag_drop_scenario(7, 'n', true, new ArrayObject([]));
}

if (REQ === 'PAGE') {
    run_load_drag_drop_scenario(7, 'n', true, [
        build_load_drag_drop_upload_pref(1, 0, 0, 'Global Assets', [11 => 'Global Child']),
    ]);
}

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['File_field->loadDragAndDropAssets'] ?? ['branches' => [], 'paths' => []];
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
    'req' => REQ,
    'real_module_path' => $target,
    'xdebug_available' => $xdebugAvailable,
    'line_percentage' => $linePercentage,
    'branch_percentage' => $branchPercentage,
    'lines' => $coveredLines,
    'branches' => $functionCoverage['branches'] ?? [],
    'paths' => $functionCoverage['paths'] ?? [],
    'covered_branches' => $coveredBranches,
    'total_branch_edges' => $totalBranches,
    'uncovered_lines' => array_values(array_keys(array_filter($coveredLines, function ($covered) {
        return ! $covered;
    }))),
    'uncovered_branches' => array_values(array_keys(array_filter($coveredBranches, function ($covered) {
        return ! $covered;
    }))),
], JSON_PRETTY_PRINT));
