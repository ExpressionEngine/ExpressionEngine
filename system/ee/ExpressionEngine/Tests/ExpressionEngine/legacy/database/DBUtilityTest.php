<?php

if (! function_exists('show_error')) {
    function show_error($message)
    {
        throw new RuntimeException($message);
    }
}

if (! function_exists('log_message')) {
    function log_message($level, $message)
    {
        return;
    }
}

if (! function_exists('xml_convert')) {
    function xml_convert($value)
    {
        return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], (string) $value);
    }
}

require_once SYSPATH . 'ee/legacy/database/DB_forge.php';
require_once SYSPATH . 'ee/legacy/database/DB_utility.php';

use PHPUnit\Framework\TestCase;

class DBUtilityTest extends TestCase
{
    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testListDatabasesCachesFirstQueryResult(): void
    {
        $db = new DBUtilityDbStub();
        $db->queryMap = [
            'SHOW DATABASES' => new DBUtilityQueryResultStub(2, [
                ['Database' => 'ee_main'],
                ['Database' => 'ee_secondary'],
            ]),
        ];

        $utility = new DBUtilityTestable($db);
        $first = $utility->list_databases();
        $second = $utility->list_databases();

        $this->assertSame(['ee_main', 'ee_secondary'], $first);
        $this->assertSame($first, $second);
        $this->assertSame(['SHOW DATABASES'], $db->queries);
    }

    public function testDatabaseExistsUsesDriverSpecificMethodWhenProvided(): void
    {
        $utility = new DBUtilityWithDatabaseExists(new DBUtilityDbStub());

        $this->assertTrue($utility->database_exists('exists_here'));
        $this->assertFalse($utility->database_exists('missing_here'));
    }

    public function testDatabaseExistsFallsBackToListDatabases(): void
    {
        $db = new DBUtilityDbStub();
        $db->queryMap = [
            'SHOW DATABASES' => new DBUtilityQueryResultStub(1, [
                ['Database' => 'ee_main'],
            ]),
        ];
        $utility = new DBUtilityTestable($db);

        $this->assertTrue($utility->database_exists('ee_main'));
        $this->assertFalse($utility->database_exists('other'));
    }

    public function testOptimizeTableThrowsWhenDriverReturnsBooleanSql(): void
    {
        $utility = new DBUtilityTestable(new DBUtilityDbStub());
        $utility->optimizeSql = false;

        $this->expectException(RuntimeException::class);
        $utility->optimize_table('exp_members');
    }

    public function testOptimizeTableReturnsFirstResultRow(): void
    {
        $db = new DBUtilityDbStub();
        $db->queryMap = [
            'OPTIMIZE exp_members' => new DBUtilityQueryResultStub(1, [
                ['Table' => 'ee.exp_members', 'Msg_type' => 'status', 'Msg_text' => 'OK'],
            ]),
        ];
        $utility = new DBUtilityTestable($db);

        $result = $utility->optimize_table('exp_members');

        $this->assertSame(['Table' => 'ee.exp_members', 'Msg_type' => 'status', 'Msg_text' => 'OK'], $result);
    }

    public function testOptimizeDatabaseReturnsBooleanWhenSqlGenerationFails(): void
    {
        $db = new DBUtilityDbStub();
        $db->tables = ['exp_members'];
        $utility = new DBUtilityTestable($db);
        $utility->optimizeSql = false;

        $this->assertFalse($utility->optimize_database());
    }

    public function testOptimizeDatabaseBuildsPerTableResultMap(): void
    {
        $db = new DBUtilityDbStub();
        $db->database = 'ee_main';
        $db->tables = ['exp_members', 'exp_titles'];
        $db->queryMap = [
            'OPTIMIZE exp_members' => new DBUtilityQueryResultStub(1, [
                ['Table' => 'ee_main.exp_members', 'Msg_type' => 'status', 'Msg_text' => 'OK'],
            ]),
            'OPTIMIZE exp_titles' => new DBUtilityQueryResultStub(1, [
                ['Table' => 'ee_main.exp_titles', 'Msg_type' => 'status', 'Msg_text' => 'OK'],
            ]),
        ];
        $utility = new DBUtilityTestable($db);

        $result = $utility->optimize_database();

        $this->assertSame(
            [
                'exp_members' => ['Msg_type' => 'status', 'Msg_text' => 'OK'],
                'exp_titles' => ['Msg_type' => 'status', 'Msg_text' => 'OK'],
            ],
            $result
        );
    }

