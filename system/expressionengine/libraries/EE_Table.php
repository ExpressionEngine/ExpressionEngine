<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Use the table library as usual, but with added steps to setup the datatable:
 *
 * 1. Name your columns and set their sorting properties:
 *
 * $this->table->set_columns(array('id' => FALSE, 'name' => TRUE));
 *
 * @todo sorting datatype? (bit of a pain to let them pick, must work in js and mysql, maybe just ask them what sql will sort on)
 * @todo if you just need non-ajax sorting, should be done here.
 * @todo allow Wes's tokens? 	'entry_id' => array('filter' => TRUE, 'token' => TRUE, 'alias' => 'id')
 * 
 * 
 * 2. Define a function in your controller that will act as the datasource.
 *
 * The function should return an array containing the following data:
 *
 * rows - an array of table rows. Each row is an array similar
 * to the normal add_row() parameter, but with a key equal to the column name.
 *
 * no_results - html to display if filtering results in no results
 *
 * Pagination settings:
 *
 * total_rows - number of rows without php
 * per_page - number of rows per page
 * *_link - link styles as per pagination
 * full_tag_* - as per pagination
 *
 * Using names from the previous example:
 * return array(
 *	   array('id' => 5, 'name' => 'pascal'),
 *	   array('id' => 3, 'name' => 'wes')
 * );
 *
 * Hint: This matches the db->result_array() output [db->result() will work as well].
 *
 * 
 * 3. Connect the datasource to your table:
 * $table_data = $this->table->datasource('somefunc');
 * 
 * If this is a filtering/pagination/sorting call, the request will stop here
 * the table will automatically be updated using the data returned from the
 * datasource. You should try to call this as soon as possible to avoid filtering
 * delays.
 *
 * Your datasource will receive an options parameter that contains the current
 * page and sorting requirements in this format:
 * 
 * array(
 *     'page' => 2,
 *     'sort' => array('name' => 'asc/desc'),
 *	   'columns' => array('id' => FALSE, 'name' => TRUE)
 * );
 *
 * Otherwise this call will return your datasource array, but *without* the
 * column names. This allows you to feed the data directly into add_row()
 * @todo just create the table and return that? have everything we need.
 *
 * 
 * 4. Include the javascript (tmpl and table plugins):
 * 
 * $this->cp->add_js_script('plugin' => array('tmpl', 'table'))
 *
 *
 * 5. Tying into the javascript:
 *
 * @todo The setup can be done automatically by grabbing all tables and
 * looking for the data-table_config property. That property contains a json
 * array that is passed to the plugin.
 * So I'll just touch on the jquery events in here. JS specific planning will go into the plugin file.
 *
 * The table plugin will modify the table as users interact with it. If your javascript
 * modifies the table or listens for events on its elements, you will need to observe
 * table updates to ensure that your code continues to function: (@todo crappy paragraph, pascal)
 * 
 * WIP list:
 *
 * $('table').bind('tablecreate')	// initial automatic setup
 * $('table').bind('tableupdate')	// changes (pagination/sorting/filtering)
 *
 * If you want to filter, you will need to connect the filtering plugin. You can
 * either give it a serializable set of form elements (form, or multiple inputs).
 * These will automatically be observed for changes.
 *
 * $('table').table('set_filter', $('form'));
 *
 * Or you can apply filters yourself, by passing a json object:
 *
 * $('table').table('add_filter', {'foo': 'bar'});
 *
 * multiple calls to add_filter stack, set_filter overrides all
 *
 * You can also remove filters:
 * $('table').table('remove_filter', 'key'/object/serializable);
 *
 * Or clear them:
 * $('table').table('clear_filter');
 *
 */

/**
 * Notes:
 *
 * Protected column names: data
 *
 */

class EE_Table extends CI_Table {

	protected $EE;
	protected $no_results = '';
	protected $jq_template = FALSE;
	protected $column_config = array();

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct($config = array())
	{
		parent::__construct($config);
		
		$this->EE =& get_instance();
	}
	
	// --------------------------------------------------------------------

