<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../MultiSelectTestBase.php';

/**
 * Test Multi Select fieldtype renderTableCell method
 */
class MultiSelectRenderTableCellTest extends MultiSelectTestBase
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
     * Test renderTableCell method with pipe-delimited data
     */
    public function testRenderTableCellPipeDelimited()
    {
        $data = 'option1|option2';
        $field_id = '1';
        $entry = (object) ['entry_id' => '1'];

        $result = $this->fieldtype->renderTableCell($data, $field_id, $entry);

        // Should return the mapped labels
        $this->assertEquals('Option 1, Option 2', $result);
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

        $this->assertEquals('Option 1', $result);
    }

    /**
     * Test renderTableCell method with array data
     */
    public function testRenderTableCellArrayData()
    {
        $data = ['option1', 'option2'];
        $field_id = '1';
        $entry = (object) ['entry_id' => '1'];

        $result = $this->fieldtype->renderTableCell($data, $field_id, $entry);

        $this->assertEquals('Option 1, Option 2', $result);
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
        $this->mockFieldOptions([
            'option1' => 'Option & "Quote"',
            'option2' => 'Option <tag>'
        ]);

        $data = 'option1|option2';
        $field_id = '1';
        $entry = (object) ['entry_id' => '1'];

        $result = $this->fieldtype->renderTableCell($data, $field_id, $entry);

        $this->assertEquals('Option & "Quote", Option <tag>', $result);
    }

    /**
     * Test renderTableCell method with numeric field_id
     */
    public function testRenderTableCellNumericFieldId()
    {
        $data = 'option1|option2';
        $field_id = 123;
        $entry = (object) ['entry_id' => '1'];

        $result = $this->fieldtype->renderTableCell($data, $field_id, $entry);

        $this->assertEquals('Option 1, Option 2', $result);
    }

    /**
     * Test renderTableCell method with string field_id
     */
    public function testRenderTableCellStringFieldId()
    {
        $data = 'option1|option2';
        $field_id = 'field_123';
        $entry = (object) ['entry_id' => '1'];

        $result = $this->fieldtype->renderTableCell($data, $field_id, $entry);

        $this->assertEquals('Option 1, Option 2', $result);
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
            $this->assertEquals('Option 1', $result);
        }
    }

    /**
     * Test renderTableCell method with unmapped values
     */
    public function testRenderTableCellUnmappedValues()
    {
        $data = 'unknown1|unknown2';
        $field_id = '1';
        $entry = (object) ['entry_id' => '1'];

        $result = $this->fieldtype->renderTableCell($data, $field_id, $entry);

        $this->assertEquals('unknown1, unknown2', $result);
    }

    /**
     * Test renderTableCell method with mixed mapped and unmapped values
     */
    public function testRenderTableCellMixedValues()
    {
        $data = 'option1|unknown|option2';
        $field_id = '1';
        $entry = (object) ['entry_id' => '1'];

        $result = $this->fieldtype->renderTableCell($data, $field_id, $entry);

        $this->assertEquals('Option 1, unknown, Option 2', $result);
    }
}

// EOF

