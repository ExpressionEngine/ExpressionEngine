<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine API Class
 *
 * Parent class to unify code for accessing and modifying data in EE
 * The parent class handles tasks common to many child classes including returning output
 * 
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Api {
	
	public $errors	= array();  // holds any and all errors on failure
	
	protected $EE;
	
	private $apis	= array(	// apis available to initialize when loading the parent Api class
							'channel_structure', 'channel_entries', 'channel_fields',
							'channel_categories', 'channel_statuses', 'channel_uploads',
							'template_structure',
							'members'
						);
	
	/**
	 * Constructor
	 *
	 */
	function __construct()
	{
		// Set the EE super object to a class variable
		$this->EE =& get_instance();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Instantiate another API
	 *
	 * Loads a child API after an API has been instantiated, since libraries
	 * in CI are singletons.
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function instantiate($which)
	{
		if ( ! is_array($which))
		{
			$which = array($which);
		}

		foreach ($which as $api)
		{
			if (in_array($api, $this->apis))
			{
				$api_driver = 'api_'.$api;
				$this->EE->load->library('api/'.$api_driver);
			}
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Initialize
	 *
	 * Reset the errors array and any config options
	 *
	 * @access	protected
	 * @param	array
	 * @return	void
	 */
	protected function initialize($params = array())
	{
		$this->errors = array();
		
		foreach ($params as $param => $val)
		{
			$this->{$param} = $val;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Error Count
	 *
	 * Just a way to keep syntax simple and not have to access
	 * $this->errors directly in the child libraries
	 *
	 * @return	int
	 */
	public function error_count()
	{
		return count($this->errors);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Set Error
	 *
	 * Adds an error to the API error array
	 *
	 * @php4	Protected Class
	 * @access	protected
	 * @param	string
	 * @return	void
	 */
	function _set_error($error_msg)
	{
		$this->errors[] = ($this->EE->lang->line($error_msg) != '') ? $this->EE->lang->line($error_msg) : str_replace('_', ' ', ucfirst($error_msg));
	}

	// --------------------------------------------------------------------
	
	/**
	 * Make URL Safe
	 *
	 * Makes a string safe for use in a URL segment - similar restrictions on short names
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function make_url_safe($str)
	{
		return preg_replace("/[^a-zA-Z0-9_\-\.]+$/i", '', $str);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Is URL Safe?
	 *
	 * Checks if a string is safe for use in a URL segment - similar restrictions on short names
	 *
	 * @param	string
	 * @return	bool
	 */
	public function is_url_safe($str)
	{
		return preg_match("/^[a-zA-Z0-9_\-\.]+$/i", $str) ? TRUE : FALSE;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Unique URL Title
	 * 
	 * Useful for those database tables that work the same as regards url_titles.  Takes the original
	 * string and which type of data we are checking against and returns a valid URL Title or FALSE
	 * if it is unable to create one.
	 *
	 * @param	string
	 * @param	string integer
	 * @param	string integer
	 * @param	string
	 * @return	string
	 */	
	protected function _unique_url_title($url_title, $self_id, $type_id = '', $type = 'channel')
	{
		if ($type_id == '')
		{
			return FALSE;
		}
	
		switch($type)
		{
			case 'category'	: $table = 'categories';		$url_title_field = 'cat_url_title';	$type_field = 'group_id';	$self_field = 'category_id';
				break;
			default			: $table = 'channel_titles';	$url_title_field = 'url_title';		$type_field = 'channel_id'; $self_field = 'entry_id';
				break;
		}
	
		// Field is limited to 75 characters, so trim url_title before querying
		$url_title = substr($url_title, 0, 75);

		if ($self_id != '')
		{
			$this->EE->db->where(array($self_field.' !=' => $self_id));
		}

		$this->EE->db->where(array($url_title_field => $url_title, $type_field => $type_id));
		$count = $this->EE->db->count_all_results($table);
		
		if ($count > 0)
		{
			// We may need some room to add our numbers- trim url_title to 70 characters
			$url_title = substr($url_title, 0, 70);
			
			// Check again
			if ($self_id != '')
			{
				$this->EE->db->where(array($self_field.' !=' => $self_id));
			}

			$this->EE->db->where(array($url_title_field => $url_title, $type_field => $type_id));
			$count = $this->EE->db->count_all_results($table);
			
			if ($count > 0)
			{
				if ($self_id != '')
				{
					$this->EE->db->where(array($self_field.' !=' => $self_id));
				}
			
				$this->EE->db->select("{$url_title_field}, MID({$url_title_field}, ".(strlen($url_title) + 1).") + 1 AS next_suffix", FALSE);
				$this->EE->db->where("{$url_title_field} REGEXP('".preg_quote($this->EE->db->escape_str($url_title))."[0-9]*$')");
				$this->EE->db->where(array($type_field => $type_id));
				$this->EE->db->order_by('next_suffix', 'DESC');
				$this->EE->db->limit(1);
				$query = $this->EE->db->get($table);
			
				// Did something go tragically wrong?  Is the appended number going to kick us over the 75 character limit?
				if ($query->num_rows() == 0 OR ($query->row('next_suffix') > 99999))
				{
					return FALSE;
				}
			
				$url_title = $url_title.$query->row('next_suffix');
			
				// little double check for safety
			
				if ($self_id != '')
				{
					$this->EE->db->where(array($self_field.' !=' => $self_id));
				}
			
				$this->EE->db->where(array($url_title_field => $url_title, $type_field => $type_id));
				$count = $this->EE->db->count_all_results($table);
			
				if ($count > 0)
				{
					return FALSE;
				}
			}
		}
		
		return $url_title;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Magic Get Method
	 * 
	 * It can be quite useful to read out some of the private members in the
	 * API libraries, but some of the workflows require that they remain
	 * unchanged. So we meet in the middle and give read access to all of them.
	 *
	 * @param	string	variable name
	 * @return	mixed	variable value
	 */
	public function __get($key)
	{
		return $this->$key;
	}
}
// END CLASS

/* End of file Api.php */
/* Location: ./system/expressionengine/libraries/api/Api.php */