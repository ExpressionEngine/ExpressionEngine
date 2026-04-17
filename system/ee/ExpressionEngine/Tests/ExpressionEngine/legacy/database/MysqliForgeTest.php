<?php

require_once SYSPATH . 'ee/legacy/database/DB_forge.php';
require_once SYSPATH . 'ee/legacy/database/drivers/mysqli/mysqli_forge.php';

use PHPUnit\Framework\TestCase;

class MysqliForgeTest extends TestCase
{
    private $db;
    private $forge;

    protected function setUp(): void
    {
        $this->db = new MysqliForgeDbStub();
        $this->forge = new MysqliForgeTestable($this->db);
    }

    public function testCreateAndDropDatabaseQueries(): void
    {
        $this->assertSame('CREATE DATABASE sample_db', $this->forge->_create_database('sample_db'));
        $this->assertSame('DROP DATABASE sample_db', $this->forge->_drop_database('sample_db'));
    }

    public function testProcessFieldsHandlesNumericAndAttributeDrivenDefinitions(): void
    {
        $fields = [
            0 => '`raw` INT NOT NULL',
            'created_at' => [
                'TYPE' => 'datetime',
                'DEFAULT' => 'CURRENT_TIMESTAMP',
                'NULL' => false,
            ],
            'deleted_at' => [
                'TYPE' => 'timestamp',
                'DEFAULT' => null,
                'NULL' => true,
            ],
            'title' => [
                'NAME' => 'headline',
                'TYPE' => 'varchar',
                'CONSTRAINT' => 100,
                'UNSIGNED' => true,
                'DEFAULT' => 'news',
                'AUTO_INCREMENT' => true,
            ],
        ];

        $sql = $this->forge->_process_fields($fields);

        $this->assertStringContainsString('`raw` INT NOT NULL,', $sql);
        $this->assertStringContainsString('<created_at> datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,', $sql);
        $this->assertStringContainsString('<deleted_at> timestamp NULL DEFAULT NULL,', $sql);
        $this->assertStringContainsString("<title> <headline>  varchar(100) UNSIGNED DEFAULT 'news' AUTO_INCREMENT", $sql);
    }

    public function testCreateTableIncludesPrimaryAndRegularKeys(): void
    {
        $fields = [
            'id' => ['TYPE' => 'int'],
            'title' => ['TYPE' => 'varchar', 'CONSTRAINT' => 255],
        ];

        $sql = $this->forge->_create_table(
            'exp_news',
            $fields,
            ['id'],
            [['id', 'title'], 'title'],
            true
        );

        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS [exp_news] (', $sql);
        $this->assertStringContainsString('PRIMARY KEY <id> (<id>)', $sql);
        $this->assertStringContainsString('KEY <id_title> (<id>, <title>)', $sql);
        $this->assertStringContainsString('KEY <title> (<title>)', $sql);
        $this->assertStringContainsString('DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;', $sql);
    }

    public function testCreateTableWithoutIfNotExistsAndKeys(): void
    {
        $sql = $this->forge->_create_table(
            'exp_simple',
            ['id' => ['TYPE' => 'int']],
            [],
            [],
            false
        );

        $this->assertStringStartsWith('CREATE TABLE [exp_simple] (', $sql);
        $this->assertStringNotContainsString('PRIMARY KEY', $sql);
        $this->assertStringNotContainsString("\n\tKEY ", $sql);
    }

    public function testDropTableBuildsSqlWithEscapedIdentifier(): void
    {
        $sql = $this->forge->_drop_table('exp_archive');

        $this->assertSame('DROP TABLE IF EXISTS [exp_archive]', $sql);
    }

    public function testAlterTableHandlesDropBatchDropSingleAndAfterField(): void
    {
        $dropBatch = $this->forge->_alter_table('DROP', 'exp_news', ['title', 'summary']);
        $dropSingle = $this->forge->_alter_table('DROP', 'exp_news', 'summary');
        $addField = $this->forge->_alter_table(
            'ADD',
            'exp_news',
            ['summary' => ['TYPE' => 'varchar', 'CONSTRAINT' => 255]],
            'title'
        );

        $this->assertSame(
            "ALTER TABLE <exp_news> DROP <title>, \n\tDROP <summary>",
            $dropBatch
        );
        $this->assertSame('ALTER TABLE <exp_news> DROP <summary>', $dropSingle);
        $this->assertStringContainsString('ALTER TABLE <exp_news> ADD', $addField);
        $this->assertStringContainsString('AFTER <title>', $addField);
    }

    public function testRenameTableBuildsSql(): void
    {
        $sql = $this->forge->_rename_table('exp_old', 'exp_new');

        $this->assertSame('ALTER TABLE <exp_old> RENAME TO <exp_new>', $sql);
    }
}

class MysqliForgeTestable extends CI_DB_mysqli_forge
{
    public function __construct($db)
    {
        $this->db = $db;
    }
}

class MysqliForgeDbStub
{
    public $char_set = 'utf8mb4';
    public $dbcollat = 'utf8mb4_unicode_ci';

    public function _protect_identifiers($value)
    {
        if (is_array($value)) {
            return array_map([$this, '_protect_identifiers'], $value);
        }

        return '<' . $value . '>';
    }

    public function escape_identifiers($value)
    {
        return '[' . $value . ']';
    }
}