	/**
	 * Setup the datasource
	 *
	 * @access	public
	 * @param	string	data callback function
	 * @param	mixed	default data that will later be passed in the get array
	 */
	function datasource($func, $options = array())
	{
		$settings = array(
			'page'			=> 1,
			'sort'			=> array(),		// column_name => value
			'columns'		=> $this->column_config
		);
		
		// override settings on non-ajax load
		// @todo @confirm @pk recursive, also tbl_ prefixed?
		foreach (array_keys($settings) as $key)
		{
			if (isset($options['tbl_'.$key]))
			{
				$settings[$key] = $options['tbl_'.$key];
				unset($options['tbl_'.$key]);
			}
		}
		
		// override settings
		if (isset($_GET['tbl_page']) && is_numeric($_GET['tbl_page']))
		{
			$settings['page'] = $_GET['tbl_page'];
		}

		if (isset($_GET['tbl_sorting']))
		{
			$settings['sorting'] = $_GET['tbl_sorting'];
		}

		
		$data = $this->EE->$func($settings, $options);
		$this->no_results = $data['no_results'];
		
		// returns PHP array (shown in js syntax for brevity):
		/*
		{
			no_results: 'something',
			total_rows: 44038,
			rows: [
				{key: value, key2: value2, key3: value3},
				{key: eulav, key2: eulav2, key3: eulav3},
			]
			
			@todo also need to support other table syntax:
			key: {class:foo, data:value}
		*/
		

		if (AJAX_REQUEST)
		{
			// do we need to apply a cell function?
			if ($this->function)
			{
				// @todo loop through rows array_map cells?
			}
			
			$this->EE->javascript->send_ajax_request(array(
				'rows'		 => $data['rows'],
				'total_rows' => $data['total_rows']
			));
		}
		
		// make sure we add a jq template
		$this->jq_template = TRUE;
		
		// remove the key information from the row data to make it usable
		// by the CI generate function. Unfortunately that means reordering
		// to match our columns. Easy enough, simply overwrite the booleans.
		foreach ($data['rows'] as &$row)
		{
			$row = array_values(array_merge($this->column_config, $row));
		}
		
		if ( ! count($data))
		{
			
			// @todo
		}
				
		$data['table_html'] = $this->generate($data['rows']);
		$data['pagination_html'] = '';
		
		return $data;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Setup columns
	 *
	 * @access	public
	 * @param	mixed	column key => sort[bool]
	 */
	public function set_columns($cols = array())
	{
		// @todo hook to register column?
		$this->column_config = $cols;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Setup columns
	 *
	 * @access	public
	 * @param	string	data callback function
	 */
	public function generate($table_data = NULL)
	{
		$this->_compile_template();
		
		if ( ! $this->jq_template)
		{
			return parent::generate($table_data);
		}
		
		$open_bak = $this->template['table_open'];
		$templates = array(
			'template' => '',
			'template_alt' => 'alt_'
		);
		
		// two templates for alternating rows
		// this may prove to be annoying with dynamic filtering
		foreach ($templates as $var => $k)
		{
			$temp = $this->template['row_'.$k.'start'];

			foreach($this->column_config as $column => $conf)
			{
				$temp .= $this->template['cell_'.$k.'start'];
				$temp .= '{{'.$column.'}}';
				$temp .= $this->template['cell_'.$k.'end'];
			}

			$temp .= $this->template['row_'.$k.'end'];
			$$var = $temp;
		}
		
		
		$jq_config = array(
			'columns'		=> $this->column_config,
			'template'		=> $template,
			'template_alt'	=> $template_alt,
			'empty_cells'	=> $this->empty_cells,
			'no_results'	=> $this->no_results
		);
		
		$table_config_data = 'data-table_config="'.form_prep($this->EE->javascript->generate_json($jq_config, TRUE)).'"';
		$this->template['table_open'] = str_replace('<table', '<table '.$table_config_data, $open_bak);
		
		$table = parent::generate($table_data);
		
		$this->template['table_open'] = $open_bak;
		return $table;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Setup async table refreshing
	 *
	 * @access	public
	 * @param	string	data callback function
	 */
	public function clear()
	{
		$this->column_config = array();
		$this->jq_template = FALSE;
		
		parent::clear();
	}
}

// END EE_Table class


/* End of file EE_Table.php */
/* Location: ./system/expressionengine/libraries/EE_Table.php */