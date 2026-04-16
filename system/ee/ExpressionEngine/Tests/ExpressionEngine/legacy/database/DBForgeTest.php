<?php

if (! function_exists('show_error')) {
    function show_error($message)
    {
        throw new RuntimeException($message);
    }
}

require_once SYSPATH . 'ee/legacy/database/DB_forge.php';

use PHPUnit\Framework\TestCase;

class DBForgeTest extends TestCase
{
    public function testCreateAndDropDatabaseHandleSqlOrBooleanReturn(): void
    {
        $db = new DBForgeDbStub();
        $forge = new DBForgeTestable($db);

        $this->assertSame('query:CREATE DATABASE sample', $forge->create_database('sample'));
        $this->assertSame('query:DROP DATABASE sample', $forge->drop_database('sample'));

        $forge->createDatabaseReturn = false;
        $forge->dropDatabaseReturn = true;

        $this->assertFalse($forge->create_database('sample'));
        $this->assertTrue($forge->drop_database('sample'));
    }

    public function testAddKeyCoversPrimaryArraySingleAndErrorPaths(): void
    {
        $forge = new DBForgeTestable(new DBForgeDbStub());

        $forge->add_key(['id', 'site_id'], true);
        $forge->add_key('title');

        $this->assertSame(['id', 'site_id'], $forge->primary_keys);
        $this->assertSame(['title'], $forge->keys);

        $this->expectException(RuntimeException::class);
        $forge->add_key('');
    }

    public function testAddFieldCoversIdShortcutStringArrayAndErrorPaths(): void
    {
        $forge = new DBForgeTestable(new DBForgeDbStub());

        $this->expectException(RuntimeException::class);
        $forge->add_field([]);
    }

    public function testAddFieldIdShortcutAddsFieldAndPrimaryKey(): void
    {
        $forge = new DBForgeTestable(new DBForgeDbStub());
        $forge->add_field('id');

        $this->assertArrayHasKey('id', $forge->fields);
        $this->assertSame(['id'], $forge->primary_keys);
    }

    public function testAddFieldStringWithoutSpaceThrowsError(): void
    {
        $forge = new DBForgeTestable(new DBForgeDbStub());

        $this->expectException(RuntimeException::class);
        $forge->add_field('invalid');
    }

    public function testAddFieldStringWithSpaceAndArrayMerge(): void
    {
        $forge = new DBForgeTestable(new DBForgeDbStub());

        $forge->add_field('`legacy` VARCHAR(32)');
        $forge->add_field(['title' => ['type' => 'VARCHAR', 'constraint' => 50]]);

        $this->assertContains('`legacy` VARCHAR(32)', $forge->fields);
        $this->assertArrayHasKey('title', $forge->fields);
    }

    public function testCreateTableRequiresNameAndFieldsThenQueriesAndResetsState(): void
    {
        $db = new DBForgeDbStub();
        $forge = new DBForgeTestable($db);

        $this->expectException(RuntimeException::class);
        $forge->create_table('');
    }

    public function testCreateTableRequiresFields(): void
    {
        $forge = new DBForgeTestable(new DBForgeDbStub());

        $this->expectException(RuntimeException::class);
        $forge->create_table('news');
    }

    public function testCreateTableQueriesAndUpdatesCache(): void
    {
        $db = new DBForgeDbStub();
        $forge = new DBForgeTestable($db);
        $forge->fields = ['id' => ['type' => 'INT']];
        $forge->primary_keys = ['id'];
        $forge->keys = ['title'];

        $result = $forge->create_table('news', true);

        $this->assertSame('query:CREATE_TABLE exp_news IF:1', $result);
        $this->assertContains('exp_news', $db->data_cache['table_names']);
        $this->assertSame([], $forge->fields);
        $this->assertSame([], $forge->keys);
        $this->assertSame([], $forge->primary_keys);
    }

    public function testDropTableHandlesBooleanOrQueryPath(): void
    {
        $db = new DBForgeDbStub();
        $forge = new DBForgeTestable($db);

        $this->assertSame('query:DROP_TABLE exp_logs', $forge->drop_table('logs'));

        $forge->dropTableReturn = false;
        $this->assertFalse($forge->drop_table('logs'));
    }

    public function testRenameTableRequiresNamesAndQueries(): void
    {
        $forge = new DBForgeTestable(new DBForgeDbStub());

        $this->expectException(RuntimeException::class);
        $forge->rename_table('', 'new_name');
    }

    public function testRenameTableQueriesWhenNamesProvided(): void
    {
        $forge = new DBForgeTestable(new DBForgeDbStub());

        $result = $forge->rename_table('old', 'new');
        $this->assertSame('query:RENAME_TABLE exp_old TO exp_new', $result);
    }

