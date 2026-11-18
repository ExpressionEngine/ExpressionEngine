<?php

if (! class_exists('CI_DB_utility')) {
    class CI_DB_utility
    {
        public $db;
    }
}

require_once SYSPATH . 'ee/legacy/database/drivers/mysqli/mysqli_utility.php';

use PHPUnit\Framework\TestCase;

class MysqliUtilityTest extends TestCase
{
    private $db;
    private $utility;

    protected function setUp(): void
    {
        $this->db = new MysqliUtilityDbStub();
        $this->utility = new MysqliUtilityTestable($this->db);
    }

    public function testListDatabasesReturnsShowDatabasesSql(): void
    {
        $this->assertSame('SHOW DATABASES', $this->utility->_list_databases());
    }

    public function testOptimizeTableEscapesIdentifiers(): void
    {
        $sql = $this->utility->_optimize_table('exp_members');

        $this->assertSame('OPTIMIZE TABLE [exp_members]', $sql);
        $this->assertSame(['exp_members'], $this->db->escapedValues);
    }

    public function testRepairTableEscapesIdentifiers(): void
    {
        $sql = $this->utility->_repair_table('exp_channel_titles');

        $this->assertSame('REPAIR TABLE [exp_channel_titles]', $sql);
        $this->assertSame(['exp_channel_titles'], $this->db->escapedValues);
    }

    public function testBackupReturnsDatabaseDisplayError(): void
    {
        $result = $this->utility->_backup(['format' => 'gzip']);

        $this->assertSame('display_error:db_unsuported_feature', $result);
        $this->assertSame(['db_unsuported_feature'], $this->db->displayErrorCalls);
    }
}

class MysqliUtilityTestable extends CI_DB_mysqli_utility
{
    public function __construct($db)
    {
        $this->db = $db;
    }
}

class MysqliUtilityDbStub
{
    public $escapedValues = [];
    public $displayErrorCalls = [];

    public function escape_identifiers($value)
    {
        $this->escapedValues[] = $value;

        return '[' . $value . ']';
    }

    public function display_error($message)
    {
        $this->displayErrorCalls[] = $message;

        return 'display_error:' . $message;
    }
}