    public function testRepairTableReturnsBooleanOrFirstResultRow(): void
    {
        $utility = new DBUtilityTestable(new DBUtilityDbStub());
        $utility->repairSql = false;
        $this->assertFalse($utility->repair_table('exp_members'));

        $db = new DBUtilityDbStub();
        $db->queryMap = [
            'REPAIR exp_members' => new DBUtilityQueryResultStub(1, [
                ['Table' => 'ee.exp_members', 'Msg_type' => 'status', 'Msg_text' => 'OK'],
            ]),
        ];
        $utility = new DBUtilityTestable($db);

        $this->assertSame(
            ['Table' => 'ee.exp_members', 'Msg_type' => 'status', 'Msg_text' => 'OK'],
            $utility->repair_table('exp_members')
        );
    }

    public function testCsvFromResultValidatesObjectAndBuildsCsv(): void
    {
        $utility = new DBUtilityTestable(new DBUtilityDbStub());

        try {
            $utility->csv_from_result('not-an-object');
            $this->fail('Expected exception');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('valid result object', $exception->getMessage());
        }

        $query = new DBUtilityResultObjectStub(
            ['name', 'note'],
            [
                ['name' => 'alpha', 'note' => 'plain'],
                ['name' => 'beta', 'note' => 'quote "inside"'],
                ['name' => null, 'note' => ''],
            ]
        );

        $csv = $utility->csv_from_result($query, ',', "\n", '"');

        $this->assertSame(
            "\"name\",\"note\",\n\"alpha\",\"plain\",\n\"beta\",\"quote \"\"inside\"\"\",\n\"\",\"\",\n",
            $csv
        );
    }

    public function testXmlFromResultValidatesObjectAndBuildsXmlWithDefaultsAndCustomParams(): void
    {
        $utility = new DBUtilityTestable(new DBUtilityDbStub());

        try {
            $utility->xml_from_result('not-an-object');
            $this->fail('Expected exception');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('valid result object', $exception->getMessage());
        }

        $load = new DBUtilityLoadStub();
        ee()->setMock('load', $load);

        $query = new DBUtilityResultObjectStub(
            ['name'],
            [
                ['name' => 'alpha & beta'],
            ]
        );

        $xmlDefault = $utility->xml_from_result($query);
        $xmlCustom = $utility->xml_from_result($query, [
            'root' => 'rows',
            'element' => 'row',
            'newline' => '',
            'tab' => '',
        ]);

        $this->assertSame(['xml', 'xml'], $load->helpers);
        $this->assertStringContainsString('<root>', $xmlDefault);
        $this->assertStringContainsString('<name>alpha &amp; beta</name>', $xmlDefault);
        $this->assertStringContainsString('<rows><row><name>alpha &amp; beta</name></row></rows>', $xmlCustom);
    }

    public function testBackupSupportsGzipTxtAndZipFormatsAndFilenameNormalization(): void
    {
        $db = new DBUtilityDbStub();
        $db->tables = ['exp_members'];
        $utility = new DBUtilityTestable($db);

        try {
            $utility->backup('exp_members');
        } catch (Throwable $exception) {
            $this->assertStringContainsString('count()', $exception->getMessage());
        }

        $gzip = $utility->backup(['format' => 'gzip', 'tables' => ['exp_members']]);
        $this->assertSame(gzencode('SQLDATA'), $gzip);

        $txt = $utility->backup(['format' => 'txt', 'tables' => ['exp_members']]);
        $this->assertSame('SQLDATA', $txt);

        $txtFromInvalidFormat = $utility->backup(['format' => 'invalid', 'tables' => ['exp_members']]);
        $this->assertSame('SQLDATA', $txtFromInvalidFormat);

        $load = new DBUtilityLoadStub();
        $zip = new DBUtilityZipStub();
        ee()->setMock('load', $load);
        ee()->setMock('zip', $zip);

        $zipData = $utility->backup([
            'format' => 'zip',
            'filename' => 'backup.zip',
            'tables' => ['exp_members'],
        ]);

        $this->assertSame('ZIP_BINARY', $zipData);
        $this->assertSame(['zip'], $load->libraries);
        $this->assertSame([['backup.sql', 'SQLDATA']], $zip->data);
    }