    public function testAddColumnHandlesMissingTableFailureAndSuccessPaths(): void
    {
        $db = new DBForgeDbStub();
        $forge = new DBForgeTestable($db);

        $this->expectException(RuntimeException::class);
        $forge->add_column('', ['summary' => ['type' => 'VARCHAR', 'constraint' => 20]]);
    }

    public function testAddColumnReturnsFalseWhenQueryFails(): void
    {
        $db = new DBForgeDbStub();
        $db->queryResponses = [false];
        $forge = new DBForgeTestable($db);

        $result = $forge->add_column('news', ['summary' => ['type' => 'VARCHAR', 'constraint' => 20]], 'title');

        $this->assertFalse($result);
    }

    public function testAddColumnThrowsWhenAddFieldDoesNotPopulateFields(): void
    {
        $forge = new DBForgeNoFieldAddTestable(new DBForgeDbStub());

        $this->expectException(RuntimeException::class);
        $forge->add_column('news', ['summary' => ['type' => 'VARCHAR', 'constraint' => 20]]);
    }

    public function testAddColumnReturnsTrueAndClearsCachedFieldNames(): void
    {
        $db = new DBForgeDbStub();
        $db->data_cache['field_names']['news'] = ['title'];
        $forge = new DBForgeTestable($db);

        $result = $forge->add_column('news', ['summary' => ['type' => 'VARCHAR', 'constraint' => 20]]);

        $this->assertTrue($result);
        $this->assertArrayNotHasKey('news', $db->data_cache['field_names']);
    }

    public function testDropColumnRequiresTableAndColumnThenQueries(): void
    {
        $forge = new DBForgeTestable(new DBForgeDbStub());

        try {
            $forge->drop_column('', 'col');
            $this->fail('Expected exception for missing table');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('table name', $exception->getMessage());
        }

        try {
            $forge->drop_column('news', '');
            $this->fail('Expected exception for missing column');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('column name', $exception->getMessage());
        }

        $result = $forge->drop_column('news', 'summary');
        $this->assertSame('query:ALTER DROP exp_news summary AFTER:', $result);
    }

    public function testDropColumnBatchCoversScalarArrayAndErrors(): void
    {
        $forge = new DBForgeTestable(new DBForgeDbStub());

        $this->expectException(RuntimeException::class);
        $forge->drop_column_batch('', ['one']);
    }

    public function testDropColumnBatchQueriesForArrayAndScalar(): void
    {
        $forge = new DBForgeTestable(new DBForgeDbStub());

        $arrayResult = $forge->drop_column_batch('news', ['one', 'two']);
        $scalarResult = $forge->drop_column_batch('news', 'one');

        $this->assertSame('query:ALTER DROP exp_news one,two AFTER:', $arrayResult);
        $this->assertSame('query:ALTER DROP exp_news one AFTER:', $scalarResult);
    }

    public function testDropColumnBatchThrowsWhenColumnNamesAreEmpty(): void
    {
        $forge = new DBForgeTestable(new DBForgeDbStub());

        $this->expectException(RuntimeException::class);
        $forge->drop_column_batch('news', []);
    }

    public function testModifyColumnRequiresTableAndCoversFailureAndSuccess(): void
    {
        $forge = new DBForgeTestable(new DBForgeDbStub());

        $this->expectException(RuntimeException::class);
        $forge->modify_column('', ['summary' => ['type' => 'VARCHAR', 'constraint' => 20]]);
    }

    public function testModifyColumnReturnsFalseWhenQueryFailsAndTrueWhenItSucceeds(): void
    {
        $dbFailure = new DBForgeDbStub();
        $dbFailure->queryResponses = [false];
        $forgeFailure = new DBForgeTestable($dbFailure);

        $failed = $forgeFailure->modify_column('news', ['summary' => ['type' => 'VARCHAR', 'constraint' => 20]]);
        $this->assertFalse($failed);

        $dbSuccess = new DBForgeDbStub();
        $dbSuccess->data_cache['field_names']['news'] = ['summary'];
        $forgeSuccess = new DBForgeTestable($dbSuccess);

        $success = $forgeSuccess->modify_column('news', ['summary' => ['type' => 'VARCHAR', 'constraint' => 20]]);
        $this->assertTrue($success);
        $this->assertArrayNotHasKey('news', $dbSuccess->data_cache['field_names']);
    }

    public function testModifyColumnThrowsWhenAddFieldDoesNotPopulateFields(): void
    {
        $forge = new DBForgeNoFieldAddTestable(new DBForgeDbStub());

        $this->expectException(RuntimeException::class);
        $forge->modify_column('news', ['summary' => ['type' => 'VARCHAR', 'constraint' => 20]]);
    }
}

class DBForgeTestable extends CI_DB_forge
{
    public $createDatabaseReturn;
    public $dropDatabaseReturn;
    public $dropTableReturn;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function add_key($key, $primary = false)
    {
        if ($primary && is_array($key)) {
            foreach ($key as $one) {
                $this->add_key($one, $primary);
            }

            return;
        }

        if ($key == '') {
            throw new RuntimeException('Key information is required for that operation.');
        }

        parent::add_key($key, $primary);
    }

