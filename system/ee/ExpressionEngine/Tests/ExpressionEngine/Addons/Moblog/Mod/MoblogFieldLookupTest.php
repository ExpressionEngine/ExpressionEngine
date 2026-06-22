<?php

use PHPUnit\Framework\TestCase;

require_once PATH_ADDONS . 'moblog/mod.moblog.php';

class MoblogFieldLookupTest extends TestCase
{
    private $db;
    private $config;
    private $moblog;

    protected function setUp(): void
    {
        parent::setUp();

        ee()->resetMocks();

        $this->db = new MoblogFieldLookupDbMock();
        $this->config = new FakeConfig();
        $this->moblog = new Moblog();

        ee()->setMock('db', $this->db);
        ee()->setMock('config', $this->config);
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();

        parent::tearDown();
    }

    public function testFieldLookupUsesBindsForFieldName()
    {
        $fieldName = 'body" OR 1=1 -- ';
        $this->db->rows = array(array('field_id' => '3'));

        $result = $this->invokeFieldLookup($fieldName);

        $this->assertSame('3', $result->row('field_id'));
        $this->assertCount(1, $this->db->queries);
        $this->assertSame(
            'SELECT field_id FROM exp_channel_fields WHERE (field_name = ? OR field_label = ?) AND field_type = ?',
            $this->db->queries[0]['sql']
        );
        $this->assertStringNotContainsString($fieldName, $this->db->queries[0]['sql']);
        $this->assertSame(array($fieldName, $fieldName, 'textarea'), $this->db->queries[0]['binds']);
    }

    public function testFieldLookupCanConstrainFieldGroup()
    {
        $fieldName = 'summary" OR 1=1 -- ';

        $this->invokeFieldLookup($fieldName, 'field_id, field_fmt', '7');

        $this->assertSame(
            'SELECT field_id, field_fmt FROM exp_channel_fields WHERE group_id = ? AND (field_name = ? OR field_label = ?) AND field_type = ?',
            $this->db->queries[0]['sql']
        );
        $this->assertStringNotContainsString($fieldName, $this->db->queries[0]['sql']);
        $this->assertSame(array('7', $fieldName, $fieldName, 'textarea'), $this->db->queries[0]['binds']);
    }

    public function testFieldLookupHonorsAllowNontextareasConfig()
    {
        $fieldName = 'image" OR 1=1 -- ';
        $this->config->setItem('moblog_allow_nontextareas', 'y');

        $this->invokeFieldLookup($fieldName);

        $this->assertSame(
            'SELECT field_id FROM exp_channel_fields WHERE (field_name = ? OR field_label = ?)',
            $this->db->queries[0]['sql']
        );
        $this->assertStringNotContainsString($fieldName, $this->db->queries[0]['sql']);
        $this->assertSame(array($fieldName, $fieldName), $this->db->queries[0]['binds']);
    }

    private function invokeFieldLookup($fieldName, $select = 'field_id', $groupId = null)
    {
        $method = new ReflectionMethod($this->moblog, 'getFieldByNameOrLabel');
        TestReflectionHelper::makeMethodAccessible($method);

        return $method->invoke($this->moblog, $fieldName, $select, $groupId);
    }
}

class MoblogFieldLookupDbMock
{
    public $queries = array();
    public $rows = array();

    public function dbprefix($table)
    {
        return 'exp_' . $table;
    }

    public function query($sql, $binds = false)
    {
        $this->queries[] = array(
            'sql' => $sql,
            'binds' => $binds
        );

        return new eeDbResultMock($this->rows);
    }
}
