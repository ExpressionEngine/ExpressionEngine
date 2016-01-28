<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Table Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class EE_Table {

	protected $EE;
	protected $uniqid = '';
	protected $base_url = '';
	protected $no_results = '';
	protected $pagination_tmpl = '';
	protected $raw_data = '';

	protected $jq_template = FALSE;

	protected $no_ajax = FALSE;
	protected $sort = array();
	protected $page_offset = array();
	protected $column_config = array();

	var $rows         = array();
	var $heading      = array();
	var $footer       = array();
	var $auto_heading = TRUE;
	var $caption      = NULL;
	var $template     = NULL;
	var $newline      = "\n";
	var $empty_cells  = "";
	var	$function     = FALSE;

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		if (REQ == 'CP')
		{
			// @todo We have a code order issue with accessories.
			// That CP code needs to change in the near future,
			// but for now we work around it.
			$this->set_template(ee()->session->cache('table', 'cp_template'));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Set the base url
	 *
	 * If not set, the cp safe refresh url will be used, which may not
	 * be correct if the page is loaded with a POST request.
	 *
	 * @access	public
	 * @param	string	base url	do not include BASE.AMP
	 */
	function set_base_url($url)
	{
		$this->base_url = $url;
	}

	// --------------------------------------------------------------------

	/**
	 * Force non-ajax behvavior
	 *
	 * Workaround for the edit page modal until we figure out a neater
	 * way to get around the first load issues on that page.
	 *
	 * @third parties: do not touch this, it will definitely change
	 *
	 * @access	public
	 */
	function force_initial_load()
	{
		$this->no_ajax = TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Setup the datasource
	 *
	 * @access	public
	 * @param	string	data callback function
	 * @param	mixed	default data that will later be passed in the get array
	 */
	function datasource($func, $options = array(), $params = array())
	{
		$settings = array(
			'offset'		=> 0,
			'sort'			=> array(),		// column_name => value
			'columns'		=> $this->column_config
		);

		// override initial settings
		foreach (array_keys($settings) as $key)
		{
			if (isset($options[$key]))
			{
				$settings[$key] = $options[$key];
			}
		}

		// override initial settings from AJAX request

		// pagination reads from GET, so must be in GET
		$tbl_offset = ee()->input->get('tbl_offset');

		if (AJAX_REQUEST && $tbl_offset === FALSE)
		{
			$settings['offset'] = 0; // js removes blank keys, so we need to be explicit for page 1
		}
		elseif (is_numeric($tbl_offset))
		{
			$settings['offset'] = $tbl_offset;
		}

		// override sort settings from POST (EE does not allow for arrays in GET)
		if (ee()->input->post('tbl_sort'))
		{
			$settings['sort'] = array();

			$sort = ee()->input->post('tbl_sort');

			// sort: [ [field, dir], [dleif, rid] ]
			foreach ($sort as $s)
			{
				$settings['sort'][ $s[0] ] = $s[1];
			}
		}

		// datasource should return a PHP array (shown in js syntax for brevity):
		/*
		{
			no_results: 'something',
			total_rows: 44038,
			rows: [
				{key: value, key2: value2, key3: value3},
				{key: eulav, key2: eulav2, key3: eulav3},
			],
			pagination: [
				per_page: 5
			]
		*/
		$controller = isset(ee()->_mcp_reference) ? ee()->_mcp_reference : ee();
		$data = $controller->$func($settings, $params);

		$this->uniqid = uniqid('tbl_');

		if (isset($data['no_results']))
		{
			$this->no_results = $data['no_results'];
		}

		if ( ! $this->no_ajax && AJAX_REQUEST)
		{
			// do we need to apply a cell function?
			if ($this->function)
			{
				// @todo loop through rows array_map cells?
			}

			ee()->output->send_ajax_response(array(
				'rows'		 => $data['rows'],
				'pagination' => $this->_create_pagination($data, TRUE)
			));
		}

		// set our initial sort
		$this->sort = array();
		foreach ($settings['sort'] as $k => $v)
		{
			$this->sort[] = array($k, $v);
		}

		// set our initial offset
		$this->page_offset = $settings['offset'];

		$this->raw_data = $data['rows'];
		$this->set_data($data['rows']);

		$data['pagination'] = $this->_create_pagination($data);

		$data['table_html'] = $this->generate();

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
		$headers = array();
		$defaults = array(
			'sort' => TRUE,
			'html' => TRUE
		);

		foreach ($cols as $key => &$col)
		{
			// asking for trouble
			if ( ! is_array($col))
			{
				$col = array();
			}

			// if no header, pass key to lang()
			if (isset($col['header']))
			{
				$headers[] = $col['header'];
				unset($col['header']);
			}
			else
			{
				$headers[] = lang($key);
			}

			// set defaults
			$col = array_merge($defaults, $col);
		}

		$this->set_heading($headers);
		$this->column_config = $cols;
	}

	// --------------------------------------------------------------------

	/**
	 * Set data
	 *
	 * @access	public
	 * @param	mixed	rows of data in the new format
	 */
	public function set_data($table_data = NULL)
	{
		if ( ! $this->jq_template)
		{
			$this->jq_template = TRUE;
			ee()->cp->add_js_script('plugin', array('tmpl', 'ee_table'));
		}

		if (empty($table_data))
		{
			return;
		}

		// remove the key information from the row data to make it usable
		// by the CI generate function. Unfortunately that means we need to
		// reorder it to match our columns. Easy enough, simply overwrite
		// the column config. @todo check performance
		$ordered_columns = array_keys($this->column_config);

		foreach ($table_data as &$row)
		{
			$new_row = array();

			foreach ($ordered_columns as $key)
			{
				$new_row[] = (isset($row[$key])) ? $row[$key] : '';
			}

			$row = $this->_prep_args($new_row);
		}

		$this->rows = $table_data;
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
		if ( ! $this->jq_template)
		{
			return $this->_generate($table_data);
		}

		$this->_compile_template();

		$open_bak = $this->template['table_open'];

		// prep the jquery template
		$temp = $this->template['row_start'];

		foreach($this->column_config as $column => $config)
		{
			$html = FALSE;

			if (is_array($config))
			{
				$html = (isset($config['html'])) ? (bool) $config['html'] : FALSE;
			}

			// handle data of array('data' => 'content', 'attr' => 'value')
			$temp .= '{{if $.isPlainObject('.$column.')}}';
				$temp .= substr($this->template['cell_start'], 0, -1);
				$temp .= '{{each '.$column.'}}';
					$temp .= '{{if $index != "data"}} ${$index}="${$value}" {{/if}}';
				$temp .= '{{/each}}';
				$temp .= '>';
				$temp .= $html ? '{{html '.$column.'.data}}' : '${'.$column.'.data}';
			$temp .= '{{else}}';
				$temp .= $this->template['cell_start'];
				$temp .= $html ? '{{html '.$column.'}}' : '${'.$column.'}';
			$temp .= '{{/if}}';

			$temp .= $this->template['cell_end']."\n";
		}

		$temp .= $this->template['row_end'];
		$template = $temp;

		// add data to our headings for the sort mechanism
		$column_k = array_keys($this->column_config);

		foreach ($this->heading as $k => &$heading)
		{
			if ( ! is_array($heading))
			{
				$heading = array('data' => $heading);
			}

			if ( ! $this->column_config[$column_k[$k]]['sort'])
			{
				$heading['class'] = 'no-sort';
			}

			$heading['data-table_column'] = $column_k[$k];
		}


		if ( ! $this->base_url)
		{
			$this->base_url = ee()->cp->get_safe_refresh();
		}

		$jq_config = array(
			'base_url'		=> $this->base_url,
			'columns'		=> $this->column_config,
			'template'		=> $template,
			'empty_cells'	=> $this->empty_cells,
			'no_results'	=> $this->no_results,
			'pagination'	=> $this->pagination_tmpl,
			'uniqid'		=> $this->uniqid,
			'sort'			=> $this->sort,
			'rows'			=> $this->raw_data
		);

		$table_config_data = 'data-table_config="'.form_prep(json_encode($jq_config)).'"';
		$this->template['table_open'] = str_replace(
			'<table',
			'<table '.$table_config_data,
			$open_bak
		);

		$table = $this->_generate();

		$this->template['table_open'] = $open_bak;
		return $table;
	}

	// --------------------------------------------------------------------

	/**
	 * Generate the table
	 *
	 * @param	mixed
	 * @return	string
	 */
	protected function _generate($table_data = NULL)
	{
		// The table data can optionally be passed to this function
		// either as a database result object or an array
		if ( ! is_null($table_data))
		{
			if (is_object($table_data))
			{
				$this->_set_from_object($table_data);
			}
			elseif (is_array($table_data))
			{
				$set_heading = (count($this->heading) == 0 AND $this->auto_heading == FALSE) ? FALSE : TRUE;
				$this->_set_from_array($table_data, $set_heading);
			}
		}

		// Is there anything to display?  No?  Smite them!
		if (count($this->heading) == 0 AND count($this->rows) == 0)
		{
			return 'Undefined table data';
		}

		// Compile and validate the template date
		$this->_compile_template();

		// set a custom cell manipulation function to a locally scoped variable so its callable
		$function = $this->function;

		// Build the table!

		$out = $this->template['table_open'];
		$out .= $this->newline;

		// Add any caption here
		if ($this->caption)
		{
			$out .= $this->newline;
			$out .= '<caption>' . $this->caption . '</caption>';
			$out .= $this->newline;
		}

		// Is there a table heading to display?
		if (count($this->heading) > 0)
		{
			$out .= $this->template['thead_open'];
			$out .= $this->newline;
			$out .= $this->template['heading_row_start'];
			$out .= $this->newline;

			foreach($this->heading as $heading)
			{
				$temp = $this->template['heading_cell_start'];

				foreach ($heading as $key => $val)
				{
					if ($key != 'data')
					{
						$temp = str_replace('<th', "<th $key='$val'", $temp);
					}
				}

				$out .= $temp;
				$out .= isset($heading['data']) ? $heading['data'] : '';
				$out .= $this->template['heading_cell_end'];
			}

			$out .= $this->template['heading_row_end'];
			$out .= $this->newline;
			$out .= $this->template['thead_close'];
			$out .= $this->newline;
		}

		// Build the table rows
		if (count($this->rows) > 0)
		{
			$out .= $this->template['tbody_open'];
			$out .= $this->newline;

			$i = 1;
			foreach($this->rows as $row)
			{
				if ( ! is_array($row))
				{
					break;
				}

				// We use modulus to alternate the row colors
				$name = (fmod($i++, 2)) ? '' : 'alt_';

				$out .= $this->template['row_'.$name.'start'];
				$out .= $this->newline;

				foreach($row as $cell)
				{
					$temp = $this->template['cell_'.$name.'start'];

					foreach ($cell as $key => $val)
					{
						if ($key != 'data')
						{
							$temp = str_replace('<td', "<td $key='$val'", $temp);
						}
					}

					$cell = isset($cell['data']) ? $cell['data'] : '';
					$out .= $temp;

					if ($cell === "" OR $cell === NULL)
					{
						$out .= $this->empty_cells;
					}
					else
					{
						if ($function !== FALSE && is_callable($function))
						{
							$out .= $function($cell);
						}
						else
						{
							$out .= $cell;
						}
					}

					$out .= $this->template['cell_'.$name.'end'];
				}

				$out .= $this->template['row_'.$name.'end'];
				$out .= $this->newline;
			}

			$out .= $this->template['tbody_close'];
			$out .= $this->newline;
		}

		// Is there a table heading to display?
		if (count($this->footer) > 0)
		{
			$out .= $this->template['tfoot_open'];
			$out .= $this->newline;
			$out .= $this->template['heading_row_start'];
			$out .= $this->newline;

			foreach($this->footer as $footer)
			{
				$temp = $this->template['heading_cell_start'];

				foreach ($footer as $key => $val)
				{
					if ($key != 'data')
					{
						$temp = str_replace('<th', "<th $key='$val'", $temp);
					}
				}

				$out .= $temp;
				$out .= isset($footer['data']) ? $footer['data'] : '';
				$out .= $this->template['heading_cell_end'];
			}

			$out .= $this->template['heading_row_end'];
			$out .= $this->newline;
			$out .= $this->template['tfoot_close'];
			$out .= $this->newline;
		}


		$out .= $this->template['table_close'];

		// Clear table class properties before generating the table
		$this->clear();

		return $out;
	}

	// --------------------------------------------------------------------

	/**
	 * Clears the table arrays.  Useful if multiple tables are being generated
	 *
	 * @return	void
	 */
	public function clear()
	{
		$this->uniqid = '';
		$this->base_url = '';
		$this->no_result = '';
		$this->pagination_tmpl = '';

		$this->sort = array();
		$this->column_config = array();

		$this->jq_template = FALSE;

		$this->rows				= array();
		$this->heading			= array();
		$this->auto_heading		= TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Set the template
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function set_template($template)
	{
		if ( ! is_array($template))
		{
			return FALSE;
		}

		$this->template = $template;
	}

	// --------------------------------------------------------------------

	/**
	 * Set the table heading
	 *
	 * Can be passed as an array or discreet params
	 *
	 * @access	public
	 * @param	mixed
	 * @return	void
	 */
	function set_heading()
	{
		$args = func_get_args();
		$this->heading = $this->_prep_args($args);
	}

	// --------------------------------------------------------------------

	/**
	 * Set the table footer
	 *
	 * Can be passed as an array or discreet params
	 *
	 * @access	public
	 * @param	mixed
	 * @return	void
	 */
	function set_footer()
	{
		$args = func_get_args();
		$this->footer = $this->_prep_args($args);
	}

	// --------------------------------------------------------------------

	/**
	 * Set columns.  Takes a one-dimensional array as input and creates
	 * a multi-dimensional array with a depth equal to the number of
	 * columns.  This allows a single array with many elements to  be
	 * displayed in a table that has a fixed column count.
	 *
	 * @access	public
	 * @param	array
	 * @param	int
	 * @return	void
	 */
	function make_columns($array = array(), $col_limit = 0)
	{
		if ( ! is_array($array) OR count($array) == 0)
		{
			return FALSE;
		}

		// Turn off the auto-heading feature since it's doubtful we
		// will want headings from a one-dimensional array
		$this->auto_heading = FALSE;

		if ($col_limit == 0)
		{
			return $array;
		}

		$new = array();
		while(count($array) > 0)
		{
			$temp = array_splice($array, 0, $col_limit);

			if (count($temp) < $col_limit)
			{
				for ($i = count($temp); $i < $col_limit; $i++)
				{
					$temp[] = '&nbsp;';
				}
			}

			$new[] = $temp;
		}

		return $new;
	}

	// --------------------------------------------------------------------

	/**
	 * Set "empty" cells
	 *
	 * Can be passed as an array or discreet params
	 *
	 * @access	public
	 * @param	mixed
	 * @return	void
	 */
	function set_empty($value)
	{
		$this->empty_cells = $value;
	}

	// --------------------------------------------------------------------

	/**
	 * Add a table row
	 *
	 * Can be passed as an array or discreet params
	 *
	 * @access	public
	 * @param	mixed
	 * @return	void
	 */
	function add_row()
	{
		$args = func_get_args();
		$this->rows[] = $this->_prep_args($args);
	}

	// --------------------------------------------------------------------

	/**
	 * Prep Args
	 *
	 * Ensures a standard associative array format for all cell data
	 *
	 * @access	public
	 * @param	type
	 * @return	type
	 */
	function _prep_args($args)
	{
		// If there is no $args[0], skip this and treat as an associative array
		// This can happen if there is only a single key, for example this is passed to table->generate
		// array(array('foo'=>'bar'))
		if (isset($args[0]) AND (count($args) == 1 && is_array($args[0])))
		{
			// args sent as indexed array
			if ( ! isset($args[0]['data']))
			{
				foreach ($args[0] as $key => $val)
				{
					if (is_array($val) && isset($val['data']))
					{
						$args[$key] = $val;
					}
					else
					{
						$args[$key] = array('data' => $val);
					}
				}
			}
		}
		else
		{
			foreach ($args as $key => $val)
			{
				if ( ! is_array($val))
				{
					$args[$key] = array('data' => $val);
				}
			}
		}

		return $args;
	}

	// --------------------------------------------------------------------

	/**
	 * Add a table caption
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function set_caption($caption)
	{
		$this->caption = $caption;
	}

	// --------------------------------------------------------------------

	/**
	 * Set table data from a database result object
	 *
	 * @access	public
	 * @param	object
	 * @return	void
	 */
	function _set_from_object($query)
	{
		if ( ! is_object($query))
		{
			return FALSE;
		}

		// First generate the headings from the table column names
		if (count($this->heading) == 0)
		{
			if ( ! method_exists($query, 'list_fields'))
			{
				return FALSE;
			}

			$this->heading = $this->_prep_args($query->list_fields());
		}

		// Next blast through the result array and build out the rows

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$this->rows[] = $this->_prep_args($row);
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Set table data from an array
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function _set_from_array($data, $set_heading = TRUE)
	{
		if ( ! is_array($data) OR count($data) == 0)
		{
			return FALSE;
		}

		$i = 0;
		foreach ($data as $row)
		{
			// If a heading hasn't already been set we'll use the first row of the array as the heading
			if ($i == 0 AND count($data) > 1 AND count($this->heading) == 0 AND $set_heading == TRUE)
			{
				$this->heading = $this->_prep_args($row);
			}
			else
			{
				$this->rows[] = $this->_prep_args($row);
			}

			$i++;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Compile Template
	 *
	 * @access	private
	 * @return	void
	 */
	function _compile_template()
	{
		if ($this->template == NULL)
		{
			$this->template = $this->_default_template();
			return;
		}

		$this->temp = $this->_default_template();
		$segments = array(
			'table_open',
			'thead_open', 'thead_close',
			'heading_row_start', 'heading_row_end',
			'heading_cell_start', 'heading_cell_end',
			'tbody_open', 'tbody_close',
			'row_start', 'row_end',
			'cell_start', 'cell_end',
			'row_alt_start', 'row_alt_end',
			'cell_alt_start', 'cell_alt_end',
			'tfoot_open', 'tfoot_close',
			'table_close'
		);
		foreach ($segments as $val)
		{
			if ( ! isset($this->template[$val]))
			{
				$this->template[$val] = $this->temp[$val];
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Default Template
	 *
	 * @access	private
	 * @return	void
	 */
	function _default_template()
	{
		return  array (
			'table_open'         => '<table border ="0" cellpadding ="4" cellspacing ="0">',

			'thead_open'         => '<thead>',
			'thead_close'        => '</thead>',

			'heading_row_start'  => '<tr>',
			'heading_row_end'    => '</tr>',
			'heading_cell_start' => '<th>',
			'heading_cell_end'   => '</th>',

			'tbody_open'         => '<tbody>',
			'tbody_close'        => '</tbody>',

			'row_start'          => '<tr>',
			'row_end'            => '</tr>',
			'cell_start'         => '<td>',
			'cell_end'           => '</td>',

			'row_alt_start'      => '<tr>',
			'row_alt_end'        => '</tr>',
			'cell_alt_start'     => '<td>',
			'cell_alt_end'       => '</td>',

			'tfoot_open'         => '<tfoot>',
			'tfoot_close'        => '</tfoot>',

			'table_close'        => '</table>'
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Setup table pagination
	 *
	 * @access	protected
	 * @param	string	pagination html
	 */
	protected function _create_pagination($data, $ajax_request = FALSE)
	{
		if ( ! isset($data['pagination']))
		{
			return '';
		}

		if ( ! isset($data['pagination']['total_rows']))
		{
			return '';
		}

		// sensible CP defaults
		$config = array(
			'base_url'				=> '',
			'per_page'				=> 50,
			'cur_page'				=> $this->page_offset,
			'num_links'				=> 2,

			'full_tag_open'			=> '<div class="paginate"><ul>', // @todo having an id here is nonsense, you can have more than one!
			'full_tag_close'		=> '</ul></div>',
		);

		$config = array_merge($config, $data['pagination']);

		// add the uniqid as a class so we can find it from
		// the table. Note: You can have multiple instances
		// of the pagination html on the page.
		if (strpos($config['full_tag_open'], 'class')) // will never be 0
		{
			$config['full_tag_open'] = preg_replace(
				'#class\s*=\s*(\042|\047)#i',
				'$0'.$this->uniqid.' ',
				$config['full_tag_open']
			);
		}
		else
		{
			$config['full_tag_open'] = preg_replace(
				'#(<\w+)#i',
				'$1 class="'.$this->uniqid.'"',
				$config['full_tag_open']
			);
		}

		ee()->load->library('pagination');
		ee()->pagination->initialize($config);

		if ($ajax_request)
		{
			return ee()->pagination->create_link_array();
		}

		$p = ee()->pagination;

		$temp = $p->full_tag_open;

		$temp .= '<li><a href="${first_page[0].pagination_url}">{{html first_page[0].text}}</a></li>';
		$temp .= '<li><a href="${previous_page[0].pagination_url}">{{html previous_page[0].text}}</a></li>';

		$temp .= '{{each(i, c_page) page}}';
			$temp .= '{{if c_page.current_page}}';
			$temp .= '<li><a class="act" href="${c_page.pagination_url}">${c_page.pagination_page_number}</a></li>';
			$temp .= '{{else}}';
			$temp .= '<li><a href="${c_page.pagination_url}">${c_page.pagination_page_number}</a></li>';
			$temp .= '{{/if}}';
		$temp .= '{{/each}}';

		$temp .= '<li><a href="${next_page[0].pagination_url}">{{html next_page[0].text}}</a></li>';
		$temp .= '<li><a href="${last_page[0].pagination_url}">{{html last_page[0].text}}</a></li>';

		$temp .= $p->full_tag_close;

		$this->pagination_tmpl = $temp;
		unset($temp);

		$links = ee()->pagination->create_link_array();

		// "Fixing" the URLs
		foreach ($links as &$section)
		{
			foreach ($section as &$link)
			{
				if (empty($link)) continue;

				$url = clone $this->base_url;

				$offset = str_replace('/', '', $link['pagination_url']);
				if ( ! empty($offset))
				{
					$url->setQueryStringVariable('tbl_offset', $offset);
				}

				$link['pagination_url'] = $url->compile();
			}
		}

		return $links;
	}
}

// END EE_Table class

// EOF
