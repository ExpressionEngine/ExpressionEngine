<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../MultiSelectTestBase.php';

/**
 * Test Multi Select fieldtype save method
 */
class MultiSelectSaveTest extends MultiSelectTestBase
{
    /**
     * Test save method with array data
     */
    public function testSaveArrayData()
    {
        $data = ['option1', 'option2', 'option3'];

        $result = $this->fieldtype->save($data);

        // Should encode the array data
        $this->assertStringContainsString('option1', $result);
        $this->assertStringContainsString('option2', $result);
        $this->assertStringContainsString('option3', $result);
    }

    /**
     * Test save method with single value
     */
    public function testSaveSingleValue()
    {
        $data = 'option1';

        $result = $this->fieldtype->save($data);

        $this->assertEquals('option1', $result);
    }

    /**
     * Test save method with empty array
     */
    public function testSaveEmptyArray()
    {
        $data = [];

        $result = $this->fieldtype->save($data);

        $this->assertEquals('', $result);
    }

    /**
     * Test save method with null data
     */
    public function testSaveNullData()
    {
        $data = null;

        $result = $this->fieldtype->save($data);

        $this->assertNull($result);
    }

    /**
     * Test save method with special characters
     */
    public function testSaveSpecialCharacters()
    {
        $data = ['option & "quote"', 'option <tag>'];

        $result = $this->fieldtype->save($data);

        // Should escape pipes and backslashes only (not HTML entities)
        $this->assertStringContainsString('option & "quote"', $result);
        $this->assertStringContainsString('option <tag>', $result);
    }

    /**
     * Test save method with numeric values
     */
    public function testSaveNumericValues()
    {
        $data = [1, 2, 3];

        $result = $this->fieldtype->save($data);

        $this->assertStringContainsString('1', $result);
        $this->assertStringContainsString('2', $result);
        $this->assertStringContainsString('3', $result);
    }

    /**
     * Test save method with pipe characters that need escaping
     */
    public function testSavePipeCharacters()
    {
        $data = ['option|with|pipe', 'another|option'];

        $result = $this->fieldtype->save($data);

        // Should escape pipe characters
        $this->assertStringContainsString('option\|with\|pipe', $result);
        $this->assertStringContainsString('another\|option', $result);
    }

    /**
     * Test save method with backslash characters that need escaping
     */
    public function testSaveBackslashCharacters()
    {
        $data = ['option\\with\\backslash', 'another\\option'];

        $result = $this->fieldtype->save($data);

        // Should escape backslash characters
        $this->assertStringContainsString('option\\\\with\\\\backslash', $result);
        $this->assertStringContainsString('another\\\\option', $result);
    }

    /**
     * Test save method with mixed data types
     */
    public function testSaveMixedDataTypes()
    {
        $data = ['string_option', 123, true, false];

        $result = $this->fieldtype->save($data);

        $this->assertStringContainsString('string_option', $result);
        $this->assertStringContainsString('123', $result);
        $this->assertStringContainsString('1', $result); // true becomes 1
        $this->assertStringContainsString('', $result); // false becomes empty string
    }

    /**
     * Test save method preserves array structure
     */
    public function testSavePreservesStructure()
    {
        $data = ['first' => 'option1', 'second' => 'option2'];

        $result = $this->fieldtype->save($data);

        // Array keys are lost during encoding, only values matter
        $this->assertStringContainsString('option1', $result);
        $this->assertStringContainsString('option2', $result);
    }
}

// EOF
