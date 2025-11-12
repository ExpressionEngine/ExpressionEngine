<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../RadioTestBase.php';

use Mockery as m;

/**
 * Test for Radio_ft::renderTableCell() method
 */
class RadioRenderTableCellTest extends RadioTestBase
{
    /**
     * @var Radio_ft
     */
    protected $fieldtype;

    public function setUp(): void
    {
        parent::setUp();
        $this->fieldtype = $this->getMockRadioFieldtypeWithSettings();
    }

    /**
     * Test renderTableCell with simple value
     */
    public function testRenderTableCellSimpleValue()
    {
        $data = 'option1';
        $fieldId = 1;
        $entry = (object) ['entry_id' => 1, 'title' => 'Test Entry'];

        $result = $this->fieldtype->renderTableCell($data, $fieldId, $entry);
        $this->assertEquals('option1', $result);
    }

    /**
     * Test renderTableCell with different value
     */
    public function testRenderTableCellDifferentValue()
    {
        $data = 'option2';
        $fieldId = 2;
        $entry = (object) ['entry_id' => 2, 'title' => 'Another Entry'];

        $result = $this->fieldtype->renderTableCell($data, $fieldId, $entry);
        $this->assertEquals('option2', $result);
    }

    /**
     * Test renderTableCell with empty string
     */
    public function testRenderTableCellEmptyString()
    {
        $data = '';
        $fieldId = 1;
        $entry = (object) ['entry_id' => 1];

        $result = $this->fieldtype->renderTableCell($data, $fieldId, $entry);
        $this->assertEquals('', $result);
    }

    /**
     * Test renderTableCell with null data
     */
    public function testRenderTableCellNullData()
    {
        $data = null;
        $fieldId = 1;
        $entry = (object) ['entry_id' => 1];

        $result = $this->fieldtype->renderTableCell($data, $fieldId, $entry);
        $this->assertEquals('', $result);
    }

    /**
     * Test renderTableCell with numeric value
     */
    public function testRenderTableCellNumericValue()
    {
        $data = '123';
        $fieldId = 1;
        $entry = (object) ['entry_id' => 1];

        $result = $this->fieldtype->renderTableCell($data, $fieldId, $entry);
        $this->assertEquals('123', $result);
    }

    /**
     * Test renderTableCell with value that has label mapping
     */
    public function testRenderTableCellWithLabelMapping()
    {
        $fieldtype = $this->getMockRadioFieldtypeWithSettings([
            'value_label_pairs' => [
                'option1' => 'First Option',
                'option2' => 'Second Option'
            ]
        ]);

        $data = 'option1';
        $fieldId = 1;
        $entry = (object) ['entry_id' => 1];

        $result = $fieldtype->renderTableCell($data, $fieldId, $entry);
        $this->assertEquals('option1', $result);
    }

    /**
     * Test renderTableCell with special characters
     */
    public function testRenderTableCellSpecialChars()
    {
        $data = 'option_1';
        $fieldId = 1;
        $entry = (object) ['entry_id' => 1];

        $result = $this->fieldtype->renderTableCell($data, $fieldId, $entry);
        $this->assertEquals('option_1', $result);
    }

    /**
     * Test renderTableCell with different field IDs
     */
    public function testRenderTableCellDifferentFieldIds()
    {
        $data = 'option1';
        $fieldIds = [1, 5, 10, 100];

        foreach ($fieldIds as $fieldId) {
            $entry = (object) ['entry_id' => 1];
            $result = $this->fieldtype->renderTableCell($data, $fieldId, $entry);
            $this->assertEquals('option1', $result, "Should work with field ID: $fieldId");
        }
    }

    /**
     * Test renderTableCell with different entry objects
     */
    public function testRenderTableCellDifferentEntries()
    {
        $data = 'option1';
        $fieldId = 1;

        $entries = [
            (object) ['entry_id' => 1, 'title' => 'Entry 1'],
            (object) ['entry_id' => 2, 'title' => 'Entry 2', 'status' => 'open'],
            (object) ['entry_id' => 3],
            (object) []
        ];

        foreach ($entries as $entry) {
            $result = $this->fieldtype->renderTableCell($data, $fieldId, $entry);
            $this->assertEquals('option1', $result);
        }
    }

    /**
     * Test renderTableCell with mock data processing
     */
    public function testRenderTableCellWithMockProcessing()
    {
        $data = 'option1';
        $fieldId = 1;
        $entry = (object) ['entry_id' => 1];

        // Test with the actual fieldtype mock
        $result = $this->fieldtype->renderTableCell($data, $fieldId, $entry);
        $this->assertEquals('option1', $result);
    }
}

// EOF
