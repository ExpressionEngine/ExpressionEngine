<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Use the table library as usual, but with added steps to setup the datatable:
 *
 * 1. Name your columns and set their sorting properties:
 *
 * $this->table->set_columns(array('id' => FALSE, 'name' => TRUE)); // @todo sorting datatype? (bit of a pain to let them pick, must work in js and mysql, maybe just ask them what sql will sort on)
 * 
 * @todo if you just need non-ajax sorting, should be done here.
 * 
 * 
 * 2. Define a function in your controller that will act as the datasource.
 * This function should be public and independent, it will be used for async requests.
 *
 * The function should return an array of table rows. Each row is an array similar
 * to the normal add_row() parameter, but with a key equal to the column name.
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
 * datasource.
 *
 * Your datasource will receive an options parameter that contains the current
 * page, filtering (form data), and sorting requirements in this format:
 * 
 * array(
 *     'page' => 2,
 *     'filters' => array('form_field' => 'value'),
 *     'sorting' => array('name' => 'asc/desc')
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
 */

/**
 * Notes:
 *
 * Protected column names: data
 *
 */

class EE_Table extends CI_Table {

	protected $EE;
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
			'filters'		=> array(),		// form_field => value
			'sorting'		=> array(),		// column_name => value
		);
		
		// override settings on non-ajax load
		// @todo @confirm @pk recursive, also tbl_ prefixed
		foreach (array_keys($settings) as $key)
		{
			if (isset($options[$key]))
			{
				$settings[$key] = $options[$key];
			}
		}
		
		// override settings through GET
		foreach (array_keys($_GET) as $key)
		{
			if (strncmp($key, 'tbl_', 4) == 0)
			{
				$settings[substr($key, 4)] = $_GET[$key];
				unset($_GET[$key]);
			}
		}
		
		// set options / override from $_GET
		$options = array_merge($options, $_GET);
		
		
		
		if ($this->input->get('tbl_page'))
		{
			$options['page'] = $_GET['tbl_page'];
		}
		
		
		$data = $this->EE->$func($settings, $options);
		// returns PHP array (shown in js syntax for brevity):
		/*
			[
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
				// loop through rows array_map cells?
			}
			
			$this->EE->javascript->send_ajax_request(array(
				'page' => 1,
				'rows' => $data
			));
		}
		
		// make sure we add a jq template
		$jq_template = TRUE;
		
		// remove the key information from the row data to make it usable
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
			return parent::generate();
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

			foreach($this->column_config as $column => $sort)
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
			'empty_cells'	=> $this->empty_cells
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