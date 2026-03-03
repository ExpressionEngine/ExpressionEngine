<?php

// This file contains multiple namespaces as part of testing
// that the gateway is loaded from .\gateway\<class>

namespace ExpressionEngine\Tests\Service\Model {

    use Mockery as m;
    use ExpressionEngine\Service\Model\Model;
    use ExpressionEngine\Service\Model\Gateway;
    use ExpressionEngine\Service\Model\MetaDataReader;
    use ExpressionEngine\Service\Model\SyntheticGateway;
    use PHPUnit\Framework\TestCase;

    class MetaDataReaderTest extends TestCase
    {
        public $model_class;
        public $reader;

        public function setUp(): void
        {
            $this->model_class = __NAMESPACE__ . '\\MetaDataModelStub';
            $this->reader = new MetaDataReader('Stub', $this->model_class);
        }

        public function tearDown(): void
        {
            $this->reader = null;
            m::close();
        }

        public function testGetName()
        {
            $this->assertEquals('Stub', $this->reader->getName());
        }

        public function testGetClass()
        {
            $this->assertEquals($this->model_class, $this->reader->getClass());
        }

        public function testGetPrimaryKey()
        {
            $this->assertEquals('stub_id', $this->reader->getPrimaryKey());
        }

        public function testGetGateways()
        {
            $gates = $this->reader->getGateways();

            $name = 'MetaDataTestStubGateway';
            $class = __NAMESPACE__ . '\\Gateway\\' . $name;

            $this->assertTrue(array_key_exists($name, $gates));
            $this->assertInstanceOf($class, $gates[$name]);
        }

        public function testGetTables()
        {
            $actual = $this->reader->getTables();
            $expected = array(
                'stub_table' => array(
                    'stub_id',
                    'first_name',
                    'last_name',
                    'age'
                )
            );

            $this->assertEquals($expected, $actual);
        }

        public function testGetTablesUsesCachedValue()
        {
            $first = $this->reader->getTables();
            $second = $this->reader->getTables();

            $this->assertSame($first, $second);
        }

        public function testGetValidationRules()
        {
            $expected = array(
                'first_name' => 'required',
                'age' => 'integer',
            );

            $this->assertEquals($expected, $this->reader->getValidationRules());
        }

        public function testGetRelationships()
        {
            $this->assertArrayHasKey('Site', $this->reader->getRelationships());
        }

        public function testGetEvents()
        {
            $expected = array(
                'beforeSave' => 'before_save',
                'afterSave' => 'after_save',
            );

            $this->assertEquals($expected, $this->reader->getEvents());
        }

        public function testGetBinaryComparisons()
        {
            $this->assertEquals(array('hash'), $this->reader->getBinaryComparisons());
        }

        public function testPublishesHooks()
        {
            $this->assertTrue($this->reader->publishesHooks());
        }

        public function testPublishesHooksReturnsFalseWhenHookIdMissing()
        {
            $class = __NAMESPACE__ . '\\MetaDataNoHookModelStub';
            $reader = new MetaDataReader('NoHook', $class);

            $this->assertFalse($reader->publishesHooks());
        }

        public function testGetGatewaysSynthesizesWhenOnlyTableIsDeclared()
        {
            $class = __NAMESPACE__ . '\\MetaDataTableOnlyModelStub';
            $reader = new MetaDataReader('TableOnly', $class);

            $gateways = $reader->getGateways();

            $this->assertArrayHasKey('table_only', $gateways);
            $this->assertInstanceOf(SyntheticGateway::class, $gateways['table_only']);
        }

        public function testGetGatewaysThrowsWhenNoGatewayAndNoTable()
        {
            $class = __NAMESPACE__ . '\\MetaDataNoTableNoGatewayModelStub';
            $reader = new MetaDataReader('NoTable', $class);

            $this->expectException(\Exception::class);
            $this->expectExceptionMessage("Model '{$class}' did not declare a table.");
            $reader->getGateways();
        }

        public function testGetTableForFieldUsesModelTableWhenDeclared()
        {
            $class = __NAMESPACE__ . '\\MetaDataTableNameModelStub';
            $reader = new MetaDataReader('TableName', $class);

            $this->assertEquals('model_table', $reader->getTableForField('anything'));
        }

        public function testGetTableForFieldLooksThroughGatewayFields()
        {
            $this->assertEquals('stub_table', $this->reader->getTableForField('age'));
            $this->assertNull($this->reader->getTableForField('missing'));
        }
    }

    class MetaDataModelStub extends Model
    {
        protected static $_primary_key = 'stub_id';
        protected static $_gateway_names = array('MetaDataTestStubGateway');

        protected static $_relationships = array(
            'Site' => array(
                'type' => 'belongsTo',
            ),
            'TemplateGroup' => array(
                'type' => 'hasMany'
            ),
            'LastAuthor' => array(
                'type' => 'hasOne',
                'model' => 'Member',
                'key' => 'last_author_id'
            ),
            'NoAccess' => array(
                'type' => 'hasAndBelongsToMany',
                'model' => 'MemberGroup'
            )
        );

        protected static $_validation_rules = array(
            'first_name' => 'required',
            'age' => 'integer',
        );

        protected static $_events = array(
            'beforeSave' => 'before_save',
            'afterSave' => 'after_save',
        );

        protected static $_binary_comparisons = array('hash');
        protected static $_hook_id = 'meta_stub';

        protected $stub_id;
        protected $first_name;
        protected $last_name;
        protected $age;
    }

    class MetaDataNoHookModelStub extends Model
    {
        protected static $_primary_key = 'stub_id';
        protected static $_hook_id = '';

        protected $stub_id;
    }

    class MetaDataTableOnlyModelStub extends Model
    {
        protected static $_primary_key = 'id';
        protected static $_table_name = 'table_only';

        protected $id;
        protected $name;
    }

    class MetaDataNoTableNoGatewayModelStub extends Model
    {
        protected static $_primary_key = 'id';

        protected $id;
    }

    class MetaDataTableNameModelStub extends Model
    {
        protected static $_primary_key = 'id';
        protected static $_table_name = 'model_table';

        protected $id;
    }
}

namespace ExpressionEngine\Tests\Service\Model\Gateway {

    use ExpressionEngine\Service\Model\Gateway;

    class MetaDataTestStubGateway extends Gateway
    {
        protected static $_table_name = 'stub_table';

        protected $stub_id;
        protected $first_name;
        protected $last_name;
        protected $age;
    }
}

// EOF
