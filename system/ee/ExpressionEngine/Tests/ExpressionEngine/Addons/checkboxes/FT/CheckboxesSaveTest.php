<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../CheckboxesTestBase.php';

/**
 * Test for Checkboxes_ft::save() method
 */
class CheckboxesSaveTest extends CheckboxesTestBase
{
    /**
     * @var Checkboxes_ft
     */
    protected $fieldtype;

    public function setUp(): void
    {
        parent::setUp();
        $this->fieldtype = $this->getMockFieldtypeWithSettings();
    }

    /**
     * Test save with array data
     */
    public function testSaveArrayData()
    {
        $data = ['option1', 'option2', 'option3'];

        $result = $this->fieldtype->save($data);

        // Should encode the array as a pipe-separated string
        $this->assertEquals('option1|option2|option3', $result);
    }

    /**
     * Test save with single value array
     */
    public function testSaveSingleValueArray()
    {
        $data = ['option1'];

        $result = $this->fieldtype->save($data);

        $this->assertEquals('option1', $result);
    }

    /**
     * Test save with empty array
     */
    public function testSaveEmptyArray()
    {
        $data = [];

        $result = $this->fieldtype->save($data);

        $this->assertEquals('', $result);
    }

    /**
     * Test save with non-array data
     */
    public function testSaveNonArrayData()
    {
        $data = 'option1';

        $result = $this->fieldtype->save($data);

        // Should return data unchanged since it's not an array
        $this->assertEquals('option1', $result);
    }

    /**
     * Test save with null data
     */
    public function testSaveNullData()
    {
        $data = null;

        $result = $this->fieldtype->save($data);

        $this->assertEquals(null, $result);
    }

    /**
     * Test save with numeric values
     */
    public function testSaveNumericValues()
    {
        $data = [1, 2, 3];

        $result = $this->fieldtype->save($data);

        $this->assertEquals('1|2|3', $result);
    }

    /**
     * Test save with values containing pipes
     */
    public function testSaveValuesWithPipes()
    {
        $data = ['option|with|pipe', 'normal_option'];

        $result = $this->fieldtype->save($data);

        // Pipes should be escaped
        $this->assertEquals('option\|with\|pipe|normal_option', $result);
    }

    /**
     * Test save with values containing backslashes
     */
    public function testSaveValuesWithBackslashes()
    {
        $data = ['option\\with\\backslash', 'normal_option'];

        $result = $this->fieldtype->save($data);

        // Backslashes should be escaped
        $this->assertEquals('option\\\\with\\\\backslash|normal_option', $result);
    }

    /**
     * Test save with mixed data types
     */
    public function testSaveMixedDataTypes()
    {
        $data = ['string_option', 123, true];

        $result = $this->fieldtype->save($data);

        // All values should be converted to strings
        $this->assertEquals('string_option|123|1', $result);
    }

    /**
     * Test save with empty strings in array
     */
    public function testSaveWithEmptyStrings()
    {
        $data = ['option1', '', 'option2'];

        $result = $this->fieldtype->save($data);

        $this->assertEquals('option1||option2', $result);
    }
}

// EOF
