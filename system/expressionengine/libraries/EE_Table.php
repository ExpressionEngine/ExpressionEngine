<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */
class EE_Table extends CI_Table {

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

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		parent::__construct();

		$this->EE =& get_instance();

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
		$controller = isset(ee()->_mcp_reference) ? ee()->_mcp_reference : $this->EE;
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

		$data['pagination_html'] = $this->_create_pagination($data);

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
			return parent::generate($table_data);
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

		$table = parent::generate();

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
		$this->uniqid = '';
		$this->base_url = '';
		$this->no_result = '';
		$this->pagination_tmpl = '';

		$this->sort = array();
		$this->column_config = array();

		$this->jq_template = FALSE;

		parent::clear();
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
			'base_url'				=> $this->base_url,
			'per_page'				=> 50,
			'cur_page'				=> $this->page_offset,

			'page_query_string'		=> TRUE,
			'query_string_segment'	=> 'tbl_offset',

			'full_tag_open'			=> '<p id="paginationLinks">', // @todo having an id here is nonsense, you can have more than one!
			'full_tag_close'		=> '</p>',

			'prev_link'				=> '<img src="'.ee()->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="&lt;" />',
			'next_link'				=> '<img src="'.ee()->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt="&gt;" />',
			'first_link'			=> '<img src="'.ee()->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="&lt; &lt;" />',
			'last_link'				=> '<img src="'.ee()->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="&gt; &gt;" />'
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

		$temp .= '{{if first_page && first_page[0] && first_page[0].text}}';
		$temp .= $p->first_tag_open.'<a '.$p->anchor_class.'href="${first_page[0].pagination_url}">{{html first_page[0].text}}</a>'.$p->first_tag_close;
		$temp .= '{{/if}}';

		$temp .= '{{if previous_page && previous_page[0] && previous_page[0].text}}';
		$temp .= $p->prev_tag_open.'<a '.$p->anchor_class.'href="${previous_page[0].pagination_url}">{{html previous_page[0].text}}</a>'.$p->prev_tag_close;
		$temp .= '{{/if}}';


		$temp .= '{{each(i, c_page) page}}';
			$temp .= '{{if c_page.current_page}}';
			$temp .= $p->cur_tag_open.'${c_page.pagination_page_number}'.$p->cur_tag_close;
			$temp .= '{{else}}';
			$temp .= $p->num_tag_open.'<a '.$p->anchor_class.'href="${c_page.pagination_url}">${c_page.pagination_page_number}</a>'.$p->num_tag_close;
			$temp .= '{{/if}}';
		$temp .= '{{/each}}';


		$temp .= '{{if next_page && next_page[0] && next_page[0].text}}';
		$temp .= $p->next_tag_open.'<a '.$p->anchor_class.'href="${next_page[0].pagination_url}">{{html next_page[0].text}}</a>'.$p->next_tag_close;
		$temp .= '{{/if}}';

		$temp .= '{{if last_page && last_page[0] && last_page[0].text}}';
		$temp .= $p->last_tag_open.'<a '.$p->anchor_class.'href="${last_page[0].pagination_url}">{{html last_page[0].text}}</a>'.$p->last_tag_close;
		$temp .= '{{/if}}';

		$temp .= $p->full_tag_close;

		$this->pagination_tmpl = $temp;
		unset($temp);

		$initial = ee()->pagination->create_links();

		if ($initial == '')
		{
			$initial = str_replace(
				$this->uniqid,
				$this->uniqid.' js_hide',
				$p->full_tag_open
			);
			$initial .= $p->full_tag_close;
		}

		return $initial;
	}
}

// END EE_Table class


/* End of file EE_Table.php */
/* Location: ./system/expressionengine/libraries/EE_Table.php */