    public function testBackupGeneratesDefaultZipFilenameWhenMissing(): void
    {
        $db = new DBUtilityDbStub();
        $db->database = 'ee_main';
        $db->tables = ['exp_members', 'exp_titles'];
        $utility = new DBUtilityTestable($db);

        $load = new DBUtilityLoadStub();
        $zip = new DBUtilityZipStub();
        ee()->setMock('load', $load);
        ee()->setMock('zip', $zip);

        $utility->backup([
            'format' => 'zip',
            'filename' => '',
            'tables' => [],
        ]);

        $this->assertCount(1, $zip->data);
        $this->assertMatchesRegularExpression('/^ee_main_\\d{4}-\\d{2}-\\d{2}_\\d{2}-\\d{2}\\.sql$/', $zip->data[0][0]);
    }
}

class DBUtilityTestable extends CI_DB_utility
{
    public $optimizeSql = null;
    public $repairSql = null;
    public $backupPayload = 'SQLDATA';
    public $backupPrefs = [];

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function _list_databases()
    {
        return 'SHOW DATABASES';
    }

    public function _optimize_table($table_name)
    {
        if ($this->optimizeSql !== null) {
            return $this->optimizeSql;
        }

        return 'OPTIMIZE ' . $table_name;
    }

    public function _repair_table($table_name)
    {
        if ($this->repairSql !== null) {
            return $this->repairSql;
        }

        return 'REPAIR ' . $table_name;
    }

    public function _backup($prefs = array())
    {
        $this->backupPrefs[] = $prefs;

        return $this->backupPayload;
    }
}

class DBUtilityWithDatabaseExists extends DBUtilityTestable
{
    public function _database_exists($database_name)
    {
        return $database_name === 'exists_here';
    }
}

class DBUtilityDbStub
{
    public $queryMap = [];
    public $queries = [];
    public $tables = [];
    public $database = 'ee';
    public $db_debug = false;
    public $displayErrorCalls = [];

    public function query($sql)
    {
        $this->queries[] = $sql;

        return $this->queryMap[$sql] ?? new DBUtilityQueryResultStub(0, []);
    }

    public function list_tables()
    {
        return $this->tables;
    }

    public function display_error($key)
    {
        $this->displayErrorCalls[] = $key;

        return 'display_error:' . $key;
    }
}

class DBUtilityQueryResultStub
{
    private $numRows;
    private $rows;

    public function __construct(int $numRows, array $rows)
    {
        $this->numRows = $numRows;
        $this->rows = $rows;
    }

    public function num_rows()
    {
        return $this->numRows;
    }

    public function result_array()
    {
        return $this->rows;
    }
}

class DBUtilityResultObjectStub
{
    private $fields;
    private $rows;

    public function __construct(array $fields, array $rows)
    {
        $this->fields = $fields;
        $this->rows = $rows;
    }

    public function list_fields()
    {
        return $this->fields;
    }

    public function result_array()
    {
        return $this->rows;
    }
}

class DBUtilityLoadStub
{
    public $helpers = [];
    public $libraries = [];

    public function helper($name)
    {
        $this->helpers[] = $name;
    }

    public function library($name)
    {
        $this->libraries[] = $name;
    }
}

class DBUtilityZipStub
{
    public $data = [];

    public function add_data($name, $payload)
    {
        $this->data[] = [$name, $payload];
    }

    public function get_zip()
    {
        return 'ZIP_BINARY';
    }
}
