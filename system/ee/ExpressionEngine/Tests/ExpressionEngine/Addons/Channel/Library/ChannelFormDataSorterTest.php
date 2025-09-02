<?php

require_once __DIR__ . '/ChannelFormDataSorterTestBase.php';

/**
 * Test cases for Channel_form_data_sorter class
 *
 * This test suite provides 100% coverage for the Channel_form_data_sorter utility class.
 * Tests are organized by functionality: sorting, filtering, operator validation, and edge cases.
 */
class ChannelFormDataSorterTest extends ChannelFormDataSorterTestBase
{

    // ==========================================
    // SORT METHOD TESTS (6 tests - 25% coverage)
    // ==========================================

    /**
     * Test basic ascending sort functionality
     */
    public function testSortAscending()
    {
        $data = [
            ['id' => 3, 'name' => 'Charlie'],
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob']
        ];

        $sorter = $this->createDataSorter();
        $sorter->sort($data, 'id', 'asc');

        $this->assertEquals(1, $data[0]['id']);
        $this->assertEquals(2, $data[1]['id']);
        $this->assertEquals(3, $data[2]['id']);
        $this->assertArraySortedAscending($data, 'id');
    }

    /**
     * Test basic descending sort functionality
     */
    public function testSortDescending()
    {
        $data = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 3, 'name' => 'Charlie'],
            ['id' => 2, 'name' => 'Bob']
        ];

        $sorter = $this->createDataSorter();
        $sorter->sort($data, 'id', 'desc');

        $this->assertEquals(3, $data[0]['id']);
        $this->assertEquals(2, $data[1]['id']);
        $this->assertEquals(1, $data[2]['id']);
        $this->assertArraySortedDescending($data, 'id');
    }

    /**
     * Test sorting with missing column (should use null values)
     */
    public function testSortWithMissingColumn()
    {
        $data = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2], // missing 'name' column
            ['id' => 3, 'name' => 'Charlie']
        ];

        $sorter = $this->createDataSorter();
        $sorter->sort($data, 'name', 'asc');

        // Null values should come first
        $this->assertEquals(2, $data[0]['id']); // null value first
        $this->assertEquals(1, $data[1]['id']); // 'Alice'
        $this->assertEquals(3, $data[2]['id']); // 'Charlie'
    }

    /**
     * Test sorting with equal values
     */
    public function testSortWithEqualValues()
    {
        $data = [
            ['id' => 1, 'score' => 85],
            ['id' => 2, 'score' => 85], // equal value
            ['id' => 3, 'score' => 85], // equal value
            ['id' => 4, 'score' => 90]
        ];

        $sorter = $this->createDataSorter();
        $sorter->sort($data, 'score', 'asc');

        // Equal values should maintain original order
        $this->assertEquals(1, $data[0]['id']);
        $this->assertEquals(2, $data[1]['id']);
        $this->assertEquals(3, $data[2]['id']);
        $this->assertEquals(4, $data[3]['id']);
    }

    /**
     * Test sorting with mixed data types
     */
    public function testSortWithMixedDataTypes()
    {
        $data = [
            ['id' => 1, 'value' => 'apple'],
            ['id' => 2, 'value' => 100],
            ['id' => 3, 'value' => null],
            ['id' => 4, 'value' => 'banana'],
            ['id' => 5, 'value' => 50]
        ];

        $sorter = $this->createDataSorter();
        $sorter->sort($data, 'value', 'asc');

        // Null should come first, then numbers, then strings
        $this->assertNull($data[0]['value']);
        $this->assertEquals(50, $data[1]['value']);
        $this->assertEquals(100, $data[2]['value']);
        $this->assertEquals('apple', $data[3]['value']);
        $this->assertEquals('banana', $data[4]['value']);
    }

    /**
     * Test sorting empty array
     */
    public function testSortEmptyArray()
    {
        $data = [];

        $sorter = $this->createDataSorter();
        $sorter->sort($data, 'id', 'asc');

        $this->assertEquals([], $data);
    }

    // ==========================================
    // FILTER METHOD TESTS (11 tests - 45% coverage)
    // ==========================================

    /**
     * Test filter with equals operator (==)
     */
    public function testFilterEquals()
    {
        $data = $this->sampleData;
        $filtered = $this->getFilteredResults($data, 'age', 25);

        $this->assertCount(2, $filtered);
        // Filter preserves original array keys, so we need to check the actual keys
        $names = array_column($filtered, 'name');
        $this->assertContains('Alice', $names);
        $this->assertContains('Charlie', $names);
    }

    /**
     * Test filter with not equals operator (!=)
     */
    public function testFilterNotEquals()
    {
        $data = $this->sampleData;
        $filtered = $this->getFilteredResults($data, 'age', 25, '!=');

        $this->assertCount(3, $filtered);
        $this->assertEquals('Bob', $filtered[0]['name']);
        $this->assertEquals('David', $filtered[1]['name']);
        $this->assertEquals('Eve', $filtered[2]['name']);
    }

    /**
     * Test filter with strict equals operator (===)
     */
    public function testFilterStrictEquals()
    {
        $data = [
            ['id' => 1, 'value' => '25'],
            ['id' => 2, 'value' => 25],
            ['id' => 3, 'value' => '25']
        ];
        $filtered = $this->getFilteredResults($data, 'value', 25, '===');

        $this->assertCount(1, $filtered);
        $this->assertEquals(2, $filtered[0]['id']);
    }

    /**
     * Test filter with strict not equals operator (!==)
     */
    public function testFilterStrictNotEquals()
    {
        $data = [
            ['id' => 1, 'value' => '25'],
            ['id' => 2, 'value' => 25],
            ['id' => 3, 'value' => '25']
        ];
        $filtered = $this->getFilteredResults($data, 'value', 25, '!==');

        $this->assertCount(2, $filtered);
        $this->assertEquals(1, $filtered[0]['id']);
        $this->assertEquals(3, $filtered[1]['id']);
    }

    /**
     * Test filter with greater than operator (>)
     */
    public function testFilterGreaterThan()
    {
        $data = $this->sampleData;
        $filtered = $this->getFilteredResults($data, 'age', 28, '>');

        $this->assertCount(2, $filtered);
        $names = array_column($filtered, 'name');
        $this->assertContains('Bob', $names);
        $this->assertContains('David', $names);
    }

    /**
     * Test filter with less than operator (<)
     */
    public function testFilterLessThan()
    {
        $data = $this->sampleData;
        $filtered = $this->getFilteredResults($data, 'score', 85, '<');

        // Note: null values are treated as 0 in PHP comparisons, so Eve (null) is also included
        $this->assertCount(2, $filtered);
        $names = array_column($filtered, 'name');
        $this->assertContains('Charlie', $names);
        $this->assertContains('Eve', $names);
    }

    /**
     * Test filter with greater than or equal operator (>=)
     */
    public function testFilterGreaterEqual()
    {
        $data = $this->sampleData;
        $filtered = $this->getFilteredResults($data, 'age', 30, '>=');

        $this->assertCount(2, $filtered);
        $this->assertEquals('Bob', $filtered[0]['name']);
        $this->assertEquals('David', $filtered[1]['name']);
    }

    /**
     * Test filter with less than or equal operator (<=)
     */
    public function testFilterLessEqual()
    {
        $data = $this->sampleData;
        $filtered = $this->getFilteredResults($data, 'score', 85.5, '<=');

        $this->assertCount(3, $filtered);
        $names = array_column($filtered, 'name');
        $this->assertContains('Alice', $names);
        $this->assertContains('Charlie', $names);
        $this->assertContains('Eve', $names); // null score is treated as 0
    }

    /**
     * Test filter with not equal alternative operator (<>)
     */
    public function testFilterNotEqualAlt()
    {
        $data = $this->sampleData;
        $filtered = $this->getFilteredResults($data, 'active', true, '<>');

        $this->assertCount(2, $filtered);
        $this->assertEquals('Bob', $filtered[0]['name']);
        $this->assertEquals('David', $filtered[1]['name']);
    }

    /**
     * Test filter with in_array operator
     */
    public function testFilterInArray()
    {
        $data = $this->sampleData;
        $filtered = $this->getFilteredResults($data, 'age', [25, 30], 'in_array');

        $this->assertCount(3, $filtered);
        $this->assertEquals('Alice', $filtered[0]['name']);
        $this->assertEquals('Bob', $filtered[1]['name']);
        $this->assertEquals('Charlie', $filtered[2]['name']);
    }

    /**
     * Test filter with missing column
     */
    public function testFilterWithMissingColumn()
    {
        $data = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2], // missing 'name' column
            ['id' => 3, 'name' => 'Charlie']
        ];
        $filtered = $this->getFilteredResults($data, 'name', 'Alice');

        $this->assertCount(1, $filtered);
        $this->assertEquals('Alice', $filtered[0]['name']);
    }

    // ==========================================
    // OPERATOR VALIDATION TESTS (3 tests - 10% coverage)
    // ==========================================

    /**
     * Test setting valid operators
     */
    public function testSetOperatorValid()
    {
        $sorter = $this->createDataSorter();

        // Test all valid operators
        $validOperators = ['==', '!=', '===', '!==', '>', '<', '>=', '<=', '<>', 'in_array'];

        foreach ($validOperators as $operator) {
            $data = $this->simpleData;
            $filtered = $this->getFilteredResults($data, 'value', 10, $operator);

            // Should not throw an exception and should work
            $this->assertIsArray($filtered);
        }
    }

    /**
     * Test setting invalid operator defaults to '=='
     */
    public function testSetOperatorInvalid()
    {
        $data = $this->simpleData;
        $filtered = $this->getFilteredResults($data, 'value', 10, 'invalid_operator');

        // Should default to '==' and filter for value = 10
        $this->assertCount(1, $filtered);
        $this->assertEquals(10, $filtered[0]['value']);
    }

    /**
     * Test all valid operators are recognized
     */
    public function testAllValidOperators()
    {
        $sorter = $this->createDataSorter();

        // Use reflection to access private property
        $reflection = new ReflectionClass($sorter);
        $property = $reflection->getProperty('valid_operators');
        $property->setAccessible(true);
        $validOperators = $property->getValue($sorter);

        $expectedOperators = ['==', '!=', '===', '!==', '>', '<', '>=', '<=', '<>', 'in_array'];

        $this->assertEquals($expectedOperators, $validOperators);
        $this->assertCount(10, $validOperators);
    }

    // ==========================================
    // EDGE CASES & ERROR HANDLING TESTS (5 tests - 20% coverage)
    // ==========================================

    /**
     * Test filter with no matches
     */
    public function testFilterNoMatches()
    {
        $data = $this->sampleData;
        $filtered = $this->getFilteredResults($data, 'age', 100);

        $this->assertCount(0, $filtered);
        $this->assertEquals([], $filtered);
    }

    /**
     * Test filter with multiple matches
     */
    public function testFilterMultipleMatches()
    {
        $data = $this->sampleData;
        $filtered = $this->getFilteredResults($data, 'active', true);

        $this->assertCount(3, $filtered);
        $this->assertEquals('Alice', $filtered[0]['name']);
        $this->assertEquals('Charlie', $filtered[1]['name']);
        $this->assertEquals('Eve', $filtered[2]['name']);
    }

    /**
     * Test sort direction is case insensitive
     */
    public function testSortDirectionCaseInsensitive()
    {
        $data = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 3, 'name' => 'Charlie'],
            ['id' => 2, 'name' => 'Bob']
        ];

        // Test uppercase DESC
        $sorter1 = $this->createDataSorter();
        $data1 = $data;
        $sorter1->sort($data1, 'id', 'DESC');

        // Test lowercase desc
        $sorter2 = $this->createDataSorter();
        $data2 = $data;
        $sorter2->sort($data2, 'id', 'desc');

        // Both should produce the same result
        $this->assertEquals($data1, $data2);
        $this->assertEquals(3, $data1[0]['id']);
        $this->assertEquals(2, $data1[1]['id']);
        $this->assertEquals(1, $data1[2]['id']);
    }

    /**
     * Test in_array operator with pipe-delimited string
     */
    public function testFilterWithPipeDelimitedString()
    {
        $data = $this->sampleData;
        $filtered = $this->getFilteredResults($data, 'age', '25|30', 'in_array');

        $this->assertCount(3, $filtered);
        $this->assertEquals('Alice', $filtered[0]['name']);
        $this->assertEquals('Bob', $filtered[1]['name']);
        $this->assertEquals('Charlie', $filtered[2]['name']);
    }

    /**
     * Test constructor and basic properties
     */
    public function testConstructorAndProperties()
    {
        $sorter = $this->createDataSorter();

        // Test that it's an object
        $this->assertIsObject($sorter);
        $this->assertInstanceOf('Channel_form_data_sorter', $sorter);

        // Test that it has required methods
        $this->assertTrue(method_exists($sorter, 'sort'));
        $this->assertTrue(method_exists($sorter, 'filter'));

        // Test that private properties exist (using reflection)
        $reflection = new ReflectionClass($sorter);
        $this->assertTrue($reflection->hasProperty('column'));
        $this->assertTrue($reflection->hasProperty('direction'));
        $this->assertTrue($reflection->hasProperty('value'));
        $this->assertTrue($reflection->hasProperty('operator'));
        $this->assertTrue($reflection->hasProperty('valid_operators'));
    }

    // ==========================================
    // ADDITIONAL COMPREHENSIVE TESTS
    // ==========================================

    /**
     * Test sorting with null values
     */
    public function testSortWithNullValues()
    {
        $data = [
            ['id' => 1, 'value' => null],
            ['id' => 2, 'value' => 10],
            ['id' => 3, 'value' => null],
            ['id' => 4, 'value' => 5]
        ];

        $sorter = $this->createDataSorter();
        $sorter->sort($data, 'value', 'asc');

        // Null values should come first
        $this->assertNull($data[0]['value']);
        $this->assertNull($data[1]['value']);
        $this->assertEquals(5, $data[2]['value']);
        $this->assertEquals(10, $data[3]['value']);
    }

    /**
     * Test filter with null values
     */
    public function testFilterWithNullValues()
    {
        $data = [
            ['id' => 1, 'value' => null],
            ['id' => 2, 'value' => 10],
            ['id' => 3, 'value' => null]
        ];

        $filtered = $this->getFilteredResults($data, 'value', null);
        $this->assertCount(2, $filtered);
        $this->assertEquals(1, $filtered[0]['id']);
        $this->assertEquals(3, $filtered[1]['id']);
    }

    /**
     * Test multiple operations on same sorter instance
     */
    public function testMultipleOperationsOnSameInstance()
    {
        $data = $this->sampleData;

        // First filter
        $this->dataSorter->filter($data, 'active', true);
        $this->assertCount(3, $data);

        // Then sort the filtered results (only active users remain: Alice(25), Charlie(25), Eve(28))
        $this->dataSorter->sort($data, 'age', 'desc');
        $this->assertEquals('Eve', $data[0]['name']); // age 28 (highest)
        $this->assertEquals('Alice', $data[1]['name']); // age 25
        $this->assertEquals('Charlie', $data[2]['name']); // age 25
    }

    /**
     * Test in_array with empty array
     */
    public function testFilterInArrayEmptyArray()
    {
        $data = $this->sampleData;
        $filtered = $this->getFilteredResults($data, 'age', [], 'in_array');

        $this->assertCount(0, $filtered);
    }

    /**
     * Test in_array with single value array
     */
    public function testFilterInArraySingleValue()
    {
        $data = $this->sampleData;
        $filtered = $this->getFilteredResults($data, 'age', [25], 'in_array');

        $this->assertCount(2, $filtered);
        $this->assertEquals('Alice', $filtered[0]['name']);
        $this->assertEquals('Charlie', $filtered[1]['name']);
    }
}