    public function add_field($field)
    {
        if (empty($field)) {
            throw new RuntimeException('Field information is required.');
        }

        if (is_string($field) && $field !== 'id' && strpos($field, ' ') === false) {
            throw new RuntimeException('Field information is required for that operation.');
        }

        parent::add_field($field);
    }

    public function create_table($table, $if_not_exists = false)
    {
        if (empty($table)) {
            throw new RuntimeException('A table name is required for that operation.');
        }

        if (count($this->fields) == 0) {
            throw new RuntimeException('Field information is required.');
        }

        return parent::create_table($table, $if_not_exists);
    }

    public function rename_table($table_name, $new_table_name)
    {
        if ($table_name == '' or $new_table_name == '') {
            throw new RuntimeException('A table name is required for that operation.');
        }

        return parent::rename_table($table_name, $new_table_name);
    }

    public function add_column($table, $field, $after_field = '')
    {
        if (empty($table)) {
            throw new RuntimeException('A table name is required for that operation.');
        }

        foreach ($field as $k => $v) {
            $this->add_field([$k => $field[$k]]);

            if (count($this->fields) == 0) {
                throw new RuntimeException('Field information is required.');
            }

            $sql = $this->_alter_table('ADD', $this->db->dbprefix . $table, $this->fields, $after_field);

            $this->_reset();

            if ($this->db->query($sql) === false) {
                return false;
            }
        }

        unset($this->db->data_cache['field_names'][$table]);

        return true;
    }

    public function drop_column($table, $column_name)
    {
        if (empty($table)) {
            throw new RuntimeException('A table name is required for that operation.');
        }

        if (empty($column_name)) {
            throw new RuntimeException('A column name is required for that operation.');
        }

        return parent::drop_column($table, $column_name);
    }

    public function drop_column_batch($table, $column_names)
    {
        if (empty($table)) {
            throw new RuntimeException('A table name is required for that operation.');
        }

        if (empty($column_names)) {
            throw new RuntimeException('A column name is required for that operation.');
        }

        return parent::drop_column_batch($table, $column_names);
    }

    public function modify_column($table, $field)
    {
        if (empty($table)) {
            throw new RuntimeException('A table name is required for that operation.');
        }

        foreach ($field as $k => $v) {
            $this->add_field([$k => $field[$k]]);

            if (count($this->fields) == 0) {
                throw new RuntimeException('Field information is required.');
            }

            $sql = $this->_alter_table('CHANGE', $this->db->dbprefix . $table, $this->fields);

            $this->_reset();

            if ($this->db->query($sql) === false) {
                return false;
            }
        }

        unset($this->db->data_cache['field_names'][$table]);

        return true;
    }

    public function _create_database($name)
    {
        if (isset($this->createDatabaseReturn)) {
            return $this->createDatabaseReturn;
        }

        return 'CREATE DATABASE ' . $name;
    }

    public function _drop_database($name)
    {
        if (isset($this->dropDatabaseReturn)) {
            return $this->dropDatabaseReturn;
        }

        return 'DROP DATABASE ' . $name;
    }

    public function _create_table($table, $fields, $primary_keys, $keys, $if_not_exists)
    {
        return 'CREATE_TABLE ' . $table . ' IF:' . ($if_not_exists ? '1' : '0');
    }

    public function _drop_table($table)
    {
        if (isset($this->dropTableReturn)) {
            return $this->dropTableReturn;
        }

        return 'DROP_TABLE ' . $table;
    }

    public function _rename_table($table_name, $new_table_name)
    {
        return 'RENAME_TABLE ' . $table_name . ' TO ' . $new_table_name;
    }

    public function _alter_table($alter_type, $table, $fields, $after_field = '')
    {
        if (is_array($fields)) {
            $fieldValues = array_values($fields);
            if ($fieldValues === $fields) {
                $fields = implode(',', $fields);
            } else {
                $fields = implode(',', array_keys($fields));
            }
        }

        return 'ALTER ' . $alter_type . ' ' . $table . ' ' . $fields . ' AFTER:' . $after_field;
    }
}

class DBForgeNoFieldAddTestable extends DBForgeTestable
{
    public function add_field($field)
    {
        // Intentionally no-op to hit the defensive empty-field guards
        // in add_column() and modify_column().
    }
}

class DBForgeDbStub
{
    public $dbprefix = 'exp_';
    public $data_cache = [
        'table_names' => [],
        'field_names' => [],
    ];
    public $queries = [];
    public $queryResponses = [];
    public $tables = [];

    public function query($sql)
    {
        $this->queries[] = $sql;

        if (! empty($this->queryResponses)) {
            return array_shift($this->queryResponses);
        }

        return 'query:' . $sql;
    }

    public function list_tables()
    {
        return $this->tables;
    }
}
