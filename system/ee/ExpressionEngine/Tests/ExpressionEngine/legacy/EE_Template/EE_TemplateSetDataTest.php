<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';
require_once SYSPATH . 'ee/legacy/libraries/Template.php';

class EE_TemplateSetDataTest extends EE_TemplateTestBase
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
    public function testSetDataWhenProcessingEnabled()
    {
        // When process_data is true, set_data should not store data
        $this->setProtectedProperty('process_data', true);

        $testData = ['key' => 'value'];
        $this->template->set_data($testData);

        $this->assertNull($this->template->get_data());
    }

    public function testSetDataWhenProcessingDisabled()
    {
        // When process_data is false, set_data should store data in raw_data
        $this->setProtectedProperty('process_data', false);

        $testData = ['key' => 'value'];
        $this->template->set_data($testData);

        $this->assertEquals($testData, $this->template->get_data());
    }

    public function testSetDataWithNullValue()
    {
        $this->setProtectedProperty('process_data', false);

        $this->template->set_data(null);

        $this->assertNull($this->template->get_data());
    }

    public function testSetDataWithArrayValue()
    {
        $this->setProtectedProperty('process_data', false);

        $testData = [
            'users' => ['user1', 'user2'],
            'settings' => ['theme' => 'dark', 'lang' => 'en']
        ];

        $this->template->set_data($testData);

        $this->assertEquals($testData, $this->template->get_data());
    }

    public function testSetDataWithScalarValue()
    {
        $this->setProtectedProperty('process_data', false);

        $testData = 'string_value';
        $this->template->set_data($testData);

        $this->assertEquals($testData, $this->template->get_data());
    }

    public function testSetDataOverwritesExistingData()
    {
        $this->setProtectedProperty('process_data', false);

        // Set initial data
        $initialData = ['initial' => 'value'];
        $this->template->set_data($initialData);

        // Overwrite with new data
        $newData = ['new' => 'value'];
        $this->template->set_data($newData);

        $this->assertEquals($newData, $this->template->get_data());
        $this->assertNotEquals($initialData, $this->template->get_data());
    }

    public function testSetDataWithEmptyArray()
    {
        $this->setProtectedProperty('process_data', false);

        $this->template->set_data([]);

        $this->assertEquals([], $this->template->get_data());
    }

    public function testSetDataWithNumericValue()
    {
        $this->setProtectedProperty('process_data', false);

        $this->template->set_data(42);

        $this->assertEquals(42, $this->template->get_data());
    }

    public function testSetDataWithBooleanValue()
    {
        $this->setProtectedProperty('process_data', false);

        $this->template->set_data(true);

        $this->assertTrue($this->template->get_data());
    }

    public function testSetDataWithObjectValue()
    {
        $this->setProtectedProperty('process_data', false);

        $testObject = new \stdClass();
        $testObject->property = 'value';

        $this->template->set_data($testObject);

        $this->assertEquals($testObject, $this->template->get_data());
        $this->assertSame($testObject, $this->template->get_data());
    }

    public function testSetDataWithLargeArray()
    {
        $this->setProtectedProperty('process_data', false);

        // Create a large array with 1000+ elements
        $largeArray = [];
        for ($i = 0; $i < 1500; $i++) {
            $largeArray['key_' . $i] = 'value_' . $i;
        }

        $this->template->set_data($largeArray);

        $result = $this->template->get_data();
        $this->assertCount(1500, $result);
        $this->assertEquals('value_0', $result['key_0']);
        $this->assertEquals('value_1499', $result['key_1499']);
    }

    public function testSetDataWithLargeNestedArray()
    {
        $this->setProtectedProperty('process_data', false);

        // Create deeply nested array
        $nestedArray = [];
        $current = &$nestedArray;
        for ($i = 0; $i < 50; $i++) {
            $current['level_' . $i] = [];
            $current = &$current['level_' . $i];
        }
        $current['deepest_value'] = 'found_it';

        $this->template->set_data($nestedArray);

        $result = $this->template->get_data();

        // Navigate to the deepest level
        $deep = $result;
        for ($i = 0; $i < 50; $i++) {
            $this->assertArrayHasKey('level_' . $i, $deep);
            $deep = $deep['level_' . $i];
        }
        $this->assertEquals('found_it', $deep['deepest_value']);
    }

    public function testSetDataWithLargeString()
    {
        $this->setProtectedProperty('process_data', false);

        // Create a large string (1MB)
        $largeString = str_repeat('Large string data repeated many times. ', 25000);

        $this->template->set_data($largeString);

        $result = $this->template->get_data();
        $this->assertEquals(strlen($largeString), strlen($result));
        $this->assertStringStartsWith('Large string data', $result);
        $this->assertStringEndsWith('times. ', $result);
    }

    public function testSetDataWithBinaryData()
    {
        $this->setProtectedProperty('process_data', false);

        // Create binary data with null bytes
        $binaryData = "Binary data\x00with\x01null\x02bytes\x03and\x04special\x05chars\x00";

        $this->template->set_data($binaryData);

        $result = $this->template->get_data();
        $this->assertEquals($binaryData, $result);
        $this->assertStringContainsString("\x00", $result);
        $this->assertStringContainsString("\x05", $result);
    }

    public function testSetDataWithUnicodeData()
    {
        $this->setProtectedProperty('process_data', false);

        $unicodeData = [
            'emoji' => '🚀🚁🚂🚃🚄🚅🚆🚇🚈🚉🚊🚋🚌🚍🚎🚏🚐🚑🚒🚓🚔🚕🚖🚗🚘🚙🚚🚛🚜🚝🚞🚟🚠🚡🚢🚣🚤🚥🚦🚧🚨🚩🚪🚫🚬🚭🚮🚯🚰🚱🚲🚳🚴🚵🚶🚷🚸🚹🚺🚻🚼🚽🚾🚿🛀🛁🛂🛃🛄🛅🛆🛇🛈🛉🛊🛋🛌🛍🛎🛏🛐🛑🛒🛓🛔🛕🛖🛗🛘🛙🛚🛛🛜🛝🛞🛟🛠🛡🛢🛣🛤🛥🛦🛧🛨🛩🛪🛫🛬🛭🛮🛯🛰🛱🛲🛳🛴🛵🛶🛷🛸🛹🛺🛻🛼🛽🛾',
            'chinese' => '你好世界测试数据',
            'arabic' => 'مرحبا بالعالم اختبار البيانات',
            'mixed' => 'Hello 世界 🌍 Test 🚀 数据'
        ];

        $this->template->set_data($unicodeData);

        $result = $this->template->get_data();
        $this->assertEquals($unicodeData, $result);
        $this->assertArrayHasKey('emoji', $result);
        $this->assertArrayHasKey('chinese', $result);
        $this->assertArrayHasKey('arabic', $result);
        $this->assertArrayHasKey('mixed', $result);
    }
}
