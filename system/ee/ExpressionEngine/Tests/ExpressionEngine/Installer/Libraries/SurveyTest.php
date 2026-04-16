<?php

namespace {
    if (! defined('CURLOPT_SSL_VERIFYPEER')) {
        define('CURLOPT_SSL_VERIFYPEER', 64);
    }
    if (! defined('CURLOPT_SSL_VERIFYHOST')) {
        define('CURLOPT_SSL_VERIFYHOST', 81);
    }
    if (! defined('CURLOPT_CONNECTTIMEOUT')) {
        define('CURLOPT_CONNECTTIMEOUT', 78);
    }
    if (! defined('CURLOPT_TIMEOUT')) {
        define('CURLOPT_TIMEOUT', 13);
    }
    if (! defined('CURLOPT_RETURNTRANSFER')) {
        define('CURLOPT_RETURNTRANSFER', 19913);
    }
    if (! defined('CURLOPT_URL')) {
        define('CURLOPT_URL', 10002);
    }
    if (! defined('CURLOPT_POST')) {
        define('CURLOPT_POST', 47);
    }
    if (! defined('CURLOPT_POSTFIELDS')) {
        define('CURLOPT_POSTFIELDS', 10015);
    }

    if (
        getenv('SURVEY_STUB_MYSQLI') === '1'
        && PHP_VERSION_ID >= 80000
        && ! function_exists('mysqli_get_server_info')
    ) {
        function mysqli_get_server_info($conn)
        {
            return '8.0.36-log';
        }
    }

    if (! function_exists('directory_map')) {
        function directory_map($source_dir, $directory_depth = 0)
        {
            return ['sample_addon'];
        }
    }

    if (! class_exists('SurveyCurlStubInstaller')) {
        class SurveyCurlStubInstaller
        {
            public static function install(): void
            {
                if (
                    getenv('SURVEY_STUB_CURL') !== '1'
                    || PHP_VERSION_ID < 80000
                    || function_exists('curl_init')
                ) {
                    return;
                }

                eval('function curl_init(){$GLOBALS["survey_curl_options"]=[]; return "curl-handle";}');
                eval('function curl_setopt($ch,$option,$value){$GLOBALS["survey_curl_options"][$option]=$value; return true;}');
                eval('function curl_exec($ch){$GLOBALS["survey_curl_executed"]=true; return "ok";}');
                eval('function curl_close($ch){$GLOBALS["survey_curl_closed"]=true; return null;}');
            }
        }
    }
}

namespace ExpressionEngine\Tests\ExpressionEngine\Installer\Libraries {

use PHPUnit\Framework\TestCase;

require_once SYSPATH . 'ee/installer/libraries/Survey.php';

class SurveyTestDouble extends \Survey
{
    public $anonData = [];

    public function fetch_anon_server_data()
    {
        return $this->anonData;
    }
}

class SurveyTest extends TestCase
{
    private $postBackup = [];
    private $serverBackup = [];

    protected function setUp(): void
    {
        ee()->resetMocks();
        $this->postBackup = $_POST;
        $this->serverBackup = $_SERVER;
        $GLOBALS['survey_curl_options'] = [];
        $GLOBALS['survey_curl_executed'] = false;
        $GLOBALS['survey_curl_closed'] = false;
    }

    protected function tearDown(): void
    {
        $_POST = $this->postBackup;
        $_SERVER = $this->serverBackup;
        ee()->resetMocks();
    }

    public function testFetchAnonServerDataReturnsExpectedPayload()
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('mysqli_get_server_info stubbing is unavailable on PHP 7.x in this harness.');
        }

        if (getenv('SURVEY_STUB_MYSQLI') !== '1') {
            $this->markTestSkipped('Requires SURVEY_STUB_MYSQLI=1 for deterministic mysqli stubbing.');
        }

        $prefs = base64_encode(serialize([
            'site_url' => 'https://example.test/',
            'force_query_string' => 'n',
        ]));

        $db = new class($prefs) {
            public $selectCalls = [];
            public $getWhereCalls = [];
            public $conn_id = 'conn';
            private $prefs;

            public function __construct($prefs)
            {
                $this->prefs = $prefs;
            }

            public function select($fields)
            {
                $this->selectCalls[] = $fields;
            }

            public function get_where($table, $where)
            {
                $this->getWhereCalls[] = [$table, $where];

                return new class($this->prefs) {
                    private $prefs;

                    public function __construct($prefs)
                    {
                        $this->prefs = $prefs;
                    }

                    public function num_rows()
                    {
                        return 1;
                    }

                    public function row($column)
                    {
                        return $this->prefs;
                    }
                };
            }
        };

