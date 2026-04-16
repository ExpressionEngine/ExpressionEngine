<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;

class EE_TemplateExitPathCoverageTest extends TestCase
{
    private const SCENARIO_FIND_LAYOUT_TOO_LATE = 'find_layout_too_late_exit';
    private const SCENARIO_FIND_LAYOUT_MULTIPLE = 'find_layout_multiple_exit';
    private const SCENARIO_SHOW_404 = 'show_404_exit';
    private const SCENARIO_SUB_TEMPLATES_LOOP = 'sub_templates_loop_exit';
    private const SCENARIO_PARSE_TEMPLATE_URI = 'parse_template_uri_exit';

    public function testExitLineCoverageForFindLayoutTooLate()
    {
        $this->runScenarioAndAppendCoverage(self::SCENARIO_FIND_LAYOUT_TOO_LATE, [882]);
    }

    public function testExitLineCoverageForFindLayoutMultiple()
    {
        $this->runScenarioAndAppendCoverage(self::SCENARIO_FIND_LAYOUT_MULTIPLE, [896]);
    }

    public function testExitLineCoverageForShow404()
    {
        $this->runScenarioAndAppendCoverage(self::SCENARIO_SHOW_404, [2428]);
    }

    public function testExitLineCoverageForProcessSubTemplatesLoopPrevention()
    {
        $this->runScenarioAndAppendCoverage(self::SCENARIO_SUB_TEMPLATES_LOOP, [1162]);
    }

    public function testExitLineCoverageForParseTemplateUriPostInstallMessageBranch()
    {
        $this->runScenarioAndAppendCoverage(self::SCENARIO_PARSE_TEMPLATE_URI, [2222]);
    }

    private function runScenarioAndAppendCoverage(string $scenario, array $expectedLines): void
    {
        if (!function_exists('xdebug_start_code_coverage')) {
            $this->markTestSkipped('Xdebug is required for exit-path coverage scenarios.');
        }

        $scriptPath = tempnam(sys_get_temp_dir(), 'ee-template-exit-child-');
        $coveragePath = tempnam(sys_get_temp_dir(), 'ee-template-exit-cov-');

        if ($scriptPath === false || $coveragePath === false) {
            $this->fail('Unable to create temporary files for exit-path coverage test.');
        }

        file_put_contents($scriptPath, $this->childScript());

        $testsRoot = realpath(dirname(__DIR__, 3)) ?: dirname(__DIR__, 3);

        $command = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg($scriptPath)
            . ' ' . escapeshellarg($scenario)
            . ' ' . escapeshellarg($coveragePath)
            . ' ' . escapeshellarg($testsRoot);

        exec($command, $output, $exitCode);

        $this->assertSame(
            0,
            $exitCode,
            "Exit coverage child process failed for scenario {$scenario}:\n" . implode("\n", $output)
        );
        $this->assertFileExists($coveragePath);

        $raw = @unserialize((string) file_get_contents($coveragePath));
        $this->assertIsArray($raw, 'Coverage payload from child process must be an array.');

        $templateCoverage = $this->templateCoverageOnly($raw);
        $this->assertNotEmpty($templateCoverage, 'Expected Template.php coverage data from child process.');

        foreach ($expectedLines as $line) {
            $covered = false;
            foreach ($templateCoverage as $file => $lines) {
                if (isset($lines[$line]) && $lines[$line] > 0) {
                    $covered = true;
                    break;
                }
            }

            $this->assertTrue($covered, "Expected line {$line} to be covered for scenario {$scenario}.");
        }

        $result = $this->getTestResultObject();
        if ($result !== null && $result->getCodeCoverage() !== null) {
            $result->getCodeCoverage()->append(
                RawCodeCoverageData::fromXdebugWithoutPathCoverage($templateCoverage),
                static::class . '::' . $scenario,
                true
            );
        }

        @unlink($scriptPath);
        @unlink($coveragePath);
    }

    private function templateCoverageOnly(array $raw): array
    {
        $filtered = [];
        $suffix = DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'ee' . DIRECTORY_SEPARATOR . 'legacy' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'Template.php';

        foreach ($raw as $file => $lines) {
            if (!is_string($file) || !is_array($lines)) {
                continue;
            }

            if (substr($file, -strlen($suffix)) === $suffix) {
                $filtered[$file] = $lines;
            }
        }

        return $filtered;
    }

