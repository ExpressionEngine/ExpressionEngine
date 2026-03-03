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
 * Test for Checkboxes_ft::renderTableCell() method
 */
class CheckboxesRenderTableCellTest extends CheckboxesTestBase
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
     * Test renderTableCell with simple data
     */
    public function testRenderTableCellSimpleData()
    {
        $data = 'option1|option2';
        $fieldId = '1';
        $entry = (object) ['entry_id' => 1, 'title' => 'Test Entry'];

        $result = $this->fieldtype->renderTableCell($data, $fieldId, $entry);

        $this->assertEquals('option1, option2', $result);
    }

    /**
     * Test renderTableCell with single value
     */
    public function testRenderTableCellSingleValue()
    {
        $data = 'option1';
        $fieldId = '1';
        $entry = (object) ['entry_id' => 1, 'title' => 'Test Entry'];

        $result = $this->fieldtype->renderTableCell($data, $fieldId, $entry);

        $this->assertEquals('option1', $result);
    }

    /**
     * Test renderTableCell with empty data
     */
    public function testRenderTableCellEmptyData()
    {
        $data = '';
        $fieldId = '1';
        $entry = (object) ['entry_id' => 1, 'title' => 'Test Entry'];

        $result = $this->fieldtype->renderTableCell($data, $fieldId, $entry);

        $this->assertEquals('', $result);
    }

    /**
     * Test renderTableCell with null data
     */
    public function testRenderTableCellNullData()
    {
        $data = null;
        $fieldId = '1';
        $entry = (object) ['entry_id' => 1, 'title' => 'Test Entry'];

        $result = $this->fieldtype->renderTableCell($data, $fieldId, $entry);

        $this->assertEquals('', $result);
    }

    /**
     * Test renderTableCell with encoded data
     */
    public function testRenderTableCellEncodedData()
    {
        $data = encode_multi_field(['option1', 'option2']);
        $fieldId = '1';
        $entry = (object) ['entry_id' => 1, 'title' => 'Test Entry'];

        $result = $this->fieldtype->renderTableCell($data, $fieldId, $entry);

        $this->assertEquals('option1, option2', $result);
    }

    /**
     * Test renderTableCell with value-label pairs
     */
    public function testRenderTableCellWithValueLabelPairs()
    {
        $data = 'option1|option2';
        $fieldId = '1';
        $entry = (object) ['entry_id' => 1, 'title' => 'Test Entry'];

        // Set up value-label pairs in settings
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'option1' => 'Option One',
                'option2' => 'Option Two'
            ]
        ]);

        $result = $fieldtype->renderTableCell($data, $fieldId, $entry);

        // Should return the mapped labels
        $this->assertEquals('Option One, Option Two', $result);
    }

    /**
     * Test renderTableCell with different field IDs
     */
    public function testRenderTableCellDifferentFieldIds()
    {
        $data = 'option1|option2';
        $fieldId = '5';
        $entry = (object) ['entry_id' => 1, 'title' => 'Test Entry'];

        $result = $this->fieldtype->renderTableCell($data, $fieldId, $entry);

        $this->assertEquals('option1, option2', $result);
    }

    /**
     * Test renderTableCell with different entry objects
     */
    public function testRenderTableCellDifferentEntries()
    {
        $data = 'option1|option2';
        $fieldId = '1';
        $entry = (object) ['entry_id' => 42, 'title' => 'Another Entry'];

        $result = $this->fieldtype->renderTableCell($data, $fieldId, $entry);

        $this->assertEquals('option1, option2', $result);
    }

    /**
     * Test renderTableCell with special characters
     */
    public function testRenderTableCellSpecialCharacters()
    {
        $data = 'option&|option<>';
        $fieldId = '1';
        $entry = (object) ['entry_id' => 1, 'title' => 'Test Entry'];

        $result = $this->fieldtype->renderTableCell($data, $fieldId, $entry);

        // Should return the decoded values without HTML encoding
        $this->assertEquals('option&, option<>', $result);
    }

    /**
     * Test renderTableCell with large data set
     */
    public function testRenderTableCellLargeDataSet()
    {
        $data = 'option1|option2|option3|option4|option5|option6|option7|option8|option9|option10';
        $fieldId = '1';
        $entry = (object) ['entry_id' => 1, 'title' => 'Test Entry'];

        $result = $this->fieldtype->renderTableCell($data, $fieldId, $entry);

        $expected = 'option1, option2, option3, option4, option5, option6, option7, option8, option9, option10';
        $this->assertEquals($expected, $result);
    }
}

// EOF