        $load = new class {
            public $helperCalls = [];

            public function helper($name)
            {
                $this->helperCalls[] = $name;
            }
        };

        $config = new class {
            public function item($key)
            {
                if ($key === 'forum_is_installed') {
                    return 'y';
                }

                if ($key === 'multiple_sites_enabled') {
                    return 'n';
                }

                return null;
            }
        };

        ee()->setMock('db', $db);
        ee()->setMock('load', $load);
        ee()->setMock('config', $config);

        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4.58 (Unix)';

        $survey = new \Survey();
        $data = $survey->fetch_anon_server_data();

        $this->assertSame(['site_system_preferences'], $db->selectCalls);
        $this->assertSame([['sites', ['site_id' => 1]]], $db->getWhereCalls);
        $this->assertSame(['directory'], $load->helperCalls);
        $this->assertSame(md5('https://example.test/'), $data['anon_id']);
        $this->assertSame('Unix', $data['os']);
        $this->assertSame('Apache/2.4.58 ', $data['server_software']);
        $this->assertSame('8.0.36', $data['mysql_version']);
        $this->assertSame('y', $data['path_info_support']);
        $this->assertSame(json_encode(['sample_addon']), $data['addons']);
        $this->assertSame('y', $data['forums']);
        $this->assertSame('n', $data['msm']);
    }

    public function testSendSurveyWithoutAnonymousServerDataUsesSerializedPostHashAndReturnsWhenCurlIsUnavailable()
    {
        if (getenv('SURVEY_STUB_CURL') !== '1') {
            $this->markTestSkipped('Requires SURVEY_STUB_CURL=1 for deterministic curl stubbing.');
        }

        $_POST = [
            'participate_in_survey' => 'y',
            'send_anonymous_server_data' => 'n',
            'submit' => 'Submit',
            'preferred_editor' => 'Code',
        ];

        $originalPost = $_POST;

        $survey = new SurveyTestDouble();
        $survey->send_survey('7.6.1');

        $this->assertFalse($GLOBALS['survey_curl_executed']);
        $this->assertFalse($GLOBALS['survey_curl_closed']);
        $this->assertSame([], $GLOBALS['survey_curl_options']);
        $this->assertArrayNotHasKey('participate_in_survey', $_POST);
        $this->assertArrayNotHasKey('send_anonymous_server_data', $_POST);
        $this->assertArrayNotHasKey('submit', $_POST);
        $this->assertNotEmpty(md5(serialize($originalPost)));
    }

    /**
     * @depends testSendSurveyWithoutAnonymousServerDataUsesSerializedPostHashAndReturnsWhenCurlIsUnavailable
     */
    public function testSendSurveyWithAnonymousServerDataBuildsAndSendsPostPayload()
    {
        if (getenv('SURVEY_STUB_CURL') !== '1') {
            $this->markTestSkipped('Requires SURVEY_STUB_CURL=1 for deterministic curl stubbing.');
        }

        \SurveyCurlStubInstaller::install();

        $_POST = [
            'participate_in_survey' => 'y',
            'send_anonymous_server_data' => 'y',
            'submit' => 'Submit',
            'favorite_feature' => 'templates',
            'note' => 'it\\\'s great',
        ];

        $survey = new SurveyTestDouble();
        $survey->anonData = ['anon_id' => 'anon-1', 'os' => 'Unix'];
        $survey->send_survey('7.6.0');

        $payload = $GLOBALS['survey_curl_options'][CURLOPT_POSTFIELDS] ?? '';

        $this->assertTrue($GLOBALS['survey_curl_executed']);
        $this->assertTrue($GLOBALS['survey_curl_closed']);
        $this->assertStringContainsString('&anon_id=anon-1', $payload);
        $this->assertStringContainsString('&favorite_feature=templates', $payload);
        $this->assertStringContainsString('&note=it%27s+great', $payload);
        $this->assertStringContainsString('&ee_version=7.6.0', $payload);
        $this->assertArrayNotHasKey('participate_in_survey', $_POST);
        $this->assertArrayNotHasKey('send_anonymous_server_data', $_POST);
        $this->assertArrayNotHasKey('submit', $_POST);
    }
}
}
