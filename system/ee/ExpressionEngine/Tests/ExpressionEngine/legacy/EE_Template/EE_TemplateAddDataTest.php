<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';
require_once SYSPATH . 'ee/legacy/libraries/Template.php';

class EE_TemplateAddDataTest extends EE_TemplateTestBase
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
        \TestReflectionHelper::makePropertyAccessible($reflection);
        $reflection->setValue($this->template, $value);
    }
    public function testAddDataWhenProcessingEnabled()
    {
        // When process_data is true, add_data should not add data
        $this->setProtectedProperty('process_data', true);

        $initialData = ['existing' => 'value'];
        $this->template->set_data($initialData);

        $this->template->add_data(['new' => 'value']);

        // When processing is enabled, get_data returns null regardless of what was set
        $this->assertNull($this->template->get_data());
    }

    public function testAddDataWhenProcessingDisabled()
    {
        // When process_data is false, add_data should work
        $this->setProtectedProperty('process_data', false);

        $this->template->add_data(['key' => 'value']);

        $this->assertEquals(['key' => 'value'], $this->template->get_data());
    }

    public function testAddDataWithKey()
    {
        $this->setProtectedProperty('process_data', false);

        $this->template->add_data('test_value', 'test_key');

        $this->assertEquals(['test_key' => 'test_value'], $this->template->get_data());
    }

    public function testAddDataWithoutKey()
    {
        $this->setProtectedProperty('process_data', false);

        $this->template->add_data(['item1' => 'value1', 'item2' => 'value2']);

        $this->assertEquals(['item1' => 'value1', 'item2' => 'value2'], $this->template->get_data());
    }

    public function testAddDataWithEmptyData()
    {
        $this->setProtectedProperty('process_data', false);

        $initialData = ['existing' => 'value'];
        $this->template->set_data($initialData);

        $this->template->add_data([]);

        $this->assertEquals($initialData, $this->template->get_data());
    }

    public function testAddDataWithNullData()
    {
        $this->setProtectedProperty('process_data', false);

        $initialData = ['existing' => 'value'];
        $this->template->set_data($initialData);

        $this->template->add_data(null);

        $this->assertEquals($initialData, $this->template->get_data());
    }

    public function testAddDataKeyOverwritesExisting()
    {
        $this->setProtectedProperty('process_data', false);

        $this->template->add_data('original_value', 'test_key');
        $this->template->add_data('new_value', 'test_key');

        $this->assertEquals(['test_key' => 'new_value'], $this->template->get_data());
    }

    public function testAddDataWithoutKeyMergesArrays()
    {
        $this->setProtectedProperty('process_data', false);

        $this->template->add_data(['a' => 1, 'b' => 2]);
        $this->template->add_data(['c' => 3, 'd' => 4]);

        $expected = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
        $this->assertEquals($expected, $this->template->get_data());
    }

    public function testAddDataRecursiveMergeArrays()
    {
        $this->setProtectedProperty('process_data', false);

        $this->template->add_data(['users' => ['john' => 'admin']]);
        $this->template->add_data(['users' => ['jane' => 'editor']]);

        $expected = ['users' => ['john' => 'admin', 'jane' => 'editor']];
        $this->assertEquals($expected, $this->template->get_data());
    }

    public function testAddDataInitializesRawData()
    {
        $this->setProtectedProperty('process_data', false);

        // raw_data should be null initially
        $this->assertNull($this->template->get_data());

        $this->template->add_data(['initialized' => true]);

        $this->assertEquals(['initialized' => true], $this->template->get_data());
    }

    public function testAddDataWithNumericKeys()
    {
        $this->setProtectedProperty('process_data', false);

        $this->template->add_data('value1', 0);
        $this->template->add_data('value2', 1);

        $this->assertEquals([0 => 'value1', 1 => 'value2'], $this->template->get_data());
    }

    public function testAddDataWithStringKeys()
    {
        $this->setProtectedProperty('process_data', false);

        $this->template->add_data('value1', 'key1');
        $this->template->add_data('value2', 'key2');

        $expected = ['key1' => 'value1', 'key2' => 'value2'];
        $this->assertEquals($expected, $this->template->get_data());
    }

    public function testAddDataMixedKeyAndNoKey()
    {
        $this->setProtectedProperty('process_data', false);

        $this->template->add_data(['initial' => 'data']);
        $this->template->add_data('keyed_value', 'specific_key');

        $expected = ['initial' => 'data', 'specific_key' => 'keyed_value'];
        $this->assertEquals($expected, $this->template->get_data());
    }

    public function testAddDataWithComplexNestedData()
    {
        $this->setProtectedProperty('process_data', false);

        $complexData = [
            'config' => [
                'database' => ['host' => 'localhost', 'port' => 3306],
                'cache' => ['enabled' => true]
            ],
            'users' => [
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane']
            ]
        ];

        $this->template->add_data($complexData);

        $this->assertEquals($complexData, $this->template->get_data());
    }

    public function testAddDataEmptyStringVsNullKeys()
    {
        $this->setProtectedProperty('process_data', false);

        // Test empty string key vs null key behavior
        $this->template->add_data('value_with_empty_key', '');
        $this->template->add_data(['merged_key' => 'value_with_null_key'], null);

        $result = $this->template->get_data();

        // Empty string key should create keyed entry
        $this->assertArrayHasKey('', $result);
        $this->assertEquals('value_with_empty_key', $result['']);

        // Null key should merge array into existing data
        $this->assertArrayHasKey('merged_key', $result);
        $this->assertEquals('value_with_null_key', $result['merged_key']);
    }

    public function testAddDataSpecialCharactersInKeys()
    {
        $this->setProtectedProperty('process_data', false);

        $specialKeys = [
            'key with spaces',
            'key-with-dashes',
            'key_with_underscores',
            'key.with.dots',
            'key@with@symbols',
            'key#with#hash',
            'key$with$dollar',
            'key%with%percent'
        ];

        foreach ($specialKeys as $key) {
            $this->template->add_data("value_for_$key", $key);
        }

        $result = $this->template->get_data();

        foreach ($specialKeys as $key) {
            $this->assertArrayHasKey($key, $result);
            $this->assertEquals("value_for_$key", $result[$key]);
        }
    }

    public function testAddDataNumericStringKeys()
    {
        $this->setProtectedProperty('process_data', false);

        // Test numeric string keys vs actual integers
        $this->template->add_data('string_zero', '0');
        $this->template->add_data('string_one', '1');
        $this->template->add_data('int_zero', 0);
        $this->template->add_data('int_one', 1);

        $result = $this->template->get_data();

        // PHP treats string and int keys differently in arrays
        $this->assertArrayHasKey('0', $result);
        $this->assertArrayHasKey('1', $result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
    }

    public function testAddDataArrayMergeRecursiveConflicts()
    {
        $this->setProtectedProperty('process_data', false);

        // Test array_merge_recursive behavior with conflicting keys
        $this->template->add_data([
            'users' => ['john'],
            'settings' => ['theme' => 'light']
        ]);

        $this->template->add_data([
            'users' => ['jane'],
            'settings' => ['lang' => 'en']
        ]);

        $result = $this->template->get_data();

        // Should merge users arrays and merge settings arrays
        $this->assertEquals(['john', 'jane'], $result['users']);
        $this->assertEquals(['theme' => 'light', 'lang' => 'en'], $result['settings']);
    }

    public function testAddDataVeryLongKeys()
    {
        $this->setProtectedProperty('process_data', false);

        $longKey = str_repeat('a', 1000); // Very long key
        $longValue = str_repeat('b', 1000); // Very long value

        $this->template->add_data($longValue, $longKey);

        $result = $this->template->get_data();

        $this->assertArrayHasKey($longKey, $result);
        $this->assertEquals($longValue, $result[$longKey]);
    }

    public function testAddDataUnicodeKeys()
    {
        $this->setProtectedProperty('process_data', false);

        $unicodeKeys = [
            'café',           // accented characters
            '测试',           // Chinese characters
            '🚀emoji🚀',      // emoji
            'key_with_ñ',     // Spanish ñ
            'русский',        // Cyrillic
        ];

        foreach ($unicodeKeys as $key) {
            $this->template->add_data("value_for_$key", $key);
        }

        $result = $this->template->get_data();

        foreach ($unicodeKeys as $key) {
            $this->assertArrayHasKey($key, $result);
            $this->assertEquals("value_for_$key", $result[$key]);
        }
    }
}
