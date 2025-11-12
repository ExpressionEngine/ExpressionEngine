<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../SelectTestBase.php';

/**
 * Test Select fieldtype renderTableCell method
 */
class SelectRenderTableCellTest extends SelectTestBase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->mockFieldOptions([
            'option1' => 'Option 1',
            'option2' => 'Option 2',
            'option3' => 'Option 3'
        ]);
    }

    /**
     * Test renderTableCell method with single value
     */
    public function testRenderTableCellSingleValue()
    {
        $data = 'option1';
        $field_id = '1';
        $entry = (object) ['entry_id' => '1'];

        $result = $this->fieldtype->renderTableCell($data, $field_id, $entry);

        // Should return the value processed through _parse_single
        $this->assertEquals('option1', $result);
    }

    /**
     * Test renderTableCell method with empty data
     */
    public function testRenderTableCellEmptyData()
    {
        $data = '';
        $field_id = '1';
        $entry = (object) ['entry_id' => '1'];

        $result = $this->fieldtype->renderTableCell($data, $field_id, $entry);

        $this->assertEquals('', $result);
    }

    /**
     * Test renderTableCell method with null data
     */
    public function testRenderTableCellNullData()
    {
        $data = null;
        $field_id = '1';
        $entry = (object) ['entry_id' => '1'];

        $result = $this->fieldtype->renderTableCell($data, $field_id, $entry);

        $this->assertEquals('', $result);
    }

    /**
     * Test renderTableCell method with special characters
     */
    public function testRenderTableCellSpecialCharacters()
    {
        $data = 'option & "quote"';
        $field_id = '1';
        $entry = (object) ['entry_id' => '1'];

        $result = $this->fieldtype->renderTableCell($data, $field_id, $entry);

        $this->assertEquals('option & "quote"', $result);
    }

    /**
     * Test renderTableCell method with numeric field_id
     */
    public function testRenderTableCellNumericFieldId()
    {
        $data = 'option1';
        $field_id = 123;
        $entry = (object) ['entry_id' => '1'];

        $result = $this->fieldtype->renderTableCell($data, $field_id, $entry);

        $this->assertEquals('option1', $result);
    }

    /**
     * Test renderTableCell method with string field_id
     */
    public function testRenderTableCellStringFieldId()
    {
        $data = 'option1';
        $field_id = 'field_123';
        $entry = (object) ['entry_id' => '1'];

        $result = $this->fieldtype->renderTableCell($data, $field_id, $entry);

        $this->assertEquals('option1', $result);
    }

    /**
     * Test renderTableCell method with different entry objects
     */
    public function testRenderTableCellDifferentEntries()
    {
        $data = 'option1';
        $field_id = '1';

        $entries = [
            (object) ['entry_id' => '1', 'title' => 'Entry 1'],
            (object) ['entry_id' => '2', 'title' => 'Entry 2'],
            (object) ['entry_id' => null],
            (object) [],
        ];

        foreach ($entries as $entry) {
            $result = $this->fieldtype->renderTableCell($data, $field_id, $entry);
            $this->assertEquals('option1', $result);
        }
    }

    /**
     * Test renderTableCell method with numeric value
     */
    public function testRenderTableCellNumericValue()
    {
        $data = '123';
        $field_id = '1';
        $entry = (object) ['entry_id' => '1'];

        $result = $this->fieldtype->renderTableCell($data, $field_id, $entry);

        $this->assertEquals('123', $result);
    }

    /**
     * Test renderTableCell method with zero value
     */
    public function testRenderTableCellZeroValue()
    {
        $data = '0';
        $field_id = '1';
        $entry = (object) ['entry_id' => '1'];

        $result = $this->fieldtype->renderTableCell($data, $field_id, $entry);

        $this->assertEquals('0', $result);
    }

    /**
     * Test renderTableCell method with very long value
     */
    public function testRenderTableCellLongValue()
    {
        $longValue = str_repeat('option_', 20);
        $data = $longValue;
        $field_id = '1';
        $entry = (object) ['entry_id' => '1'];

        $result = $this->fieldtype->renderTableCell($data, $field_id, $entry);

        $this->assertEquals($longValue, $result);
    }

    /**
     * Test renderTableCell method with boolean value
     */
    public function testRenderTableCellBooleanValue()
    {
        $data = '1';
        $field_id = '1';
        $entry = (object) ['entry_id' => '1'];

        $result = $this->fieldtype->renderTableCell($data, $field_id, $entry);

        $this->assertEquals('1', $result);
    }

    /**
     * Test renderTableCell method with nested options
     */
    public function testRenderTableCellNestedOptions()
    {
        $this->mockNestedFieldOptions();
        $data = 'option1';
        $field_id = '1';
        $entry = (object) ['entry_id' => '1'];

        $result = $this->fieldtype->renderTableCell($data, $field_id, $entry);

        $this->assertEquals('option1', $result);
    }
}

// EOF

