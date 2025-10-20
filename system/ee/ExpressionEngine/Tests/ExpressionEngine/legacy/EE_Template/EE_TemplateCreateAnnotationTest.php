<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';
require_once SYSPATH . 'ee/legacy/libraries/Template.php';

class EE_TemplateCreateAnnotationTest extends EE_TemplateTestBase
{
    private $reflectionMethod;

    public function setUp(): void
    {
        parent::setUp();

        // Get the protected method using reflection
        $this->reflectionMethod = new \ReflectionMethod($this->template, 'createAnnotation');
        \TestReflectionHelper::makeMethodAccessible($this->reflectionMethod);
    }

    public function testCreateAnnotationInitializesRuntimeObject()
    {
        // Clear any existing annotations
        $this->clearAnnotationProperty();

        $data = ['context' => 'test'];
        $result = $this->reflectionMethod->invoke($this->template, $data);

        // Should initialize the annotations property
        $reflection = new \ReflectionProperty($this->template, 'annotations');
        \TestReflectionHelper::makePropertyAccessible($reflection);
        $annotations = $reflection->getValue($this->template);

        $this->assertInstanceOf(\ExpressionEngine\Library\Template\Annotation\Runtime::class, $annotations);

        // Should return a string starting with the annotation comment
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);
    }

    public function testCreateAnnotationReturnsString()
    {
        $data = ['context' => 'test'];
        $result = $this->reflectionMethod->invoke($this->template, $data);

        $this->assertIsString($result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);

        // Should contain a hash-like key between the markers
        $key = substr($result, 8, -4); // Remove '{!-- ra:' and ' --}'
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $key);
    }

    public function testCreateAnnotationWithEmptyData()
    {
        $data = [];
        $result = $this->reflectionMethod->invoke($this->template, $data);

        $this->assertIsString($result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);
    }


    public function testCreateAnnotationWithComplexData()
    {
        $data = [
            'context' => 'Template parsing',
            'line' => 42,
            'file' => '/path/to/template.html',
            'variables' => ['var1', 'var2'],
            'nested' => [
                'key' => 'value',
                'array' => [1, 2, 3]
            ]
        ];

        $result = $this->reflectionMethod->invoke($this->template, $data);

        $this->assertIsString($result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);

        // Verify the key is stored by reading it back
        $reflection = new \ReflectionProperty($this->template, 'annotations');
        \TestReflectionHelper::makePropertyAccessible($reflection);
        $annotations = $reflection->getValue($this->template);
        $storedData = $annotations->read($result);
        $this->assertEquals($data, (array) $storedData);
    }

    public function testCreateAnnotationWithUnicodeData()
    {
        $data = [
            'context' => '测试模板',
            'emoji' => '🚀🚁🚂',
            'mixed' => 'Hello 世界 🌍'
        ];

        $result = $this->reflectionMethod->invoke($this->template, $data);

        $this->assertIsString($result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);

        // Verify unicode data is preserved
        $reflection = new \ReflectionProperty($this->template, 'annotations');
        \TestReflectionHelper::makePropertyAccessible($reflection);
        $annotations = $reflection->getValue($this->template);
        $storedData = $annotations->read($result);
        $this->assertEquals($data, (array) $storedData);
    }

    public function testCreateAnnotationReusesInstance()
    {
        // First call
        $data1 = ['context' => 'first'];
        $result1 = $this->reflectionMethod->invoke($this->template, $data1);

        // Second call
        $data2 = ['context' => 'second'];
        $result2 = $this->reflectionMethod->invoke($this->template, $data2);

        // Should be the same Runtime instance
        $reflection = new \ReflectionProperty($this->template, 'annotations');
        \TestReflectionHelper::makePropertyAccessible($reflection);
        $annotations1 = $reflection->getValue($this->template);
        $reflection2 = new \ReflectionProperty($this->template, 'annotations');
        \TestReflectionHelper::makePropertyAccessible($reflection2);
        $annotations2 = $reflection2->getValue($this->template);
        $this->assertSame($annotations1, $annotations2);

        // But different keys
        $key1 = substr($result1, 8, -4);
        $key2 = substr($result2, 8, -4);
        $this->assertNotEquals($key1, $key2);

        // Verify both data sets are stored
        $storedData1 = $annotations1->read($result1);
        $storedData2 = $annotations1->read($result2);
        $this->assertEquals($data1, (array) $storedData1);
        $this->assertEquals($data2, (array) $storedData2);
    }

    public function testCreateAnnotationWithObjectData()
    {
        $object = new \stdClass();
        $object->property = 'value';
        $object->number = 42;

        $data = ['context' => 'object_test', 'object' => $object];
        $result = $this->reflectionMethod->invoke($this->template, $data);

        $this->assertIsString($result);

        // Verify object data is preserved
        $reflection = new \ReflectionProperty($this->template, 'annotations');
        \TestReflectionHelper::makePropertyAccessible($reflection);
        $annotations = $reflection->getValue($this->template);
        $storedData = $annotations->read($result);
        $this->assertEquals($data, (array) $storedData);
        $this->assertEquals($object, $storedData->object);
    }

    public function testCreateAnnotationWithLargeData()
    {
        // Create large data to test performance and memory handling
        $largeData = [
            'context' => 'large_test',
            'big_array' => array_fill(0, 1000, 'test_value_' . rand()),
            'big_string' => str_repeat('x', 10000),
            'nested' => array_fill(0, 100, ['level' => rand(1, 10)])
        ];

        $result = $this->reflectionMethod->invoke($this->template, $largeData);

        $this->assertIsString($result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);

        // Verify large data is stored correctly
        $reflection = new \ReflectionProperty($this->template, 'annotations');
        \TestReflectionHelper::makePropertyAccessible($reflection);
        $annotations = $reflection->getValue($this->template);
        $storedData = $annotations->read($result);
        $this->assertEquals($largeData, (array) $storedData);
        $this->assertCount(1000, $storedData->big_array);
        $this->assertEquals(10000, strlen($storedData->big_string));
    }

    public function testCreateAnnotationWithSpecialCharacters()
    {
        $data = [
            'context' => 'special_chars_test',
            'regex_chars' => '[]{}()*+?.^$|',
            'html' => '<script>alert("test")</script>',
            'json' => '{"key": "value", "array": [1,2,3]}',
            'slashes' => 'path\\to\\file/and/more\\slashes'
        ];

        $result = $this->reflectionMethod->invoke($this->template, $data);

        $this->assertIsString($result);

        // Verify special characters are preserved
        $reflection = new \ReflectionProperty($this->template, 'annotations');
        \TestReflectionHelper::makePropertyAccessible($reflection);
        $annotations = $reflection->getValue($this->template);
        $storedData = $annotations->read($result);
        $this->assertEquals($data, (array) $storedData);
    }


    public function testCreateAnnotationWithCircularReferences()
    {
        // Test with circular reference in data
        $data1 = ['context' => 'test1'];
        $data2 = ['context' => 'test2'];
        $data1['ref'] = &$data2;
        $data2['ref'] = &$data1;

        $data = ['circular' => $data1];

        // This should not cause infinite recursion or memory issues
        $result = $this->reflectionMethod->invoke($this->template, $data);

        $this->assertIsString($result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);
    }

    public function testCreateAnnotationWithResourceHandles()
    {
        // Test with resource handles (should be handled gracefully)
        $resource = fopen(__FILE__, 'r'); // Open current file as resource
        $data = ['context' => 'test', 'resource' => $resource];

        try {
            $result = $this->reflectionMethod->invoke($this->template, $data);

            $this->assertIsString($result);
            $this->assertStringStartsWith('{!-- ra:', $result);
            $this->assertStringEndsWith(' --}', $result);
        } finally {
            fclose($resource);
        }
    }

    public function testCreateAnnotationWithClosures()
    {
        // Test with closures (anonymous functions)
        $closure = function() { return 'test'; };
        $data = ['context' => 'test', 'closure' => $closure];

        $result = $this->reflectionMethod->invoke($this->template, $data);

        $this->assertIsString($result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);
    }

    public function testCreateAnnotationWithExtremelyNestedArrays()
    {
        // Test with deeply nested arrays
        $nested = ['level' => 1];
        $current = &$nested;
        for ($i = 2; $i <= 100; $i++) {
            $current['next'] = ['level' => $i];
            $current = &$current['next'];
        }

        $data = ['nested' => $nested];

        $result = $this->reflectionMethod->invoke($this->template, $data);

        $this->assertIsString($result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);
    }

    public function testCreateAnnotationWithBinaryData()
    {
        // Test with binary data
        $binaryData = "\x00\x01\x02\x03\x04\x05\xFF\xFE\xFD";
        $data = ['context' => 'test', 'binary' => $binaryData];

        $result = $this->reflectionMethod->invoke($this->template, $data);

        $this->assertIsString($result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);
    }

    public function testCreateAnnotationWithNullBytes()
    {
        // Test with null bytes in data
        $data = ['context' => 'test' . "\x00" . 'with_null'];

        $result = $this->reflectionMethod->invoke($this->template, $data);

        $this->assertIsString($result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);
    }

    public function testCreateAnnotationWithAnnotationStoreFailure()
    {
        // Test when annotation store might fail
        $this->clearAnnotationProperty();

        // Create a mock Runtime that throws an exception
        $mockRuntime = $this->getMockBuilder(\ExpressionEngine\Library\Template\Annotation\Runtime::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $mockRuntime->method('create')->willThrowException(new \Exception('Store failure'));

        // Set the mock as the annotations property
        $reflection = new \ReflectionProperty($this->template, 'annotations');
        \TestReflectionHelper::makePropertyAccessible($reflection);
        $reflection->setValue($this->template, $mockRuntime);

        $data = ['context' => 'test'];

        // Should throw exception when annotation store fails
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Store failure');

        $this->reflectionMethod->invoke($this->template, $data);
    }

    private function clearAnnotationProperty()
    {
        $reflection = new \ReflectionProperty($this->template, 'annotations');
        \TestReflectionHelper::makePropertyAccessible($reflection);
        $reflection->setValue($this->template, null);
    }
}
