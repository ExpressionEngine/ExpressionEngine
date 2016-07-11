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

// --------------------------------------------------------------------

/**
 * ExpressionEngine File Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class File {

	var $reserved_cat_segment	= '';
	var $use_category_names		= FALSE;
	var $categories				= array();
	var $catfields				= array();
	var $valid_thumbs			= array();
	var $return_data			= '';

	/**
	  * Constructor
	  */
	public function __construct()
	{
		if (ee()->config->item("use_category_name") == 'y' && ee()->config->item("reserved_category_word") != '')
		{
			$this->use_category_names	= ee()->config->item("use_category_name");
			$this->reserved_cat_segment	= ee()->config->item("reserved_category_word");
		}

	}

	// ------------------------------------------------------------------------

	/**
	  *  Files tag
	  */
	public function entries()
	{
		$this->_fetch_disable_param();

		ee()->load->library('pagination');
		$pagination = ee()->pagination->create();
		ee()->TMPL->tagdata = $pagination->prepare(ee()->TMPL->tagdata);

		if ($this->enable['pagination'] == FALSE)
		{
			$pagination->paginate = FALSE;
		}

		$results = $this->_get_file_data($pagination);

		if (empty($results))
		{
			return ee()->TMPL->no_results();
		}

		$this->query = $results;

		if ($this->query->num_rows() == 0)
		{
			return ee()->TMPL->no_results();
		}

		$this->fetch_categories();
		$this->fetch_valid_thumbs();
		$this->parse_file_entries();

		if ($this->enable['pagination'] && $pagination->paginate == TRUE)
		{
			$this->return_data = $pagination->render($this->return_data);
		}

		return $this->return_data;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Build SQL Query
	  */
	private function _get_file_data($pagination = '')
	{
		$file_id			= '';
		$category_id		= FALSE;
		$category_group		= FALSE;
		$category_params	= array('category' => 'category_id', 'category_group' => 'category_group');
		$dynamic			= (ee()->TMPL->fetch_param('dynamic') !== 'no') ? TRUE : FALSE;

		// Parse the URL query string
		$query_string = (ee()->uri->page_query_string != '') ? ee()->uri->page_query_string : ee()->uri->query_string;
		$uristr = ee()->uri->uri_string;
		if ($dynamic && ! empty($query_string))
		{
			// If the query string is a number, treat it as a file ID
			if (is_numeric($query_string))
			{
				$file_id = $query_string;
			}
			else if ($this->enable['categories'])
			{
				ee()->load->helper('segment');
				$category_id = parse_category($query_string);
			}
		}

		// Check the file_id parameter and override the one fetched from the
		// query string
		if (ee()->TMPL->fetch_param('file_id'))
		{
			$file_id = ee()->TMPL->fetch_param('file_id');
		}

		// Chec	k for category parameters
		foreach	($category_params as $param => $variable)
		{
			if ($this->enable['categories']
				&& ($temp = ee()->TMPL->fetch_param($param)))
			{
				$$variable = $temp;
			}
		}

		// Start the cache so we can use for pagination
		ee()->db->start_cache();

		// Join the categories table if we're dealing with categories at all
		if ($category_id OR $category_group)
		{
			ee()->db->distinct();

			// We use 'LEFT' JOIN when there is a 'not' so that we get entries
			// that are not assigned to a category.
			if ((substr($category_group, 0, 3) == 'not' OR substr($category_id, 0, 3) == 'not') && ee()->TMPL->fetch_param('uncategorized_entries') !== 'n')
			{
				ee()->db->join('file_categories', 'exp_files.file_id = exp_file_categories.file_id', 'LEFT');
				ee()->db->join('categories', 'exp_file_categories.cat_id = exp_categories.cat_id', 'LEFT');
			}
			else
			{
				ee()->db->join('file_categories', 'exp_files.file_id = exp_file_categories.file_id', 'INNER');
				ee()->db->join('categories', 'exp_file_categories.cat_id = exp_categories.cat_id', 'INNER');
			}
		}

		// Start pulling File IDs to both paginate on then pull data
		ee()->db->select('exp_files.file_id');
		ee()->db->from('files');

		// Specify file ID(s) if supplied
		if ($file_id != '')
		{
			ee()->functions->ar_andor_string($file_id, 'exp_files.file_id');
		}

		// Specify directory ID(s) if supplied
		if (($directory_ids = ee()->TMPL->fetch_param('directory_id')) != FALSE)
		{
			ee()->functions->ar_andor_string($directory_ids, 'upload_location_id');
		}
		// If no directory_id is set, restrict files to current site
		else
		{
			ee()->db->where_in('exp_files.site_id', ee()->TMPL->site_ids);
		}

		// Specify category and category group ID(s) if supplied
		foreach ($category_params as $param => $variable)
		{
			if ($$variable)
			{
				$cat_field_name = ($param == 'category') ? 'exp_categories.cat_id' : 'exp_categories.group_id';

				$include_uncategorized = (substr($$variable, 0, 3) == 'not'
					&& ee()->TMPL->fetch_param('uncategorized_entries') !== 'n') ? TRUE : FALSE;

				ee()->functions->ar_andor_string($$variable, $cat_field_name, '', $include_uncategorized);
			}
		}

		// Set the limit
		$limit = (int) ee()->TMPL->fetch_param('limit', 0);
		$offset = (int) ee()->TMPL->fetch_param('offset', 0);
		if ($limit > 0 && $this->enable['pagination'] && $pagination->paginate == TRUE)
		{
			$pagination->build(ee()->db->count_all_results(), $limit);
			ee()->db->limit($pagination->per_page, $pagination->offset);
		}
		else if ($limit > 0 && $offset >= 0)
		{
			ee()->db->limit($limit, $offset);
		}
		else
		{
			ee()->db->limit(100);
		}

		// Set order and sort
		$allowed_orders	= array('date', 'upload_date', 'random');
		$order_by		= strtolower(ee()->TMPL->fetch_param('orderby', 'upload_date'));
		$order_by		= ($order_by == 'date' OR ! in_array($order_by, $allowed_orders)) ? 'upload_date' : $order_by;
		$random			= ($order_by == 'random') ? TRUE : FALSE;
		$sort			= strtolower(ee()->TMPL->fetch_param('sort', 'desc'));
		$sort			= ($random) ? 'random' : $sort;

		if ( ! $random)
		{
			ee()->db->select($order_by);
		}

		ee()->db->order_by($order_by, $sort);

		ee()->db->stop_cache();
		// Run the query and pass it to the final query
		$query = ee()->db->get();
		ee()->db->flush_cache();

		if ($query->num_rows() == 0)
		{
			return array();
		}

		foreach ($query->result() as $row)
		{
			$file_ids[] = $row->file_id;
		}

		//  Build the full SQL query
		ee()->db->select('*')
			->join('upload_prefs', 'upload_prefs.id = files.upload_location_id', 'LEFT')
			->where_in('file_id', $file_ids)
			->order_by($order_by, $sort);
		return ee()->db->get('files');
	}

	// ------------------------------------------------------------------------

	/**
	  *  Fetch categories
	  */
	function fetch_categories()
	{
		ee()->db->select('field_id, field_name')
			->from('category_fields')
			->where_in('site_id', ee()->TMPL->site_ids);

		$query = ee()->db->get();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$this->catfields[] = array('field_name' => $row['field_name'], 'field_id' => $row['field_id']);
			}
		}

		$categories = array();

		foreach ($this->query->result_array() as $row)
		{
			$categories[] = $row['file_id'];
		}


		$sql = ee()->db->select('c.cat_name, c.cat_url_title, c.cat_id, c.cat_image, c.cat_description,
									c.parent_id, p.cat_id, p.file_id, c.group_id, cg.field_html_formatting, fd.*')
			->from('exp_categories AS c, exp_file_categories AS p')
			->join('category_field_data AS fd', 'fd.cat_id = c.cat_id', 'LEFT')
			->join('category_groups AS cg', 'cg.group_id = c.group_id', 'LEFT')
			->where('c.cat_id = p.cat_id')
			->where_in('file_id', $categories)
			->order_by('c.group_id, c.parent_id, c.cat_order');


		$query = ee()->db->get();

		if ($query->num_rows() == 0)
		{
			return;
		}

		foreach ($categories as $val)
		{
			$this->temp_array = array();
			$this->cat_array  = array();
			$parents = array();

			foreach ($query->result_array() as $row)
			{
				if ($val == $row['file_id'])
				{
					$this->temp_array[$row['cat_id']] = array('category_id' => $row['cat_id'], 'parent_id' => $row['parent_id'], 'category_name' => $row['cat_name'], 'category_image' => $row['cat_image'], 'category_description' => $row['cat_description'], 'category_group_id' => $row['group_id'], 'category_url_title' => $row['cat_url_title']);


					// Add in the path variable
					$this->temp_array[$row['cat_id']]['path'] = ($this->use_category_names == TRUE)
							? array($this->reserved_cat_segment.'/'.$row['cat_url_title'], array('path_variable' => TRUE)) :
								array('/C'.$row['cat_id'], array('path_variable' => TRUE));

					foreach ($row as $k => $v)
					{
						if (strpos($k, 'field') !== FALSE)
						{
							$this->temp_array[$row['cat_id']][$k] = $v;
						}
					}

					if ($row['parent_id'] > 0 && ! isset($this->temp_array[$row['parent_id']])) $parents[$row['parent_id']] = '';
					unset($parents[$row['cat_id']]);
				}
			}

			if (count($this->temp_array) == 0)
			{
				$temp = FALSE;
			}
			else
			{
				foreach($this->temp_array as $k => $v)
				{
					if (isset($parents[$v['parent_id']])) $v['parent_id'] = 0;

					if (0 == $v['parent_id'])
					{
						$this->cat_array[] = $v;
						$this->process_subcategories($k);
					}
				}
			}

			$this->categories[$val] = $this->cat_array;
		}

		unset($this->temp_array);
		unset($this->cat_array);
	}

	// ------------------------------------------------------------------------

	/**
	  *  Process Subcategories
	  */
	function process_subcategories($parent_id)
	{
		foreach($this->temp_array as $key => $val)
		{
			if ($parent_id == $val['parent_id'])
			{
				$this->cat_array[] = $val;
				$this->process_subcategories($key);
			}
		}
	}

	// ------------------------------------------------------------------------

	/**
	  *  Fetch Valid Thumbs
	  */
	function fetch_valid_thumbs()
	{
		ee()->db->select('upload_location_id, short_name');
		ee()->db->from('upload_prefs');

		ee()->db->join('file_dimensions', 'upload_prefs.id = file_dimensions.upload_location_id');

		ee()->db->where_in('upload_prefs.site_id', ee()->TMPL->site_ids);

		if (($directory_ids = ee()->TMPL->fetch_param('directory_id')) != FALSE)
		{
			ee()->functions->ar_andor_string($directory_ids, 'upload_location_id');
		}

		$sql = ee()->db->get();

		if ($sql->num_rows() == 0)
		{
			return;
		}

		foreach ($sql->result_array() as $row)
		{
			$this->valid_thumbs[] = array('dir' => $row['upload_location_id'], 'name' => $row['short_name']);
		}

	}

	// ------------------------------------------------------------------------

	/**
	  *  Parse file entries
	  */
	function parse_file_entries()
	{
		ee()->load->library('typography');
		ee()->typography->initialize(array(
			'convert_curly' => FALSE
		));

		// Fetch the "category chunk"
		// We'll grab the category data now to avoid processing cycles in the foreach loop below

		$cat_chunk = array();
		if (strpos(ee()->TMPL->tagdata, LD.'/categories'.RD) !== FALSE)
		{
			if (preg_match_all("/".LD."categories(.*?)".RD."(.*?)".LD.'\/'.'categories'.RD."/s", ee()->TMPL->tagdata, $matches))
			{
				for ($j = 0; $j < count($matches[0]); $j++)
				{
					$cat_chunk[] = array($matches[2][$j], ee()->functions->assign_parameters($matches[1][$j]), $matches[0][$j]);
				}
			}
		}

		ee()->load->model('file_upload_preferences_model');
		$upload_prefs = ee()->file_upload_preferences_model->get_file_upload_preferences(1, NULL, TRUE);

		$parse_data = array();
		foreach ($this->query->result_array() as $count => $row)
		{
			$row_prefs = $upload_prefs[$row['upload_location_id']];

			//  More Variables, Mostly for Conditionals
			$row['absolute_count']  = (int) ee()->TMPL->fetch_param('limit') + $count + 1;
			$row['logged_in']       = (ee()->session->userdata('member_id') == 0) ? FALSE : TRUE;
			$row['logged_out']      = (ee()->session->userdata('member_id') != 0) ? FALSE : TRUE;
			$row['directory_id']    = $row['id'];
			$row['directory_title'] = $row['name'];
			$row['entry_id']        = $row['file_id'];
			$row['extension']       = substr(strrchr($row['file_name'], '.'), 1);
			$row['path']            = $row_prefs['url'];
			$row['url']             = rtrim($row_prefs['url'], '/').'/'.$row['file_name'];
			$row['viewable_image']  = $this->is_viewable_image($row['file_name']);

			// Add in the path variable
			$row['id_path'] = array('/'.$row['file_id'], array('path_variable' => TRUE));

			// typography on title?
			$row['title']			= ee()->typography->format_characters($row['title']);

			// typography on caption
			ee()->typography->parse_type($row['description'],
				array(
					'text_format'	=> 'xhtml',
					'html_format'	=> 'safe',
					'auto_links'	=> 'y',
					'allow_img_url'	=> 'y'
				)
			);

			// Backwards compatible support for some old variables
			$row['caption']    = $row['description'];
			$row['entry_date'] = $row['upload_date'];
			$row['edit_date']  = $row['modified_date'];
			$row['filename']   = $row['file_name'];
			$row['file_url']   = $row['url'];

			// Get File Size/H/W data
			$size_data = $this->get_file_sizes(reduce_double_slashes($row_prefs['server_path'].'/'.$row['filename']));

			foreach($size_data as $k => $v)
			{
				$row[$k] = $v;
			}

			$row['file_size'] = $row['size'];
			$row['file_size:human'] = (string) ee('Format')->make('Number', $row['size'])->bytes();
			$row['file_size:human_long'] = (string) ee('Format')->make('Number', $row['size'])->bytes(FALSE);

			foreach ($this->valid_thumbs as $data)
			{
				if ($row['viewable_image'] && $row['id'] == $data['dir'])
				{
					$size_data = array();

					$row['url:'.$data['name']] = rtrim($row_prefs['url'], '/').'/_'.$data['name'].'/'.$row['file_name'];
					// backwards compat variable
					$row[$data['name'].'_file_url'] = $row['url:'.$data['name']];

					$size_data = $this->get_file_sizes(reduce_double_slashes($row_prefs['server_path'].'/_'.$data['name'].'/'.$row['file_name']));

					$row['height:'.$data['name']] = $size_data['height'];
					$row['width:'.$data['name']] = $size_data['width'];
					$row['file_size:'.$data['name']] = (int) $size_data['size'];
					$row['file_size:'.$data['name'].':human'] = (string) ee('Format')->make('Number', (int) $size_data['size'])->bytes();
					$row['file_size:'.$data['name'].':human_long'] = (string) ee('Format')->make('Number', (int) $size_data['size'])->bytes(FALSE);

					// backwards compat variables
					foreach($size_data as $k => $v)
					{
						$row[$data['name'].'_'.$k] = $v;
					}
				}
				// if the file doesn't exist this key is null, and fails isset(), so use array_key_exists
				elseif ( ! array_key_exists($data['name'].'_height', $row))
				{
					$row['url:'.$data['name']]                     = '';
					$row['height:'.$data['name']]                  = '';
					$row['width:'.$data['name']]                   = '';
					$row['file_size:'.$data['name']]               = '';
					$row['file_size:'.$data['name'].':human']      = '';
					$row['file_size:'.$data['name'].':human_long'] = '';

					// backwards compat
					$row[$data['name'].'_height']   = '';
					$row[$data['name'].'_width']    = '';
					$row[$data['name'].'_size']     = '';
					$row[$data['name'].'_file_url'] = '';
				}
			}

			// Category variables
			$row['categories'] = ($this->enable['categories'] && isset($this->categories[$row['file_id']])) ? $this->categories[$row['file_id']] : array();

			$parse_data[] = $row;
		}

		$this->return_data = ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $parse_data);
	}

	// -------------------------------------------------------------------------

	function is_viewable_image($file)
	{
		$viewable_image = array('bmp','gif','jpeg','jpg','jpe','png');

		$ext = strtolower(substr(strrchr($file, '.'), 1));


		$viewable = (in_array($ext, $viewable_image)) ? TRUE : FALSE;
		return $viewable;
	}

	// --------------------------------------------------------------------

	/**
	 * Gets File Metadata- may move to db
	 *
	 * @param	string	$file_path	The full path to the file to check
	 * @return	array
	 */
	function get_file_sizes($file_path)
	{
		ee()->load->helper('file');
		$filedata = array('height' => '', 'width' => '');

		$filedata['is_image'] = $this->is_viewable_image($file_path);

		if ($filedata['is_image'] && function_exists('getimagesize'))
		{
			$D = @getimagesize($file_path);

			$filedata['height']	= $D['1'];
			$filedata['width']	= $D['0'];
		}

		$s = get_file_info($file_path, array('size'));

		$filedata['size'] = ($s) ? $s['size'] : FALSE;

		return $filedata;
	}


	// ------------------------------------------------------------------------

	/**
	  * Fetch Disable Parameter
	  */
	private function _fetch_disable_param()
	{
		$this->enable = array(
			'categories'		=> TRUE,
			'category_fields'	=> TRUE,
			'pagination'		=> TRUE
		);

		if ($disable = ee()->TMPL->fetch_param('disable'))
		{
			foreach (explode("|", $disable) as $val)
			{
				if (isset($this->enable[$val]))
				{
					$this->enable[$val] = FALSE;
				}
			}
		}
	}
}
// END CLASS

// EOF