    private function childScript(): string
    {
        return <<<'PHP'
<?php
$scenario = $argv[1] ?? '';
$coveragePath = $argv[2] ?? '';
$rootArg = $argv[3] ?? getcwd();

$testsRoot = rtrim((string) $rootArg, '/\\');
if (substr($testsRoot, -strlen('/system/ee/ExpressionEngine/Tests')) !== '/system/ee/ExpressionEngine/Tests') {
    $candidate = $testsRoot . '/system/ee/ExpressionEngine/Tests';
    if (is_dir($candidate)) {
        $testsRoot = $candidate;
    }
}

if (!is_file($testsRoot . '/bootstrap.php')) {
    fwrite(STDERR, "Unable to locate test bootstrap at {$testsRoot}/bootstrap.php\n");
    exit(255);
}

require_once $testsRoot . '/bootstrap.php';
require_once $testsRoot . '/ExpressionEngine/legacy/EE_Template/test_helpers.php';
require_once SYSPATH . 'ee/legacy/libraries/Template.php';

if (!function_exists('strip_quotes')) {
    function strip_quotes($str)
    {
        return trim($str, "\"'");
    }
}

xdebug_start_code_coverage();

register_shutdown_function(function () use ($coveragePath) {
    @file_put_contents($coveragePath, serialize(xdebug_get_code_coverage()));
});

$config = new class extends FakeConfig {
    public function site_url()
    {
        return 'https://example.com/';
    }
};
$config->items = [
    'debug' => 0,
    'site_id' => 1,
    'site_url' => 'https://example.com/',
    'site_name' => 'Example Site',
    'site_label' => 'Example Site',
    'site_short_name' => 'default_site',
    'site_description' => 'Description',
    'site_index' => '',
    'webmaster_email' => 'admin@example.com',
    'template_loop_prevention' => 'y',
    'site_404' => 'errors/not_found',
    'enable_template_routes' => 'n',
    'strict_urls' => 'n',
    'save_tmpl_files' => 'n',
];
$config->_global_vars = [];
ee()->setMock('config', $config);

ee()->setMock('lang', new class {
    public function line($key)
    {
        return $key;
    }
});

ee()->setMock('output', new class {
    public $out_type;

    public function fatal_error($message)
    {
    }

    public function set_status_header($code, $text = '')
    {
    }

    public function set_output($output)
    {
        return $this;
    }

    public function _display()
    {
    }
});

switch ($scenario) {
    case 'find_layout_too_late_exit':
        $template = new EE_Template();
        $template->template = '{exp:channel:entries}{layout="layouts/main"}';
        $method = new ReflectionMethod($template, '_find_layout');
        $method->setAccessible(true);
        $method->invoke($template);
        break;

    case 'find_layout_multiple_exit':
        $template = new EE_Template();
        $template->template = '{layout="layouts/main"}{layout="layouts/secondary"}';
        $method = new ReflectionMethod($template, '_find_layout');
        $method->setAccessible(true);
        $method->invoke($template);
        break;

    case 'show_404_exit':
        $template = new class extends EE_Template {
            public function fetch_and_parse($template_group = '', $template = '', $is_embed = false, $site_id = '', $is_layout = false)
            {
                $this->final_template = 'raw-404';

                return true;
            }

            public function parse_globals($str)
            {
                return 'parsed-404';
            }
        };
        $template->show_404();
        break;

    case 'sub_templates_loop_exit':
        $template = new EE_Template();
        $template->templates_sofar = '|1:foo/bar||1:foo/bar|';
        $template->process_sub_templates('A{embed="foo/bar"}B');
        break;

    case 'parse_template_uri_exit':
        ee()->setMock('uri', new class {
            public $segments = [];
            public $query_string = '';
            public $uri_string = '';

            public function segment($n)
            {
                return false;
            }
        });

        ee()->setMock('db', new class {
            public function select($fields = '*')
            {
                return $this;
            }

            public function from($table)
            {
                return $this;
            }

            public function where($field, $value = null)
            {
                return $this;
            }

            public function get($table = null)
            {
                return new eeDbResultMock([['template_data' => 'post-install-message']]);
            }

            public function order_by($field, $direction = '')
            {
                return $this;
            }
        });

        $template = new class extends EE_Template {
            public function fetch_template($template_group, $template, $show_default = true, $site_id = '', $is_layout = false)
            {
                return false;
            }

            public function parse(&$str, $is_embed = false, $site_id = '', $is_layout = false)
            {
                $this->final_template = 'parsed-post-install-message';
            }

            public function parse_globals($str)
            {
                return $str;
            }
        };

        $template->parse_template_uri();
        break;

    default:
        exit(2);
}
PHP;
    }
}
