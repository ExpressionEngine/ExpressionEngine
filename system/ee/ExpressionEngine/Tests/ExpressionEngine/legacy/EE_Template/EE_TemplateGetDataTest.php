<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';
require_once SYSPATH . 'ee/legacy/libraries/Template.php';

class EE_TemplateGetDataTest extends EE_TemplateTestBase
{
    public function setUp(): void
    {
        parent::setUp();
        // Set process_data to false by default for most tests
        $this->setProtectedProperty('process_data', false);
    }

    private function setProtectedProperty($property, $value)
    {
        $reflection = new \ReflectionProperty($this->template, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($this->template, $value);
    }
    public function testGetDataWhenProcessingEnabled()
    {
        // When process_data is true, get_data should return null
        $this->setProtectedProperty('process_data', true);

        // Set some data to ensure it's ignored
        $this->template->set_data(['test' => 'data']);

        $this->assertNull($this->template->get_data());
    }

    public function testGetDataWhenProcessingDisabled()
    {
        // When process_data is false, get_data should return raw_data
        $this->setProtectedProperty('process_data', false);

        $testData = ['key' => 'value'];
        $this->template->set_data($testData);

        $this->assertEquals($testData, $this->template->get_data());
    }

    public function testGetDataWithNullRawData()
    {
        $this->setProtectedProperty('process_data', false);

        // raw_data should be null initially
        $this->assertNull($this->template->get_data());
    }

    public function testGetDataWithEmptyArray()
    {
        $this->setProtectedProperty('process_data', false);

        $this->template->set_data([]);

        $this->assertEquals([], $this->template->get_data());
    }

    public function testGetDataWithPopulatedArray()
    {
        $this->setProtectedProperty('process_data', false);

        $testData = [
            'users' => ['john', 'jane'],
            'settings' => ['theme' => 'dark']
        ];

        $this->template->set_data($testData);

        $this->assertEquals($testData, $this->template->get_data());
    }

    public function testGetDataWithScalarData()
    {
        $this->setProtectedProperty('process_data', false);

        $testData = 'string value';
        $this->template->set_data($testData);

        $this->assertEquals($testData, $this->template->get_data());
    }

    public function testGetDataWithNumericData()
    {
        $this->setProtectedProperty('process_data', false);

        $this->template->set_data(12345);

        $this->assertEquals(12345, $this->template->get_data());
    }

    public function testGetDataWithBooleanData()
    {
        $this->setProtectedProperty('process_data', false);

        $this->template->set_data(false);

        $this->assertFalse($this->template->get_data());
    }

    public function testGetDataWithObjectData()
    {
        $this->setProtectedProperty('process_data', false);

        $testObject = new \stdClass();
        $testObject->property = 'value';

        $this->template->set_data($testObject);

        $result = $this->template->get_data();
        $this->assertEquals($testObject, $result);
        $this->assertSame($testObject, $result);
    }

    public function testGetDataWithComplexNestedData()
    {
        $this->setProtectedProperty('process_data', false);

        $complexData = [
            'config' => [
                'database' => ['host' => 'localhost', 'port' => 3306],
                'cache' => ['enabled' => true, 'ttl' => 3600]
            ],
            'users' => [
                ['id' => 1, 'name' => 'John', 'roles' => ['admin', 'editor']],
                ['id' => 2, 'name' => 'Jane', 'roles' => ['editor']]
            ],
            'metadata' => [
                'version' => '1.0.0',
                'timestamp' => time()
            ]
        ];

        $this->template->set_data($complexData);

        $this->assertEquals($complexData, $this->template->get_data());
    }

    public function testGetDataAfterAddDataOperations()
    {
        $this->setProtectedProperty('process_data', false);

        // Test combination of set_data and add_data operations
        $this->template->set_data(['initial' => 'value']);
        $this->template->add_data(['added' => 'data'], 'key');
        $this->template->add_data(['merged' => 'array']);

        $expected = [
            'initial' => 'value',
            'key' => ['added' => 'data'],
            'merged' => 'array'
        ];

        $this->assertEquals($expected, $this->template->get_data());
    }

    public function testGetDataReturnsCopy()
    {
        $this->setProtectedProperty('process_data', false);

        $testArray = ['mutable' => 'data'];
        $this->template->set_data($testArray);

        $result = $this->template->get_data();
        $result['mutable'] = 'modified';

        // PHP returns arrays by value, so modifying the returned array shouldn't affect internal data
        $this->assertEquals('data', $this->template->get_data()['mutable']);
        $this->assertEquals('modified', $result['mutable']);
    }

    public function testGetDataWithLargeDataSet()
    {
        $this->setProtectedProperty('process_data', false);

        // Create a large dataset
        $largeData = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeData['item_' . $i] = 'value_' . $i;
        }

        $this->template->set_data($largeData);

        $result = $this->template->get_data();
        $this->assertCount(1000, $result);
        $this->assertEquals('value_0', $result['item_0']);
        $this->assertEquals('value_999', $result['item_999']);
    }

    public function testGetDataWithCorruptedData()
    {
        $this->setProtectedProperty('process_data', false);

        // Simulate corrupted data (non-array in raw_data)
        $this->template->set_data('not_an_array');

        $result = $this->template->get_data();

        // Should still return the data as-is
        $this->assertEquals('not_an_array', $result);
    }

    public function testGetDataWithResource()
    {
        $this->setProtectedProperty('process_data', false);

        // Create a temporary file resource
        $tempFile = tmpfile();
        fwrite($tempFile, 'test data');

        $this->template->set_data($tempFile);

        $result = $this->template->get_data();

        // Should return the resource
        $this->assertTrue(is_resource($result));
        fclose($tempFile); // Clean up
    }

    public function testGetDataMemoryImplications()
    {
        $this->setProtectedProperty('process_data', false);

        // Test with data that approaches memory limits
        $largeData = str_repeat('x', 1000000); // 1MB string

        $this->template->set_data($largeData);

        $result = $this->template->get_data();

        // Should handle large data without issues
        $this->assertEquals(strlen($largeData), strlen($result));
    }

    public function testGetDataWithCallableData()
    {
        $this->setProtectedProperty('process_data', false);

        // Test with callable data (functions/closures)
        $callable = function($param) {
            return 'called with: ' . $param;
        };

        $this->template->set_data($callable);

        $result = $this->template->get_data();

        // Should return the callable
        $this->assertTrue(is_callable($result));
        $this->assertEquals('called with: test', $result('test'));
    }
}
