<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Library\CP;

use EllisLab\ExpressionEngine\Library\CP\Table;
use PHPUnit\Framework\TestCase;

class TableTest extends TestCase {

	private $table;

	public function tearDown()
	{
		unset($this->table);
	}

	/**
	 * Test setData() method
	 *
	 * @dataProvider tableDataProvider
	 */
	public function testsTableCreation($config, $data, $expected, $columns, $description)
	{
		$this->table = new Table($config);
		$this->table->setLocalize(new Localize());
		$this->table->setColumns($columns);
		$this->table->setData($data);
		$this->assertEquals($expected, $this->table->viewData(), $description);
	}

	public function tableDataProvider()
	{
		$return = array();

		// Empty table config for now
		$config = array();

		$no_results_empty = array('text' => 'no_rows_returned', 'action_text' => '', 'action_link' => '');

		// Given these columns of input...
		$columns = array(
			'Name',
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

		// We should get this on output
		$expected_cols = array(
			array(
				'label'		=> 'Name',
				'encode'	=> TRUE,
				'sort'		=> TRUE,
				'type'		=> Table::COL_TEXT
			),
			array(
				'label'		=> 'Records',
				'encode'	=> TRUE,
				'sort'		=> TRUE,
				'type'		=> Table::COL_TEXT
			),
			array(
				'label'		=> 'Size',
				'encode'	=> TRUE,
				'sort'		=> TRUE,
				'type'		=> Table::COL_TEXT
			),
			array(
				'label'		=> 'Manage',
				'encode'	=> TRUE,
				'sort'		=> FALSE,
				'type'		=> Table::COL_TOOLBAR
			),
			array(
				'label'		=> 'Status',
				'encode'	=> TRUE,
				'sort'		=> TRUE,
				'type'		=> Table::COL_STATUS
			),
			array(
				'label'		=> NULL,
				'encode'	=> TRUE,
				'sort'		=> FALSE,
				'type'		=> Table::COL_CHECKBOX
			)
		);

		// And with this input of table data...
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

		$expected_base_config = array(
			'base_url'          => NULL,
			'lang_cols'         => TRUE,
			'search'            => NULL,
			'wrap'              => TRUE,
			'no_results'        => $no_results_empty,
			'sort_col'          => 'Name',
			'sort_dir'          => 'asc',
			'limit'             => 25,
			'page'              => 1,
			'total_rows'        => 2,
			'grid_input'        => FALSE,
			'reorder'           => FALSE,
			'reorder_header'    => FALSE,
			'class'             => '',
			'table_attrs'       => array(),
			'sortable'          => TRUE,
			'subheadings'       => FALSE,
			'columns'           => $expected_cols,
			'action_buttons'    => array(),
			'action_content'    => NULL,
			'sort_col_qs_var'   => 'sort_col',
			'sort_dir_qs_var'   => 'sort_dir',
			'autosort'          => FALSE,
			'autosearch'        => FALSE,
			'checkbox_header'   => FALSE,
			'attrs'             => array()
		);

		// We should get this entire array back when we ask for
		// the table's view data
		$expected = array_merge(
			$expected_base_config,
			array(
				'data' => array(
					array(
						'attrs' => array(),
						'columns' => array(
							array(
								'content' 	=> 'col 1 data',
								'type'		=> Table::COL_TEXT,
								'encode'	=> TRUE
							),
							array(
								'content' 	=> 'col 2 data',
								'type'		=> Table::COL_TEXT,
								'encode'	=> TRUE
							),
							array(
								'content' 	=> 'col 3 data',
								'type'		=> Table::COL_TEXT,
								'encode'	=> TRUE
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
						)
					),
					array(
						'attrs' => array(),
						'columns' => array(
							array(
								'content' 	=> 'col 1 data 2',
								'type'		=> Table::COL_TEXT,
								'encode'	=> TRUE
							),
							array(
								'content' 	=> 'col 2 data 2',
								'type'		=> Table::COL_TEXT,
								'encode'	=> TRUE
							),
							array(
								'content' 	=> NULL,
								'type'		=> Table::COL_TEXT,
								'encode'	=> TRUE
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
				)
			)
		);

		$return[] = array($config, $data, $expected, $columns, 'Test sample table creation');

		$expected = array_merge(
			$expected_base_config,
			array(
				'sort_col'		=> NULL,
				'total_rows'	=> 0,
				'columns'		=> array(),
				'data'			=> array()
			)
		);

		$return[] = array($config, array(), $expected, array(), 'Test empty table');

		$expected = array_merge(
			$expected_base_config,
			array(
				'total_rows'	=> 0,
				'data'			=> array()
			)
		);

		$return[] = array($config, array(), $expected, $columns, 'Test table with columns but no data');

		$no_results = array('text' => 'no_results', 'action_text' => 'test', 'action_link' => 'test');

		$config = array(
			'wrap'			=> FALSE,
			'sort_col'		=> 'Records',
			'sort_dir'		=> 'desc',
			'search'		=> 'My search',
			'no_results'	=> $no_results
		);

		$expected = array_merge(
			$expected_base_config,
			array_merge($config, array(
				'total_rows'	=> 0,
				'data' => array()
			))
		);

		$return[] = array($config, array(), $expected, $columns, 'Test with alternate config');

		$config = array(
			'autosort'	=> FALSE,
			'lang_cols'	=> FALSE,
			'sort_col'	=> 'Name',
			'sort_dir'	=> 'desc',
			'sortable'	=> TRUE,
			'limit'		=> 50
		);

		$expected = array_merge(
			$expected_base_config,
			array(
				'lang_cols'	=> FALSE,
				'sort_col'	=> 'Name',
				'sort_dir'	=> 'desc',
				'sortable'	=> TRUE,
				'limit'		=> 50,
				'data'		=> array(
					array(
						'attrs' => array(),
						'columns' => array(
							array(
								'content' 	=> 'col 1 data',
								'type'		=> Table::COL_TEXT,
								'encode'	=> TRUE
							),
							array(
								'content' 	=> 'col 2 data',
								'type'		=> Table::COL_TEXT,
								'encode'	=> TRUE
							),
							array(
								'content' 	=> 'col 3 data',
								'type'		=> Table::COL_TEXT,
								'encode'	=> TRUE
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
						)
					),
					array(
						'attrs' => array(),
						'columns' => array(
							array(
								'content' 	=> 'col 1 data 2',
								'type'		=> Table::COL_TEXT,
								'encode'	=> TRUE
							),
							array(
								'content' 	=> 'col 2 data 2',
								'type'		=> Table::COL_TEXT,
								'encode'	=> TRUE
							),
							array(
								'content' 	=> NULL,
								'type'		=> Table::COL_TEXT,
								'encode'	=> TRUE
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
				)
			)
		);

		$return[] = array($config, $data, $expected, $columns, 'Test autosort off');

		$config['autosort'] = TRUE;

		$expected = array(
			'base_url'          => NULL,
			'lang_cols'         => FALSE,
			'search'            => NULL,
			'wrap'              => TRUE,
			'no_results'        => $no_results_empty,
			'sort_col'          => 'Name',
			'sort_dir'          => 'desc',
			'limit'             => 50,
			'page'              => 1,
			'total_rows'        => 2,
			'grid_input'        => FALSE,
			'reorder'           => FALSE,
			'reorder_header'    => FALSE,
			'class'             => '',
			'table_attrs'       => array(),
			'sortable'          => TRUE,
			'subheadings'       => FALSE,
			'columns'           => $expected_cols,
			'action_buttons'    => array(),
			'action_content'    => NULL,
			'sort_col_qs_var'   => 'sort_col',
			'sort_dir_qs_var'   => 'sort_dir',
			'autosort'          => TRUE,
			'autosearch'        => FALSE,
			'checkbox_header'   => FALSE,
			'attrs'             => array(),
			'data'              => array(
				array(
					'attrs' => array(),
					'columns' => array(
						array(
							'content' 	=> 'col 1 data 2',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 2 data 2',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> NULL,
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
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
				),
				array(
					'attrs' => array(),
					'columns' => array(
						array(
							'content' 	=> 'col 1 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 2 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 3 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
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
					)
				)
			)
		);

		$return[] = array($config, $data, $expected, $columns, 'Test autosort on');

		$config['autosearch'] = TRUE;
		$config['search'] = 'data 2';
		$config['attrs'] = array('data-test' => 'test');

		$expected = array(
			'base_url'          => NULL,
			'lang_cols'         => FALSE,
			'search'            => 'data 2',
			'wrap'              => TRUE,
			'no_results'        => $no_results_empty,
			'sort_col'          => 'Name',
			'sort_dir'          => 'desc',
			'limit'             => 50,
			'page'              => 1,
			'total_rows'        => 1,
			'grid_input'        => FALSE,
			'reorder'           => FALSE,
			'reorder_header'    => FALSE,
			'class'             => '',
			'table_attrs'       => array('data-test' => 'test'),
			'sortable'          => TRUE,
			'subheadings'       => FALSE,
			'columns'           => $expected_cols,
			'action_buttons'    => array(),
			'action_content'    => NULL,
			'sort_col_qs_var'   => 'sort_col',
			'sort_dir_qs_var'   => 'sort_dir',
			'autosort'          => TRUE,
			'autosearch'        => TRUE,
			'checkbox_header'   => FALSE,
			'attrs'             => array('data-test' => 'test'),
			'data'              => array(
				array(
					'attrs' => array(),
					'columns' => array(
						array(
							'content' 	=> 'col 1 data 2',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 2 data 2',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> NULL,
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
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
			)
		);

		$return[] = array($config, $data, $expected, $columns, 'Test autosort search');

		$config['search'] = 'col 1 data 2';
		$expected['search'] = 'col 1 data 2';

		// Because strpos of entire string will return 0, make sure we're === FALSE there
		$return[] = array($config, $data, $expected, $columns, 'Test autosort search with entire string');

		$config['search'] = '';
		$config['limit'] = 1;

		$expected['search'] = '';
		$expected['limit'] = 1;
		$expected['total_rows'] = 2;
		$expected['data'] = array(
			array(
				'attrs' => array(),
				'columns' => array(
					array(
						'content' 	=> 'col 1 data 2',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> 'col 2 data 2',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> NULL,
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
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

		$return[] = array($config, $data, $expected, $columns, 'Test pagination');

		$config['limit'] = 1;
		$config['page'] = 2;

		$expected['page'] = 2;
		$expected['data'] = array(
			array(
				'attrs' => array(),
				'columns' => array(
					array(
						'content' 	=> 'col 1 data',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> 'col 2 data',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> 'col 3 data',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
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
				)
			)
		);

		$return[] = array($config, $data, $expected, $columns, 'Test pagination 2');

		$config['limit'] = 20;
		$config['page'] = 1;
		$config['subheadings'] = TRUE;

		$config = array('subheadings' => TRUE);

		$data = array(
			'heading3' => array(
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
			),
			'heading1' => array(
				array(
					'col 2 data',
					'col 3 data',
					'col 1 data',
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
				),
				array(
					'col 3 data 3',
					'col 2 data 3',
					NULL,
					array('toolbar_items' =>
						array('view' => 'http://test/2')
					),
					'status',
					array('name' => 'table[]', 'value' => 'test2')
				)
			),
			'heading2' => array(
				array(
					'col 3 data',
					'col 2 data',
					'col 1 data',
					array('toolbar_items' =>
						array('view' => 'http://test/')
					),
					'status',
					array('name' => 'table[]', 'value' => 'test')
				)
			)
		);

		$expected = $expected_base_config;
		$expected['total_rows'] = 6;
		$expected['limit'] = 0;
		$expected['subheadings'] = TRUE;
		$expected['data'] = array(
			'heading3' => array(
				array(
					'attrs' => array(),
					'columns' => array(
						array(
							'content' 	=> 'col 1 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 2 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 3 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
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
					)
				),
				array(
					'attrs' => array(),
					'columns' => array(
						array(
							'content' 	=> 'col 1 data 2',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 2 data 2',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> NULL,
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
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
			),
			'heading1' => array(
				array(
					'attrs' => array(),
					'columns' => array(
						array(
							'content' 	=> 'col 2 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 3 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 1 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
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
					)
				),
				array(
					'attrs' => array(),
					'columns' => array(
						array(
							'content' 	=> 'col 1 data 2',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 2 data 2',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> NULL,
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
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
				),
				array(
					'attrs' => array(),
					'columns' => array(
						array(
							'content' 	=> 'col 3 data 3',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 2 data 3',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> NULL,
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
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
			),
			'heading2' => array(
				array(
					'attrs' => array(),
					'columns' => array(
						array(
							'content' 	=> 'col 3 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 2 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 1 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
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
					)
				)
			)
		);

		$return[] = array($config, $data, $expected, $columns, 'Test subheadings');

		$config['autosort'] = TRUE;
		$expected['autosort'] = TRUE;
		$expected['data'] = array(
			'heading1' => array(
				array(
					'attrs' => array(),
					'columns' => array(
						array(
							'content' 	=> 'col 1 data 2',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 2 data 2',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> NULL,
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
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
				),
				array(
					'attrs' => array(),
					'columns' => array(
						array(
							'content' 	=> 'col 2 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 3 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 1 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
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
					)
				),
				array(
					'attrs' => array(),
					'columns' => array(
						array(
							'content' 	=> 'col 3 data 3',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 2 data 3',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> NULL,
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
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
			),
			'heading2' => array(
				array(
					'attrs' => array(),
					'columns' => array(
						array(
							'content' 	=> 'col 3 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 2 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 1 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
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
					)
				)
			),
			'heading3' => array(
				array(
					'attrs' => array(),
					'columns' => array(
						array(
							'content' 	=> 'col 1 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 2 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 3 data',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
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
					)
				),
				array(
					'attrs' => array(),
					'columns' => array(
						array(
							'content' 	=> 'col 1 data 2',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> 'col 2 data 2',
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
						),
						array(
							'content' 	=> NULL,
							'type'		=> Table::COL_TEXT,
							'encode'	=> TRUE
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
			)
		);

		$return[] = array($config, $data, $expected, $columns, 'Test subheadings with autosort');

		$config['autosearch'] = TRUE;
		$config['search'] = 'col 1';
		$expected['autosearch'] = TRUE;
		$expected['search'] = 'col 1';
		$expected['total_rows'] = 5;
		$expected['subheadings'] = FALSE;
		$expected['data'] = array(
			array(
				'attrs' => array(),
				'columns' => array(
					array(
						'content' 	=> 'col 1 data',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> 'col 2 data',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> 'col 3 data',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
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
				)
			),
			array(
				'attrs' => array(),
				'columns' => array(
					array(
						'content' 	=> 'col 1 data 2',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> 'col 2 data 2',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> NULL,
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
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
			),
			array(
				'attrs' => array(),
				'columns' => array(
					array(
						'content' 	=> 'col 1 data 2',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> 'col 2 data 2',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> NULL,
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
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
			),
			array(
				'attrs' => array(),
				'columns' => array(
					array(
						'content' 	=> 'col 2 data',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> 'col 3 data',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> 'col 1 data',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
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
				)
			),
			array(
				'attrs' => array(),
				'columns' => array(
					array(
						'content' 	=> 'col 3 data',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> 'col 2 data',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> 'col 1 data',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
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
				)
			)
		);

		$return[] = array($config, $data, $expected, $columns, 'Test subheadings with autosort and autosearch');

		return $return;
	}

	public function testSortByDiskSize()
	{
		$table = new Table(array('autosort' => TRUE, 'sort_col' => 'Size', 'sort_dir' => 'desc'));
		$table->setLocalize(new Localize());
		$table->setColumns(array(
			'Name',
			'Size' => array('encode' => FALSE)
		));
		$table->setData(array(
			array(
				'size1',
				'12 <abbr title="Gigabytes">GB</abbr>'
			),
			array(
				'size2',
				'64 <abbr title="Kilobytes">KB</abbr>'
			),
			array(
				'size3',
				'32 <abbr title="Megabytes">MB</abbr>'
			),
			array(
				'size4',
				'12.0 <abbr title="Kilobytes">KB</abbr>'
			),
			array(
				'size5',
				'123 MB'
			),
			array(
				'size6',
				'42.0 TB'
			),
			array(
				'size7',
				'3PB'
			)
		));
		$view_data = $table->viewData();

		$expected = array(
			array(
				'attrs' => array(),
				'columns' => array(
					array(
						'content' 	=> 'size7',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> '3PB',
						'type'		=> Table::COL_TEXT,
						'encode'	=> FALSE
					)
				)
			),
			array(
				'attrs' => array(),
				'columns' => array(
					array(
						'content' 	=> 'size6',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> '42.0 TB',
						'type'		=> Table::COL_TEXT,
						'encode'	=> FALSE
					)
				)
			),
			array(
				'attrs' => array(),
				'columns' => array(
					array(
						'content' 	=> 'size1',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> '12 <abbr title="Gigabytes">GB</abbr>',
						'type'		=> Table::COL_TEXT,
						'encode'	=> FALSE
					)
				)
			),
			array(
				'attrs' => array(),
				'columns' => array(
					array(
						'content' 	=> 'size5',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> '123 MB',
						'type'		=> Table::COL_TEXT,
						'encode'	=> FALSE
					)
				)
			),
			array(
				'attrs' => array(),
				'columns' => array(
					array(
						'content' 	=> 'size3',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> '32 <abbr title="Megabytes">MB</abbr>',
						'type'		=> Table::COL_TEXT,
						'encode'	=> FALSE
					)
				)
			),
			array(
				'attrs' => array(),
				'columns' => array(
					array(
						'content' 	=> 'size2',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> '64 <abbr title="Kilobytes">KB</abbr>',
						'type'		=> Table::COL_TEXT,
						'encode'	=> FALSE
					)
				)
			),
			array(
				'attrs' => array(),
				'columns' => array(
					array(
						'content' 	=> 'size4',
						'type'		=> Table::COL_TEXT,
						'encode'	=> TRUE
					),
					array(
						'content' 	=> '12.0 <abbr title="Kilobytes">KB</abbr>',
						'type'		=> Table::COL_TEXT,
						'encode'	=> FALSE
					)
				)
			)
		);

		$this->assertEquals($expected, $view_data['data'], 'Sort columns by disk size');
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @dataProvider badTableDataProvider
	 */
	public function testsTableThrowsException($config, $data, $columns, $description)
	{
		$this->table = new Table($config);
		$this->table->setColumns($columns);
		$this->table->setData($data);
	}

	public function badTableDataProvider()
	{
		$return = array();

		$config = array();

		$columns = array(
			'Name',
			'Records',
			'Size'
		);

		$data = array(
			array('test', 'test')
		);

		$return[] = array($config, $data, $columns, 'Test column count mismatch');

		$columns = array(
			'Name',
			'Manage' => array(
				'type'	=> Table::COL_TOOLBAR
			),
			array(
				'type'	=> Table::COL_CHECKBOX
			)
		);

		$data = array(
			array(
				'test',
				'test',
				array('name' => 'table[]')
			)
		);

		$return[] = array($config, $data, $columns, 'Test invalid data for checkboxes and toolbars');

		$data = array(
			array(
				'test',
				'test',
				array('name' => 'table[]', 'value' => 'test')
			)
		);

		$return[] = array($config, $data, $columns, 'Test invalid data for toolbars');

		$data = array(
			array(
				'test',
				array('toolbar_items' => array()),
				array('value' => 'test')
			)
		);

		$return[] = array($config, $data, $columns, 'Test invalid data for checkboxes');

		return $return;
	}
}

class Localize
{
	public function get_date_format($seconds = FALSE)
	{
		return '%n/%j/%Y';
	}

	public function string_to_timestamp($human_string, $localized = TRUE, $date_format = NULL)
	{
		return strtotime($human_string);
	}
}

// EOF
