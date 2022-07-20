<?php
namespace ExpressionEngine\Tests\Library\CP\Form\Fields;

use ExpressionEngine\Library\CP\Form\Fields\Table;
use PHPUnit\Framework\TestCase;

class TableTest extends TestCase
{
    /**
     * @var array
     */
    protected $test_columns = [
        'details' => ['sort' => false],
        'value' => ['sort' => false],
    ];

    /**
     * @var array
     */
    protected $test_options = [
        'lang_cols' => true,
        'class' => 'test_css_class'
    ];

    /**
     * @var \string[][]
     */
    protected $test_table_data = [
        [
            'col1' => 'foo',
            'col2' => 'bar'
        ],
        [
            'col1' => 'foz',
            'col2' => 'baz'
        ]
    ];

    public function testFieldInstanceHtmlFieldObj()
    {
        $field = new Table;
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\Html', $field);
    }

    /**
     * @return Table
     */
    public function testSetColumnsReturnInstance(): Table
    {
        $field = new Table;
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->setColumns($this->test_columns));
        return $field;
    }

    /**
     * @depends testSetColumnsReturnInstance
     * @param Table $field
     * @return Table
     */
    public function testGetColumnsReturnsAccurate(Table $field): Table
    {
        $this->assertEquals($this->test_columns, $field->getColumns());
        $this->assertEquals($this->test_columns, $field->get('columns'));
        return $field;
    }

    /**
     * @return Table
     */
    public function testSetOptionsReturnInstance(): Table
    {
        $field = new Table;
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->setOptions($this->test_options));
        return $field;
    }

    /**
     * @depends testSetOptionsReturnInstance
     * @param Table $field
     * @return Table
     */
    public function testGetOptionsReturnsAccurate(Table $field): Table
    {
        $this->assertEquals($this->test_options, $field->getOptions());
        $this->assertEquals($this->test_options, $field->get('table_options'));
        return $field;
    }

    /**
     * @return Table
     */
    public function testSetNoResultsReturnInstance(): Table
    {
        $field = new Table;
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->setNoResultsText('test-text', 'test-action-text'));
        return $field;
    }

    /**
     * @depends testSetNoResultsReturnInstance
     * @param Table $field
     * @return Table
     */
    public function testGetNoResultsReturnsAccurate(Table $field): Table
    {
        $no_results = $field->getNoResultsText();
        $this->assertArrayHasKey('text', $no_results);
        $this->assertArrayHasKey('action_text', $no_results);
        $this->assertArrayHasKey('action_link', $no_results);
        $this->assertArrayHasKey('external', $no_results);
        $this->assertEquals('test-text', $no_results['text']);
        $this->assertEquals('test-action-text', $no_results['action_text']);
        $this->assertEquals($field->get('no_results_text'), $field->getNoResultsText());
        return $field;
    }

    /**
     * @return Table
     */
    public function testSetDataReturnInstance(): Table
    {
        $field = new Table;
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->setData($this->test_table_data));
        return $field;
    }

    /**
     * @depends testSetDataReturnInstance
     * @param Table $field
     * @return Table
     */
    public function testGetDataReturnsAccurate(Table $field): Table
    {
        $this->assertEquals($this->test_table_data, $field->getData());
        $this->assertEquals($this->test_table_data, $field->get('data'));
        return $field;
    }

    /**
     * @depends testGetDataReturnsAccurate
     * @param Table $field
     * @return Table
     */
    public function testAddRowReturnsInstance(Table $field): Table
    {
        $new_row = ['col1' => 'test', 'col2' => 'table'];
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->addRow($new_row));
        return $field;
    }

    /**
     * @depends testGetDataReturnsAccurate
     * @param Table $field
     * @return Table
     */
    public function testAddRowReturnsAccurateInstance(Table $field): Table
    {
        $new_row = ['col1' => 'test2', 'col2' => 'table2'];
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->addRow($new_row));

        $rows = $field->getData();
        $this->assertCount(4, $rows);
        $this->assertEquals($new_row, $rows['3']);
        return $field;
    }

    /**
     * @return Table
     */
    public function testSetBaseUrlReturnInstance(): Table
    {
        $field = new Table;
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->setBaseUrl(null));
        return $field;
    }

    /**
     * @depends testSetBaseUrlReturnInstance
     * @param Table $field
     * @return Table
     */
    public function testGetBaseUrlReturnsAccurate(Table $field): Table
    {
        $this->assertNull($field->getBaseUrl());
        $this->assertNull($field->get('base_url'));
        return $field;
    }
}
