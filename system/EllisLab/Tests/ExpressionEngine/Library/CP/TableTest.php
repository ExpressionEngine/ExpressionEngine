<?php
namespace EllisLab\Tests\ExpressionEngine\Library\CP;

use EllisLab\ExpressionEngine\Library\CP\Table;

class TableTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Test setColumns() method
	 *
	 * @dataProvider tableColumnsDataProvider
	 */
	public function testSetColumns($input, $expected, $description)
	{
		$table = new Table();
		$table->setColumns($input);
		$this->assertEquals($expected, $table->columns, $description);
	}

	public function tableColumnsDataProvider()
	{
		$return = array();

		$input = array(
			'Table Name',
			'Records',
			'Size',
			'Manage' => array(
				'type'	=> Table::COL_TOOLBAR
			),
			'Status' => array(
				'type'	=> Table::COL_STATUS
			),
			array(
				'type'	=> Table::COL_CHECKBOX
			)
		);

		$expected = array(
			'Table Name' => array(
				'encode'	=> FALSE,
				'sort'		=> TRUE,
				'type'		=> Table::COL_TEXT
			),
			'Records' => array(
				'encode'	=> FALSE,
				'sort'		=> TRUE,
				'type'		=> Table::COL_TEXT
			),
			'Size' => array(
				'encode'	=> FALSE,
				'sort'		=> TRUE,
				'type'		=> Table::COL_TEXT
			),
			'Manage' => array(
				'encode'	=> FALSE,
				'sort'		=> FALSE,
				'type'		=> Table::COL_TOOLBAR
			),
			'Status' => array(
				'encode'	=> FALSE,
				'sort'		=> TRUE,
				'type'		=> Table::COL_STATUS
			),
			array(
				'encode'	=> FALSE,
				'sort'		=> FALSE,
				'type'		=> Table::COL_CHECKBOX
			)
		);

		$return[] = array($input, $expected, 'Test columns');

		return $return;
	}

	/**
	 * Test setData() method
	 *
	 * @dataProvider tableDataProvider
	 */
	public function testSetData($data, $expected, $columns, $description)
	{
		$table = new Table();
		$table->setColumns($columns);
		$table->setData($data);
		$this->assertEquals($expected, $table->viewData(), $description);
	}

	public function tableDataProvider()
	{
		$return = array();

		$columns = array(
			'Table Name',
			'Records',
			'Size',
			'Manage' => array(
				'type'	=> Table::COL_TOOLBAR
			),
			'Status' => array(
				'type'		=> Table::COL_STATUS,
				'encode'	=> TRUE
			),
			array(
				'type'	=> Table::COL_CHECKBOX
			)
		);

		$expected_cols = array(
			'Table Name' => array(
				'encode'	=> FALSE,
				'sort'		=> TRUE,
				'type'		=> Table::COL_TEXT
			),
			'Records' => array(
				'encode'	=> FALSE,
				'sort'		=> TRUE,
				'type'		=> Table::COL_TEXT
			),
			'Size' => array(
				'encode'	=> FALSE,
				'sort'		=> TRUE,
				'type'		=> Table::COL_TEXT
			),
			'Manage' => array(
				'encode'	=> FALSE,
				'sort'		=> FALSE,
				'type'		=> Table::COL_TOOLBAR
			),
			'Status' => array(
				'encode'	=> TRUE,
				'sort'		=> TRUE,
				'type'		=> Table::COL_STATUS
			),
			array(
				'encode'	=> FALSE,
				'sort'		=> FALSE,
				'type'		=> Table::COL_CHECKBOX
			)
		);

		$data = array(
			array(
				'col 1 data',
				'col 2 data',
				'col 3 data',
				array('toolbar_items' =>
					array('view' => 'http://test/')
				),
				'status',
				array('name' => 'table[]', 'value' => 'test')
			),
			array(
				'col 1 data 2',
				'col 2 data 2',
				NULL,
				array('toolbar_items' =>
					array('view' => 'http://test/2')
				),
				'status',
				array('name' => 'table[]', 'value' => 'test2')
			)
		);

		$expected = array(
			'base_url'	=> NULL,
			'search'	=> NULL,
			'wrap'		=> TRUE,
			'sort_col'	=> 'Table Name',
			'sort_dir'	=> 'asc',
			'columns'	=> $expected_cols,
			'data'		=> array(
				array(
					array(
						'content' 	=> 'col 1 data',
						'type'		=> Table::COL_TEXT,
						'encode'	=> FALSE
					),
					array(
						'content' 	=> 'col 2 data',
						'type'		=> Table::COL_TEXT,
						'encode'	=> FALSE
					),
					array(
						'content' 	=> 'col 3 data',
						'type'		=> Table::COL_TEXT,
						'encode'	=> FALSE
					),
					array(
						'content' 	=> '',
						'type'		=> Table::COL_TOOLBAR,
						'encode'	=> FALSE,
						'toolbar_items'	=> array('view' => 'http://test/'),
					),
					array(
						'content' 	=> 'status',
						'type'		=> Table::COL_STATUS,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> '',
						'type'		=> Table::COL_CHECKBOX,
						'encode'	=> FALSE,
						'name'		=> 'table[]',
						'value'		=> 'test'
					)
				),
				array(
					array(
						'content' 	=> 'col 1 data 2',
						'type'		=> Table::COL_TEXT,
						'encode'	=> FALSE
					),
					array(
						'content' 	=> 'col 2 data 2',
						'type'		=> Table::COL_TEXT,
						'encode'	=> FALSE
					),
					array(
						'content' 	=> NULL,
						'type'		=> Table::COL_TEXT,
						'encode'	=> FALSE
					),
					array(
						'content' 	=> '',
						'type'		=> Table::COL_TOOLBAR,
						'encode'	=> FALSE,
						'toolbar_items'	=> array('view' => 'http://test/2'),
					),
					array(
						'content' 	=> 'status',
						'type'		=> Table::COL_STATUS,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> '',
						'type'		=> Table::COL_CHECKBOX,
						'encode'	=> FALSE,
						'name'		=> 'table[]',
						'value'		=> 'test2'
					)
				)
			)
		);

		$return[] = array($data, $expected, $columns, 'Test data');

		return $return;
	}
}