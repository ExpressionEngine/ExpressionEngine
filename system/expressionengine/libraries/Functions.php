<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Functions Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Functions {  
	
	var $seed 				= FALSE; // Whether we've seeded our rand() function.  We only seed once per script execution
	var $cached_url			= array();
	var $cached_path		= array();
	var $cached_index		= array();
	var $cached_captcha		= '';
	var $template_map		= array();
	var $template_type		= '';
	var $action_ids			= array();
	var $file_paths	 		= array();
	var $conditional_debug = FALSE;
	var $catfields			= array();
	  
	/**
	 * Constructor
	 */	  
	function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}
  	

	
	// --------------------------------------------------------------------

	/**
	 * Fetch base site index
	 *
	 * @access	public
	 * @param	bool
	 * @param	bool
	 * @return	string
	 */
	function fetch_site_index($add_slash = FALSE, $sess_id = TRUE)
	{
		if (isset($this->cached_index[$add_slash.$sess_id.$this->template_type]))
		{
			return $this->cached_index[$add_slash.$sess_id.$this->template_type];
		}
				
		$url = $this->EE->config->slash_item('site_url');
		
		$url .= $this->EE->config->item('site_index');
		
		if ($this->EE->config->item('force_query_string') == 'y')
		{
			$url .= '?';
		}
		
		if (is_object($this->EE->session) && $this->EE->session->userdata('session_id') != '' && REQ != 'CP' && $sess_id == TRUE && 
			$this->EE->config->item('user_session_type') != 'c' && $this->template_type == 'webpage')
		{ 
			$url .= "/S=".$this->EE->session->userdata('session_id')."/";
		}
		
		if ($add_slash == TRUE)
		{
			if (substr($url, -1) != '/')
			{
				$url .= "/";
			}
		}
		
		$this->cached_index[$add_slash.$sess_id.$this->template_type] = $url;
		return $url;
	} 
	
	// --------------------------------------------------------------------

	/**
	 * Create a custom URL
	 *
	 * The input to this function is parsed and added to the
	 * full site URL to create a full URL/URI
	 *
	 * @access	public
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	function create_url($segment, $sess_id = TRUE)
	{
		// Since this function can be used via a callback
		// we'll fetch the segment if it's an array
		if (is_array($segment))
		{
			$segment = $segment[1];
		}
		
		if (isset($this->cached_url[$segment]))
		{
			return $this->cached_url[$segment];
		}

		$full_segment = $segment;		 
		$segment = str_replace(array("'", '"'), '', $segment);
		$segment = preg_replace("/(.+?(\/))index(\/)(.*?)/", "\\1\\2", $segment);		
		$segment = preg_replace("/(.+?(\/))index$/", "\\1", $segment);

		// These are exceptions to the normal path rules		
		if ($segment == '' OR strtolower($segment) == 'site_index')
		{
			return $this->fetch_site_index();
		}
		
		if (strtolower($segment) == 'logout')
		{
			$qs = ($this->EE->config->item('force_query_string') == 'y') ? '' : '?';		
			return $this->fetch_site_index(0, 0).$qs.'ACT='.$this->fetch_action_id('Member', 'member_logout');
		}	
		// END Specials

		// Load the string helper
		$this->EE->load->helper('string');
 
		$base = $this->fetch_site_index(0, $sess_id).'/'.trim_slashes($segment);
		
		$out = $this->remove_double_slashes($base);			
						
		$this->cached_url[$full_segment] = $out;
						
		return $out;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Creates a url for Pages links
	 *
	 * @access	public
	 * @return	string
	 */
	function create_page_url($base_url, $segment, $trailing_slash = true)
	{
		// Load the string helper
		$this->EE->load->helper('string');       

		if ($this->EE->config->item('force_query_string') == 'y')
		{
			if (strpos($base_url, $this->EE->config->item('index_page') . '/') !== FALSE)
			{
				$base_url = rtrim($base_url, '/');
			}
			
			$base_url .= '?';
		}
		
		$base = $base_url.'/'.trim_slashes($segment);
       
       if (substr($base, -1) != '/' && $trailing_slash == TRUE)
       {
           $base .= '/';
       }
       
       $out = $this->remove_double_slashes($base);
               
       return $out;          
	}
	

	// --------------------------------------------------------------------

	/**
	 * Fetch site index with URI query string
	 *
	 * @access	public
	 * @return	string
	 */
	function fetch_current_uri()
	{ 
		return rtrim($this->remove_double_slashes($this->fetch_site_index(1).$this->EE->uri->uri_string), '/');
	}

	// --------------------------------------------------------------------

	/**
	 * Prep Query String
	 *
	 * This function checks to see if "Force Query Strings" is on.
	 * If so it adds a question mark to the URL if needed
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function prep_query_string($str)
	{
		if (stristr($str, '.php') && substr($str, -7) == '/index/')
		{
			$str = substr($str, 0, -6);
		}
		
		if (strpos($str, '?') === FALSE && $this->EE->config->item('force_query_string') == 'y')
		{
			if (stristr($str, '.php'))
			{
				$str = preg_replace("#(.+?)\.php(.*?)#", "\\1.php?\\2", $str);
			}
			else
			{
				$str .= "?";
			}
		}
		
		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Convert EE Tags to Entities
	 *
	 * @access	public
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	function encode_ee_tags($str, $convert_curly = FALSE)
	{
		if ($str != '')
		{
			if ($convert_curly === TRUE)
			{
				$str = str_replace(array('{', '}'), array('&#123;', '&#125;'), $str);
			}
			else
			{
				$str = preg_replace("/\{(\/){0,1}exp:(.+?)\}/", "&#123;\\1exp:\\2&#125;", $str);
				$str = str_replace(array('{exp:', '{/exp'), array('&#123;exp:', '&#123;\exp'), $str);				
				$str = preg_replace("/\{embed=(.+?)\}/", "&#123;embed=\\1&#125;", $str);
				$str = preg_replace("/\{path:(.+?)\}/", "&#123;path:\\1&#125;", $str);
				$str = preg_replace("/\{redirect=(.+?)\}/", "&#123;redirect=\\1&#125;", $str);
			}
		}
		
		return $str;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Remove duplicate slashes from URL
	 *
	 * With all the URL/URI parsing/building, there is the potential
	 * to end up with double slashes.  This is a clean-up function.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function remove_double_slashes($str)
	{
		return preg_replace("#(^|[^:])//+#", "\\1/", $str);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Extract path info
	 *
	 * We use this to extract the template group/template name
	 * from path variables, like {some_var path="channel/index"}
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function extract_path($str)
	{
		if (preg_match("#=(.*)#", $str, $match))
		{		
			if (isset($this->cached_path[$match[1]]))
			{
				return $this->cached_path[$match[1]];
			}

			// Load the string helper
			$this->EE->load->helper('string');

			$path = trim_slashes(str_replace(array("'",'"'), "", $match[1]));
			
			if (substr($path, -6) == 'index/')
			{
				$path = str_replace('/index', '', $path);
			}
			
			if (substr($path, -5) == 'index')
			{
				$path = str_replace('/index', '', $path);
			}
			
			$this->cached_path[$match[1]] = $path;
		
			return $path;
		}
		else
		{
			return 'SITE_INDEX';
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Replace variables
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function var_swap($str, $data)
	{
		if ( ! is_array($data))
		{
			return FALSE;
		}
	
		foreach ($data as $key => $val)
		{
			$str = str_replace('{'.$key.'}', $val, $str);
		}
	
		return $str;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Redirect
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function redirect($location, $method = FALSE)
	{
		$location = str_replace('&amp;', '&', $this->insert_action_ids($location));

		if (count($this->EE->session->flashdata))
		{			
			// Ajax requests don't redirect - serve the flashdata
			
			if ($this->EE->input->server('HTTP_X_REQUESTED_WITH') && ($this->EE->input->server('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest'))
			{
				// We want the data that would be available for the next request
				$this->EE->session->_age_flashdata();
				die($this->EE->javascript->generate_json($this->EE->session->flashdata));
			}
		}

		if ($method === FALSE)
		{
			$method = $this->EE->config->item('redirect_method');
		}		

		switch($method)
		{
			case 'refresh'	: header("Refresh: 0;url=$location");
				break;
			default			: header("Location: $location");
				break;
		}

		exit;
	}

	
	// --------------------------------------------------------------------

	/**
	 * Convert a string into an encrypted hash
	 * DEPRECATED 2.0
	 * 
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function hash($str)
	{
		$this->EE->load->helper('security');
		return do_hash($str);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Random number/password generator
	 *
	 * @access	public
	 * @param	string
	 * @param	int
	 * @return	string
	 */
	function random($type = 'encrypt', $len = 8)
	{
		$this->EE->load->helper('string');
		return random_string($type, $len);
	}

	// --------------------------------------------------------------------

	/**
	 * Form declaration
	 *
	 * This function is used by modules when they need to create forms
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */	
	function form_declaration($data)
	{
		// Load the form helper
		$this->EE->load->helper('form');
		
		$deft = array(
						'hidden_fields'	=> array(),
						'action'		=> '', 
						'id'			=> '',
						'class'			=> '',
						'secure'		=> TRUE,
						'enctype' 		=> '',
						'onsubmit'		=> '',
					);
		
		
		foreach ($deft as $key => $val)
		{
			if ( ! isset($data[$key]))
			{
				$data[$key] = $val;
			}
		}
		
		if (is_array($data['hidden_fields']) && ! isset($data['hidden_fields']['site_id']))
		{
			$data['hidden_fields']['site_id'] = $this->EE->config->item('site_id');
		}


		// Add the CSRF Protection Hash
		if ($this->EE->config->item('csrf_protection') == TRUE )
		{
			$data['hidden_fields'][$this->EE->security->csrf_token_name] = $this->EE->security->csrf_hash;		
		}

		// 'form_declaration_modify_data' hook.
		//  - Modify the $data parameters before they are processed
		if ($this->EE->extensions->active_hook('form_declaration_modify_data') === TRUE)
		{
			$data = $this->EE->extensions->call('form_declaration_modify_data', $data);
		}
		
		// 'form_declaration_return' hook.
		//  - Take control of the form_declaration function
		if ($this->EE->extensions->active_hook('form_declaration_return') === TRUE)
		{
			$form = $this->EE->extensions->call('form_declaration_return', $data);
			if ($this->EE->extensions->end_script === TRUE) return $form;
		}
			
		if ($data['action'] == '')
		{
			$data['action'] = $this->fetch_site_index();
		}
		
		if ($data['onsubmit'] != '')
		{
			$data['onsubmit'] = 'onsubmit="'.trim($data['onsubmit']).'"';
		}
		
		if (substr($data['action'], -1) == '?')
		{
			$data['action'] = substr($data['action'], 0, -1);
		}
		
		$data['name']	= (isset($data['name']) && $data['name'] != '') ? 'name="'.$data['name'].'" '	: '';
		$data['id']		= ($data['id'] != '') 							? 'id="'.$data['id'].'" ' 		: '';
		$data['class']	= ($data['class'] != '')						? 'class="'.$data['class'].'" '	: '';

		if ($data['enctype'] == 'multi' OR strtolower($data['enctype']) == 'multipart/form-data')
		{
			$data['enctype'] = 'enctype="multipart/form-data" ';
		}
		
		$form  = '<form '.$data['id'].$data['class'].$data['name'].'method="post" action="'.$data['action'].'" '.$data['onsubmit'].' '.$data['enctype'].">\n";
		
		if ($data['secure'] == TRUE)
		{
			if ($this->EE->config->item('secure_forms') == 'y')
			{
				if ( ! isset($data['hidden_fields']['XID']))
				{
					$data['hidden_fields'] = array_merge(array('XID' => '{XID_HASH}'), $data['hidden_fields']);
				}
				elseif ($data['hidden_fields']['XID'] == '')
				{
					$data['hidden_fields']['XID']  = '{XID_HASH}';
				}
			}
		}
	
		if (is_array($data['hidden_fields']))
		{
			$form .= "<div class='hiddenFields'>\n";
			
			foreach ($data['hidden_fields'] as $key => $val)
			{
				$form .= '<input type="hidden" name="'.$key.'" value="'.form_prep($val).'" />'."\n";
			}
			
			$form .= "</div>\n\n";
		}
			  
		return $form;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Form backtrack
	 *
	 * This function lets us return a user to a previously
	 * visited page after submitting a form.  The page
	 * is determined by the offset that the admin
	 * places in each form
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */	
	function form_backtrack($offset = '')
	{
		$ret = $this->fetch_site_index();

		if ($offset != '')
		{
			if (isset($this->EE->session->tracker[$offset]))
			{
				if ($this->EE->session->tracker[$offset] != 'index')
				{
					return $this->remove_double_slashes($this->fetch_site_index().'/'.$this->EE->session->tracker[$offset]);
				}
			}
		}
		
		if (isset($_POST['RET']))
		{
			if (strncmp($_POST['RET'], '-', 1) == 0)
			{
				$return = str_replace("-", "", $_POST['RET']);
				
				if (isset($this->EE->session->tracker[$return]))
				{
					if ($this->EE->session->tracker[$return] != 'index')
					{
						$ret = $this->fetch_site_index().'/'.$this->EE->session->tracker[$return];
					}
				}
			}
			else
			{	
				if (strpos($_POST['RET'], '/') !== FALSE)
				{
					if (strncasecmp($_POST['RET'], 'http://', 7) == 0 OR
						strncasecmp($_POST['RET'], 'https://', 8) == 0 OR
						strncasecmp($_POST['RET'], 'www.', 4) == 0)
					{
						$ret = $_POST['RET'];
					}
					else
					{
						$ret = $this->create_url($_POST['RET']);
					}
				}
				else
				{
					$ret = $_POST['RET'];
				}
			}
		
			// We need to slug in the session ID if the admin is running
			// their site using sessions only.  Normally the $this->EE->functions->fetch_site_index()
			// function adds the session ID automatically, except in cases when the 
			// $_POST['RET'] variable is set. Since the login routine relies on the RET
			// info to know where to redirect back to we need to sandwich in the session ID.
			if ($this->EE->config->item('user_session_type') != 'c')
			{				
				if ($this->EE->session->userdata['session_id'] != '' && ! stristr($ret, $this->EE->session->userdata['session_id']))
				{
					$url = $this->EE->config->slash_item('site_url');
					
					$url .= $this->EE->config->item('site_index');
			
					if ($this->EE->config->item('force_query_string') == 'y')
					{
						$url .= '?';
					}		
			
					$sess_id = "/S=".$this->EE->session->userdata['session_id']."/";
	
					$ret = str_replace($url, $url.$sess_id, $ret);			
				}			
			}			
		} 
		
		return $this->remove_double_slashes($ret);
	}
	
	// --------------------------------------------------------------------

	/**
	 * eval() 
	 *
	 * Evaluates a string as PHP
	 *
	 * @access	public
	 * @param	string
	 * @return	mixed
	 */	
	function evaluate($str)
	{	
		return eval('?'.'>'.$str.'<?php ');		
	}
	
	// --------------------------------------------------------------------

	/**
	 * Encode email from template callback
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function encode_email($str)
	{
		if (isset($this->EE->session->cache['functions']['emails'][$str]))
		{
			return preg_replace("/(eeEncEmail_)\w+/", '\\1'.$this->EE->functions->random('alpha', 10), $this->EE->session->cache['functions']['emails'][$str]);
		}
	
		$email = (is_array($str)) ? trim($str[1]) : trim($str);
		
		$title = '';
		$email = str_replace(array('"', "'"), '', $email);
		
		if ($p = strpos($email, "title="))
		{
			$title = substr($email, $p + 6);
			$email = trim(substr($email, 0, $p));
		}
	
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		
		$encoded = $this->EE->typography->encode_email($email, $title, TRUE);
		
		$this->EE->session->cache['functions']['emails'][$str] = $encoded;

		return $encoded;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Delete spam prevention hashes
	 *
	 * @access	public
	 * @return	void
	 */
	function clear_spam_hashes()
	{	 
		if ($this->EE->config->item('secure_forms') == 'y')
		{
			$this->EE->db->query("DELETE FROM exp_security_hashes WHERE date < UNIX_TIMESTAMP()-7200");
		}	
	}
	
	// --------------------------------------------------------------------

	/**
	 * Set Cookie
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	function set_cookie($name = '', $value = '', $expire = '')
	{
		if ( ! is_numeric($expire))
		{
			$expire = time() - 86500;
		}
		else
		{
			if ($expire > 0)
			{
				$expire = time() + $expire;
			}
			else
			{
				$expire = 0;
			}
		}
					
		$prefix = ( ! $this->EE->config->item('cookie_prefix')) ? 'exp_' : $this->EE->config->item('cookie_prefix').'_';
		$path	= ( ! $this->EE->config->item('cookie_path'))	? '/'	: $this->EE->config->item('cookie_path');
		
		if (REQ == 'CP' && $this->EE->config->item('multiple_sites_enabled') == 'y')
		{
			$domain = $this->EE->config->cp_cookie_domain;
		}
		else
		{
			$domain = ( ! $this->EE->config->item('cookie_domain')) ? '' : $this->EE->config->item('cookie_domain');
		}
		
		$value = stripslashes($value);
					
		setcookie($prefix.$name, $value, $expire, $path, $domain, 0);
	}

	// --------------------------------------------------------------------

	/**
	 * Character limiter
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function char_limiter($str, $num = 500)
	{
		if (strlen($str) < $num) 
		{
			return $str;
		}
		
		$str = str_replace("\n", " ", $str);		
		
		$str = preg_replace("/\s+/", " ", $str);

		if (strlen($str) <= $num)
		{
			return $str;
		}
		$str = trim($str);
										
		$out = "";
				
		foreach (explode(" ", trim($str)) as $val)
		{
			$out .= $val;			
												
			if (strlen($out) >= $num)
			{
				return (strlen($out) == strlen($str)) ? $out : $out.'&#8230;'; 
			}
			
			$out .= ' ';
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Word limiter
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function word_limiter($str, $num = 100)
	{
		if (strlen($str) < $num) 
		{
			return $str;
		}
		
		// allows the split to work properly with multi-byte Unicode characters
		if (is_php('4.3.2') === TRUE)
		{
			$word = preg_split('/\s/u', $str, -1, PREG_SPLIT_NO_EMPTY);	
		}
		else
		{
			$word = preg_split('/\s/', $str, -1, PREG_SPLIT_NO_EMPTY);
		}
		
		if (count($word) <= $num)
		{
			return $str;
		}
				
		$str = "";
				 
		for ($i = 0; $i < $num; $i++) 
		{
			$str .= $word[$i]." ";
		}

		return trim($str).'&#8230;'; 
	}
	
	// --------------------------------------------------------------------

	/**
	 * Fetch Email Template
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function fetch_email_template($name)
	{
		$query = $this->EE->db->query("SELECT template_name, data_title, template_data, enable_template FROM exp_specialty_templates WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."' AND template_name = '".$this->EE->db->escape_str($name)."'");

		// Unlikely that this is necessary but it's possible a bad template request could
		// happen if a user hasn't run the update script.
		if ($query->num_rows() == 0)
		{
			return array('title' => '', 'data' => '');
		}

		if ($query->row('enable_template')  == 'y')
		{
			return array('title' => $query->row('data_title') , 'data' => $query->row('template_data') );
		}
		
		$this->EE->load->library('security');
		
		if ($this->EE->session->userdata['language'] != '')
		{
			$user_lang = $this->EE->session->userdata['language'];
		}
		else
		{
			if ($this->EE->input->cookie('language'))
			{
				$user_lang = $this->EE->input->cookie('language');
			}
			elseif ($this->EE->config->item('deft_lang') != '')
			{
				$user_lang = $this->EE->config->item('deft_lang');
			}
			else
			{
				$user_lang = 'english';
			}
		}

		$user_lang = $this->EE->security->sanitize_filename($user_lang);

		if ( function_exists($name))
		{
			$title = $name.'_title';
		
			return array('title' => $title(), 'data' => $name());
		}
		else
		{
			if ( ! @include(APPPATH.'language/'.$user_lang.'/email_data'.EXT))
			{
				return array('title' => $query->row('data_title') , 'data' => $query->row('template_data') );
			}
			
			if (function_exists($name))
			{
				$title = $name.'_title';
		
				return array('title' => $title(), 'data' => $name());
			}
			else
			{
				return array('title' => $query->row('data_title') , 'data' => $query->row('template_data') );
			}
		}
	}
		
	// --------------------------------------------------------------------

	/**
	 * Create character encoding menu
	 *
	 * DEPRECATED IN 2.0
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function encoding_menu($name, $selected = '')
	{
		$file = APPPATH.'config/languages'.EXT;	

		if ( ! file_exists($file)) 
		{
			return FALSE;
		}

		require_once $file;
		
		$languages = array_flip($languages);
		
		$this->EE->load->helper('form');
		
		return form_dropdown($name, $languages, $selected);
	}

	// --------------------------------------------------------------------

	/**
	 * Create Directory Map
	 *
	 * @access	public
	 * @param	string
	 * @param	bool
	 * @return	array
	 */
	function create_directory_map($source_dir, $top_level_only = FALSE)
	{
		if ( ! isset($filedata))
			$filedata = array();
		
		if ($fp = @opendir($source_dir))
		{ 
			while (FALSE !== ($file = readdir($fp)))
			{
				if (@is_dir($source_dir.$file) && substr($file, 0, 1) != '.' AND $top_level_only == FALSE) 
				{		
					$temp_array = array();
					 
					$temp_array = $this->create_directory_map($source_dir.$file."/");	
					
					$filedata[$file] = $temp_array;
				}
				elseif (substr($file, 0, 1) != "." && $file != 'index.html')
				{
					$filedata[] = $file;
				}
			}		 
			return $filedata;		
		} 
	} 
	
	// --------------------------------------------------------------------

	/**
	 * Create pull-down optios from dirctory map
	 *
	 * @access	public
	 * @param	array
	 * @param	string
	 * @return	string
	 */
	function render_map_as_select_options($zarray, $array_name = '') 
	{	
		foreach ($zarray as $key => $val)
		{
			if ( is_array($val))
			{
				if ($array_name != '')
				{
					$key = $array_name.'/'.$key;					
				}
			
				$this->render_map_as_select_options($val, $key);
			}		
			else
			{
				if ($array_name != '')
				{
					$val = $array_name.'/'.$val;					
				}
					
				if (substr($val, -4) == '.php')
				{
					if ($val != 'theme_master.php')
					{					
						$this->template_map[] = $val;
					}
				}
			}
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Fetch names of installed language packs
	 *
	 * DEPRECATED IN 2.0
	 * 
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function language_pack_names($default)
	{
		$source_dir = APPPATH.'language/';

		$dirs = array();

		if ($fp = @opendir($source_dir))
		{
			while (FALSE !== ($file = readdir($fp)))
			{
				if (is_dir($source_dir.$file) && substr($file, 0, 1) != ".")
				{
					$dirs[] = $file;
				}
			}
			closedir($fp);
		}

		sort($dirs);
		
		$r  = "<div class='default'>";
		$r .= "<select name='deft_lang' class='select'>\n";

		foreach ($dirs as $dir)
		{
			$selected = ($dir == $default) ? " selected='selected'" : '';
			$r .= "<option value='{$dir}'{$selected}>".ucfirst($dir)."</option>\n";
		}

		$r .= "</select>";
		$r .= "</div>";

		return $r;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Delete cache files
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function clear_caching($which, $sub_dir = '', $relationships=FALSE)
	{
		$actions = array('page', 'tag', 'db', 'sql', 'relationships', 'all');
		
		if ( ! in_array($which, $actions))
		{
			return;			
		}

		/* -------------------------------------
		/*  Disable Tag Caching
		/*  
		/*  All for you, Nevin!  Disables tag caching, which if used unwisely
		/*  on a high traffic site can lead to disastrous disk i/o
		/*  This setting allows quick thinking admins to temporarily disable
		/*  it without hacking or modifying folder permissions
		/*  
		/*  Hidden Configuration Variable
		/*  - disable_tag_caching => Disable tag caching? (y/n)
		/* -------------------------------------*/

		if ($which == 'tag' && $this->EE->config->item('disable_tag_caching') == 'y')
		{
			return;
		}
		
		$db_path = '';
			
		if ($sub_dir != '')
		{
			if ($which == 'all' OR $which == 'db')
			{
				$segs = explode('/', str_replace($this->fetch_site_index(), '', $sub_dir));

				$segment_one = (isset($segs['0'])) ? $segs['0'] : 'default';
				$segment_two = (isset($segs['1'])) ? $segs['1'] : 'index';	
				
				$db_path = '/'.$segment_one.'+'.$segment_two.'/';			
			}

			$sub_dir = '/'.md5($sub_dir).'/';
		}
	
		switch ($which)
		{
			case 'page' : $this->delete_directory(APPPATH.'cache/page_cache'.$sub_dir);
				break;
			case 'db'	: $this->delete_directory(APPPATH.'cache/db_cache_'.$this->EE->config->item('site_id').$db_path);
				break;
			case 'tag'  : $this->delete_directory(APPPATH.'cache/tag_cache'.$sub_dir);
				break;
			case 'sql'  : $this->delete_directory(APPPATH.'cache/sql_cache'.$sub_dir);
				break;
			case 'relationships' : $this->EE->db->query("UPDATE exp_relationships SET rel_data = '', reverse_rel_data = ''");
				break;
			case 'all'  : 
						$this->delete_directory(APPPATH.'cache/page_cache'.$sub_dir);
						$this->delete_directory(APPPATH.'cache/db_cache_'.$this->EE->config->item('site_id').$db_path);
						$this->delete_directory(APPPATH.'cache/sql_cache'.$sub_dir);

						if ($this->EE->config->item('disable_tag_caching') != 'y')
						{
							$this->delete_directory(APPPATH.'cache/tag_cache'.$sub_dir);
						}
												  
						if ($relationships === TRUE)
						{
							$this->EE->db->query("UPDATE exp_relationships SET rel_data = '', reverse_rel_data = ''");
						}
				break;
		}			
	}
	
	// --------------------------------------------------------------------

	/**
	 * Delete Direcories
	 *
	 * @access	public
	 * @param	string
	 * @param	bool
	 * @return	void
	 */
	function delete_directory($path, $del_root = FALSE)
	{
		$path = rtrim($path, '/');

		if ( ! is_dir($path))
		{
			return FALSE;
		}
		
		// let's try this the sane way first
		@exec("mv {$path} {$path}_delete", $out, $ret);

		if (isset($ret) && $ret == 0)
		{
			if ($del_root === FALSE)
			{
				@mkdir($path, 0777);
				
				if ($fp = @fopen($path.'/index.html', FOPEN_WRITE_CREATE_DESTRUCTIVE))
				{
					fclose($fp);
				}				
			}

			@exec("rm -r -f {$path}_delete");
		}
		else
		{
			if ( ! $current_dir = @opendir($path))
			{
				return;
			}

			while($filename = @readdir($current_dir))
			{		
				if (@is_dir($path.'/'.$filename) and ($filename != "." and $filename != ".."))
				{
					$this->delete_directory($path.'/'.$filename, TRUE);
				}
				elseif($filename != "." and $filename != "..")
				{
					@unlink($path.'/'.$filename);
				}
			}

			@closedir($current_dir);

			if (substr($path, -6) == '_cache' && $fp = @fopen($path.'/index.html', FOPEN_WRITE_CREATE_DESTRUCTIVE))
			{
				fclose($fp);			
			}

			if ($del_root == TRUE)
			{
				@rmdir($path);
			}			
		}
	}
 
	// --------------------------------------------------------------------

	/**
	 * Fetch allowed channels
	 *
	 * This function fetches the ID numbers of the
	 * channels assigned to the currently logged in user.
	 *
	 * @access	public
	 * @param	bool
	 * @return	array
	 */
	function fetch_assigned_channels($all_sites = FALSE)
	{
		$allowed_channels = array();
		
		if (REQ == 'CP' AND isset($this->EE->session->userdata['assigned_channels']) && $all_sites === FALSE)
		{
			$allowed_channels = array_keys($this->EE->session->userdata['assigned_channels']);
		}
		elseif ($this->EE->session->userdata['group_id'] == 1)
		{
			if ($all_sites === TRUE)
			{
				$this->EE->db->select('channel_id');
				$query = $this->EE->db->get('channels');
			}
			else
			{
				$this->EE->db->select('channel_id');
				$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
				$query = $this->EE->db->get('channels');
			}
			
			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$allowed_channels[] = $row['channel_id'];
				}
			}
		}
		else
		{
			if ($all_sites === TRUE)
			{
				$result = $this->EE->db->query("SELECT exp_channel_member_groups.channel_id FROM exp_channel_member_groups 
									  WHERE exp_channel_member_groups.group_id = '".$this->EE->db->escape_str($this->EE->session->userdata['group_id'])."'");
			}
			else
			{
				$result = $this->EE->db->query("SELECT exp_channels.channel_id FROM exp_channels, exp_channel_member_groups 
									  WHERE exp_channels.channel_id = exp_channel_member_groups.channel_id
									  AND exp_channels.site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'
									  AND exp_channel_member_groups.group_id = '".$this->EE->db->escape_str($this->EE->session->userdata['group_id'])."'");
			}
			
			if ($result->num_rows() > 0)
			{
				foreach ($result->result_array() as $row)
				{
					$allowed_channels[] = $row['channel_id'];
				}
			}
		}

		return array_values($allowed_channels);
	}

	// --------------------------------------------------------------------

	/**
	 * Log Search terms
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */  
	function log_search_terms($terms = '', $type = 'site')
	{
		if ($terms == '' OR $this->EE->db->table_exists('exp_search_log') === FALSE)
			return;
			
		if ($this->EE->config->item('enable_search_log') == 'n')
			return;
			
		$this->EE->load->helper('xml');			
			
		$search_log = array(
								'member_id'		=> $this->EE->session->userdata('member_id'),
								'screen_name'	=> $this->EE->session->userdata('screen_name'),
								'ip_address'	=> $this->EE->input->ip_address(),
								'search_date'	=> $this->EE->localize->now,
								'search_type'	=> $type,
								'search_terms'	=> xml_convert($this->EE->functions->encode_ee_tags($this->EE->security->xss_clean($terms), TRUE)),
								'site_id'		=> $this->EE->config->item('site_id')
							);
								
		$this->EE->db->query($this->EE->db->insert_string('exp_search_log', $search_log));
		
		// Prune Database
		srand(time());
		if ((rand() % 100) < 5) 
		{ 
			$max = ( ! is_numeric($this->EE->config->item('max_logged_searches'))) ? 500 : $this->EE->config->item('max_logged_searches');
		
			$query = $this->EE->db->query("SELECT MAX(id) as search_id FROM exp_search_log WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'");
			
			$row = $query->row_array();
			
			if (isset($row['search_id'] ) && $row['search_id'] > $max)
			{
				$this->EE->db->query("DELETE FROM exp_search_log WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."' AND id < ".($row['search_id'] -$max)."");
			}
		}
	}
 
	// --------------------------------------------------------------------

	/**
	 * Fetch Action ID
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function fetch_action_id($class, $method)
	{  
		if ($class == '' OR $method == '')
		{
			return FALSE;
		}
		
		$this->action_ids[ucfirst($class)][$method] = $method;
		
		return LD.'AID:'.ucfirst($class).':'.$method.RD;
	}	
	
	// --------------------------------------------------------------------

	/**
	 * Insert Action IDs
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function insert_action_ids($str)
	{
		if (count($this->action_ids) == 0)
		{
			return $str;
		}
		
		$sql = "SELECT action_id, class, method FROM exp_actions WHERE";

		foreach($this->action_ids as $key => $value)
		{
			foreach($value as $k => $v)
			{
				$sql .= " (class= '".$this->EE->db->escape_str($key)."' AND method = '".$this->EE->db->escape_str($v)."') OR";
			}
		}
		
		$query = $this->EE->db->query(substr($sql, 0, -3));
		
		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$str = str_replace(LD.'AID:'.$row['class'].':'.$row['method'].RD, $row['action_id'], $str);
			}
		}
		
		return $str;
	}
		
	// --------------------------------------------------------------------

	/**
	 * Compile and cache relationship data
	 *
	 * This is used when submitting new channel entries or gallery posts.
	 * It serializes the related entry data.  The reason it's in this 
	 * file is because it gets called from the publish class and the
	 * gallery class so we need it somewhere that is accessible to both.
	 *
	 * @access	public
	 * @param	string
	 * @param	bool
	 * @param	bool
	 * @return	void
	 */
	function compile_relationship($data, $parent_entry = TRUE, $reverse = FALSE)
	{
		if ($data['type'] == 'channel' OR ($reverse === TRUE && $parent_entry === FALSE))
		{
			$sql = "SELECT t.entry_id, t.channel_id, t.forum_topic_id, t.author_id, t.ip_address, t.title, t.url_title, t.status, t.dst_enabled, t.view_count_one, t.view_count_two, t.view_count_three, t.view_count_four, t.allow_comments, t.comment_expiration_date, t.sticky, t.entry_date, t.year, t.month, t.day, t.entry_date, t.edit_date, t.expiration_date, t.recent_comment_date, t.comment_total, t.site_id as entry_site_id,
					w.channel_title, w.channel_name, w.channel_url, w.comment_url, w.comment_moderate, w.channel_html_formatting, w.channel_allow_img_urls, w.channel_auto_link_urls, 
					m.username, m.email, m.url, m.screen_name, m.location, m.occupation, m.interests, m.aol_im, m.yahoo_im, m.msn_im, m.icq, m.signature, m.sig_img_filename, m.sig_img_width, m.sig_img_height, m.avatar_filename, m.avatar_width, m.avatar_height, m.photo_filename, m.photo_width, m.photo_height, m.group_id, m.member_id, m.bday_d, m.bday_m, m.bday_y, m.bio,
					md.*,
					wd.*
			FROM exp_channel_titles		AS t
			LEFT JOIN exp_channels 		AS w  ON t.channel_id = w.channel_id 
			LEFT JOIN exp_channel_data	AS wd ON t.entry_id = wd.entry_id 
			LEFT JOIN exp_members		AS m  ON m.member_id = t.author_id 
			LEFT JOIN exp_member_data	AS md ON md.member_id = m.member_id 
			WHERE t.entry_id = '".(($reverse === TRUE && $parent_entry === FALSE) ? $data['parent_id'] : $data['child_id'])."'";
			
			$entry_query = $this->EE->db->query($sql);
	
			// Is there a category group associated with this channel?
			$query = $this->EE->db->query("SELECT cat_group FROM  exp_channels WHERE channel_id = '".$entry_query->row('channel_id') ."'");	 
			$cat_group = (trim($query->row('cat_group')) == '') ? FALSE : $query->row('cat_group');

			$this->cat_array = array();
			$cat_array = array();
	
			if ($cat_group !== FALSE)
			{
				$this->get_categories($cat_group, ($reverse === TRUE && $parent_entry === FALSE) ? $data['parent_id'] : $data['child_id']);
			}
			$cat_array = $this->cat_array;
			
			if ($parent_entry == TRUE)
			{
				$this->EE->db->query("INSERT INTO exp_relationships (rel_parent_id, rel_child_id, rel_type, rel_data, reverse_rel_data) 
							VALUES ('".$data['parent_id']."', '".$data['child_id']."', '".$data['type']."',
									'".addslashes(serialize(array('query' => $entry_query, 'cats_fixed' => '1', 'categories' => $cat_array)))."', '')");
				return $this->EE->db->insert_id();
			}
			else
			{
				if ($reverse === TRUE)
				{
					$this->EE->db->query("UPDATE exp_relationships 
								SET reverse_rel_data = '".addslashes(serialize(array('query' => $entry_query, 'cats_fixed' => '1', 'categories' => $cat_array)))."' 
								WHERE rel_type = '".$this->EE->db->escape_str($data['type'])."' AND rel_parent_id = '".$data['parent_id']."'");
				}
				else
				{
					$this->EE->db->query("UPDATE exp_relationships 
								SET rel_data = '".addslashes(serialize(array('query' => $entry_query, 'cats_fixed' => '1', 'categories' => $cat_array)))."' 
								WHERE rel_type = 'channel' AND rel_child_id = '".$data['child_id']."'");
				}
			}		
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Get Categories for Channel Entry/Entries
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	array
	 */
	function get_categories($cat_group, $entry_id)
	{		
		// fetch the custom category fields
		$field_sqla = '';
		$field_sqlb = '';
		
		$query = $this->EE->db->query("SELECT field_id, field_name FROM exp_category_fields WHERE group_id IN ('".str_replace('|', "','", $this->EE->db->escape_str($cat_group))."')");
			
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$this->catfields[] = array('field_name' => $row['field_name'], 'field_id' => $row['field_id']);
			}

			
			$field_sqla = ", cg.field_html_formatting, fd.* ";
			$field_sqlb = " LEFT JOIN exp_category_field_data AS fd ON fd.cat_id = c.cat_id 
							LEFT JOIN exp_category_groups AS cg ON cg.group_id = c.group_id";
		}

		$sql = "SELECT		c.cat_name, c.cat_url_title, c.cat_id, c.cat_image, p.cat_id, c.parent_id, c.cat_description, c.group_id
				{$field_sqla}
				FROM		(exp_categories AS c, exp_category_posts AS p)
				{$field_sqlb}
				WHERE		c.group_id	IN ('".str_replace('|', "','", $this->EE->db->escape_str($cat_group))."')
				AND			p.entry_id	= '".$entry_id."'
				AND			c.cat_id 	= p.cat_id
				ORDER BY	c.parent_id, c.cat_order";
	
		$sql = str_replace("\t", " ", $sql);
		$query = $this->EE->db->query($sql);
		
		$this->cat_array = array();
		$parents = array();
				
		if ($query->num_rows() > 0)
		{
			$this->temp_array = array();
			
			foreach ($query->result_array() as $row)
			{	
				$this->temp_array[$row['cat_id']] = array($row['cat_id'], $row['parent_id'], $row['cat_name'], $row['cat_image'], $row['cat_description'], $row['group_id'], $row['cat_url_title']);
						
				if ($field_sqla != '')
				{
					foreach ($row as $k => $v)
					{
						if (strpos($k, 'field') !== FALSE)
						{
							$this->temp_array[$row['cat_id']][$k] = $v;
						}
					}
				}

				if ($row['parent_id'] > 0 && ! isset($this->temp_array[$row['parent_id']])) $parents[$row['parent_id']] = '';
				unset($parents[$row['cat_id']]);			  
			}
				
			foreach($this->temp_array as $k => $v) 
			{			
				if (isset($parents[$v[1]])) $v[1] = 0;
					
				if (0 == $v[1])
				{	
					$this->cat_array[] = $v;
					$this->process_subcategories($k);
				}
			}
		
			unset($this->temp_array);
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Process Subcategories
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function process_subcategories($parent_id)
	{		
		foreach($this->temp_array as $key => $val) 
		{
			if ($parent_id == $val[1])
			{
				$this->cat_array[] = $val;
				$this->process_subcategories($key);
			}
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Add security hashes to forms
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function add_form_security_hash($str)
	{
		if ($this->EE->config->item('secure_forms') == 'y')
		{
			if (preg_match_all("/({XID_HASH})/", $str, $matches))
			{
				$db_reset = FALSE;
				
				// Disable DB caching if it's currently set
				
				if ($this->EE->db->cache_on == TRUE)
				{
					$this->EE->db->cache_off();
					$db_reset = TRUE;
				}
			
				// Add security hashes
				
				$sql = "INSERT INTO exp_security_hashes (date, ip_address, hash) VALUES";
				
				foreach ($matches[1] as $val)
				{
					$hash = $this->random('encrypt');
					$str = preg_replace("/{XID_HASH}/", $hash, $str, 1);
					$sql .= "(UNIX_TIMESTAMP(), '".$this->EE->input->ip_address()."', '".$hash."'),";
				}
				
				$this->EE->db->query(substr($sql,0,-1));
				
				// Re-enable DB caching
				
				if ($db_reset == TRUE)
				{
					$this->EE->db->cache_on();			
				}
			}
		}
	
		return $str;	
	}

	// --------------------------------------------------------------------

	/**
	 * Remap pMachine Pro URLs
	 *
	 *  Since pM URLs are different than EE URLs,
	 *  for those who have migrated from pM we will
	 *  check the URL formatting.  If the request is
	 *  for a pMachine URL, we'll remap it to the new EE location
	 *
	 *  DEPRECATED in 2.0
	 *
	 * @access	public
	 * @return	void
	 */
	function remap_pm_urls()
	{
		if ($this->EE->config->item('remap_pm_urls') == 'y' AND $this->EE->config->item('remap_pm_dest') !== FALSE AND $this->EE->uri->uri_string != '')
		{
			$p_uri = ( ! isset($_GET['id'])) ? $this->EE->uri->uri_string : '/'.$_GET['id'].'/';
			
			if (preg_match('/^\/[0-9]{1,6}(?:\_[0-9]{1,4}){3}/', $p_uri))
			{
				$pentry_id = substr($p_uri, 1, (strpos($p_uri, '_')-1));
			}
			elseif (preg_match('/^\/P[0-9]{1,6}/', $p_uri))
			{	
				$p_uri = str_replace("/", "", $p_uri);
				$pentry_id = substr($p_uri, 1);
			}
				
			if (isset($pentry_id) AND $pentry_id != '')
			{
				$query = $this->EE->db->query("SELECT url_title FROM exp_channel_titles WHERE pentry_id = '".$this->EE->db->escape_str($pentry_id)."'");
				
				if ($query->num_rows() == 1)
				{
					$this->redirect($this->EE->config->slash_item('remap_pm_dest').$query->row('url_title') .'/');
					exit;
				}
			}
		}		
	}
	
	// --------------------------------------------------------------------

	/**
	 * Generate CAPTCHA
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function create_captcha($old_word = '')
	{
		if ($this->EE->config->item('captcha_require_members') == 'n' AND $this->EE->session->userdata['member_id'] != 0)
		{
			return '';
		}
		
		// -------------------------------------------
		// 'create_captcha_start' hook.
		//  - Allows rewrite of how CAPTCHAs are created
		//
			if ($this->EE->extensions->active_hook('create_captcha_start') === TRUE)
			{
				$edata = $this->EE->extensions->call('create_captcha_start', $old_word);
				if ($this->EE->extensions->end_script === TRUE) return $edata;
			}	
		// -------------------------------------------
			
		$img_path	= $this->EE->config->slash_item('captcha_path', 1);
		$img_url	= $this->EE->config->slash_item('captcha_url');
		$use_font	= ($this->EE->config->item('captcha_font') == 'y') ? TRUE : FALSE;
				
		$font_face	= "texb.ttf";
		$font_size	= 16;
		
		$expiration = 60*60*2;  // 2 hours
		
		$img_width	= 140;	// Image width
		$img_height	= 30;	// Image height	
				
		if ($img_path == '' OR $img_url == '')
		{
			return FALSE;
		}

		if ( ! @is_dir($img_path)) 
		{
			return FALSE;
		}
		
		if ( ! is_really_writable($img_path))
		{
			return FALSE;
		}
		
		if ( ! file_exists(APPPATH.'config/captcha'.EXT))
		{
			return FALSE;
		}	
		
		if ( ! extension_loaded('gd'))
		{
			return FALSE;
		}
		
		if (substr($img_url, -1) != '/') $img_url .= '/';
		
		
		// Disable DB caching if it's currently set
		
		$db_reset = FALSE;
		if ($this->EE->db->cache_on == TRUE)
		{
			$this->EE->db->cache_off();
			$db_reset = TRUE;
		}
		
		// Remove old images - add a bit of randomness so we aren't doing this every page access
		
		list($usec, $sec) = explode(" ", microtime());
		$now = ((float)$usec + (float)$sec);
		
		if ((mt_rand() % 100) < $this->EE->session->gc_probability)
		{
			$old = time() - $expiration;
			$this->EE->db->query("DELETE FROM exp_captcha WHERE date < ".$old);		

			$current_dir = @opendir($img_path);

			while($filename = @readdir($current_dir))
			{		
				if ($filename != "." and $filename != ".." and $filename != "index.html")
				{
					$name = str_replace(".jpg", "", $filename);

					if (($name + $expiration) < $now)
					{
						@unlink($img_path.$filename);
					}
				}
			}

			@closedir($current_dir);			
		}
	
		// Fetch and insert word	
		if ($old_word == '')
		{
			require APPPATH.'config/captcha'.EXT;
			$word = $words[array_rand($words)];
			
			if ($this->EE->config->item('captcha_rand') == 'y')
			{
				$word .= $this->random('nozero', 2);
			}

			$this->EE->db->query("INSERT INTO exp_captcha (date, ip_address, word) VALUES (UNIX_TIMESTAMP(), '".$this->EE->input->ip_address()."', '".$this->EE->db->escape_str($word)."')");		
		}
		else
		{
			$word = $old_word;
		}
		
		$this->cached_captcha = $word;
		
		// Determine angle and position			
		$length	= strlen($word);
		$angle	= ($length >= 6) ? rand(-($length-6), ($length-6)) : 0;
		$x_axis	= rand(6, (360/$length)-16);			
		$y_axis = ($angle >= 0 ) ? rand($img_height, $img_width) : rand(6, $img_height);
		
		// Create image	
		$im = ImageCreate($img_width, $img_height);
				
		// Assign colors		
		$bg_color		= ImageColorAllocate($im, 255, 255, 255);
		$border_color	= ImageColorAllocate($im, 153, 102, 102);
		$text_color		= ImageColorAllocate($im, 204, 153, 153);
		$grid_color		= imagecolorallocate($im, 255, 182, 182);
		$shadow_color	= imagecolorallocate($im, 255, 240, 240);

		// Create the rectangle		
		ImageFilledRectangle($im, 0, 0, $img_width, $img_height, $bg_color);
		
		// Create the spiral pattern		
		$theta		= 1;
		$thetac		= 6;  
		$radius		= 12;  
		$circles	= 20;  
		$points		= 36;

		for ($i = 0; $i < ($circles * $points) - 1; $i++) 
		{
			$theta = $theta + $thetac;
			$rad = $radius * ($i / $points );
			$x = ($rad * cos($theta)) + $x_axis;
			$y = ($rad * sin($theta)) + $y_axis;
			$theta = $theta + $thetac;
			$rad1 = $radius * (($i + 1) / $points);
			$x1 = ($rad1 * cos($theta)) + $x_axis;
			$y1 = ($rad1 * sin($theta )) + $y_axis;
			imageline($im, $x, $y, $x1, $y1, $grid_color);
			$theta = $theta - $thetac;
		}

		//imageline($im, $img_width, $img_height, 0, 0, $grid_color);
	
		// Write the text		
		$font_path = APPPATH.'fonts/'.$font_face;

		if ($use_font == TRUE)
		{
			if ( ! file_exists($font_path))
			{
				$use_font = FALSE;
			}		
		}
				
		if ($use_font == FALSE OR ! function_exists('imagettftext'))
		{
			$font_size = 5;
			ImageString($im, $font_size, $x_axis, $img_height/3.8, $word, $text_color);
		}
		else
		{
			imagettftext($im, $font_size, $angle, $x_axis, $img_height/1.5, $text_color, $font_path, $word);
		}

		// Create the border
		imagerectangle($im, 0, 0, $img_width-1, $img_height-1, $border_color);		

		// Generate the image		
		$img_name = $now.'.jpg';

		ImageJPEG($im, $img_path.$img_name);
		
		$img = "<img src=\"$img_url$img_name\" width=\"$img_width\" height=\"$img_height\" style=\"border:0;\" alt=\" \" />";
		
		ImageDestroy($im);
	
		// Re-enable DB caching
		if ($db_reset == TRUE)
		{
			$this->EE->db->cache_on();			
		}
		
		return $img;
	}
	
	// --------------------------------------------------------------------

	/**
	 * SQL "AND" or "OR" string for conditional tag parameters
	 *
	 * This function lets us build a specific type of query
	 * needed when tags have conditional parameters:
	 *
	 * {exp:some_tag  param="value1|value2|value3"}
	 *
	 * Or the parameter can contain "not":
	 *
	 * {exp:some_tag  param="not value1|value2|value3"}
	 *
	 * This function explodes the pipes and constructs a series of AND
	 * conditions or OR conditions
	 *
	 * We should probably put this in the DB class but it's not
	 * something that is typically used
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	function sql_andor_string($str, $field, $prefix = '', $null=FALSE)
	{
		if ($str == "" OR $field == "")
		{
			return '';
		}
		
		$str = trim($str);	
		$sql = '';
		$not = '';
		
		if ($prefix != '')
		{
			$prefix .= '.';
		}
		
		if (strpos($str, '|') !== FALSE)
		{
			$parts = preg_split('/\|/', $str, -1, PREG_SPLIT_NO_EMPTY);
			$parts = array_map('trim', array_map(array($this->EE->db, 'escape_str'), $parts));
			
			if (count($parts) > 0)
			{
				if (strncasecmp($parts[0], 'not ', 4) == 0)
				{
					$parts[0] = substr($parts[0], 4);
					$not = 'NOT';
				}
				
				if ($null === TRUE)
				{
					$sql .= "AND ({$prefix}{$field} {$not} IN ('".implode("','", $parts)."') OR {$prefix}{$field} IS NULL)";
				}
				else
				{
					$sql .= "AND {$prefix}{$field} {$not} IN ('".implode("','", $parts)."')";
				}
			}
		}
		else
		{	
			if (strncasecmp($str, 'not ', 4) == 0)
			{
				$str = trim(substr($str, 3));
				$not = '!';
			}
			
			if ($null === TRUE)
			{
				$sql .= "AND ({$prefix}{$field} {$not}= '".$this->EE->db->escape_str($str)."' OR {$prefix}{$field} IS NULL)";
			}
			else
			{
				$sql .= "AND {$prefix}{$field} {$not}= '".$this->EE->db->escape_str($str)."'";
			}
		}

		return $sql;		
	}
	
	// --------------------------------------------------------------------

	/**
	 * AR "AND" or "OR" string for conditional tag parameters
	 *
	 * This function lets us build a specific type of query
	 * needed when tags have conditional parameters:
	 *
	 * {exp:some_tag  param="value1|value2|value3"}
	 *
	 * Or the parameter can contain "not":
	 *
	 * {exp:some_tag  param="not value1|value2|value3"}
	 *
	 * This function explodes the pipes and builds an AR query.
	 *
	 * We should probably put this in the DB class but it's not
	 * something that is typically used
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	string
	 * @param	bool
	 */
	function ar_andor_string($str, $field, $prefix = '', $null=FALSE)
	{
		if ($str == "" OR $field == "")
		{
			return '';
		}
		
		$str = trim($str);	
		
		if ($prefix != '')
		{
			$prefix .= '.';
		}
		
		if (strpos($str, '|') !== FALSE)
		{
			$parts = preg_split('/\|/', $str, -1, PREG_SPLIT_NO_EMPTY);
			$parts = array_map('trim', array_map(array($this->EE->db, 'escape_str'), $parts));
						
			if (count($parts) > 0)
			{
				if ($null === TRUE)
				{
					// MySQL Only
					if (strncasecmp($parts[0], 'not ', 4) == 0)
					{
						$parts[0] = substr($parts[0], 4);
						$sql = "AND ({$prefix}{$field} NOT IN ('".implode("','", $parts)."') OR {$prefix}{$field} IS NULL)";
					}
					else
					{
						$sql = "AND ({$prefix}{$field} IN ('".implode("','", $parts)."') OR {$prefix}{$field} IS NULL)";
					}
					
					$this->EE->db->where($sql);
					// END MySQL Only
				}
				else
				{
					if (strncasecmp($parts[0], 'not ', 4) == 0)
					{
						$parts[0] = substr($parts[0], 4);
						$this->EE->db->where_not_in($prefix.$field, $parts);
					}
					else
					{
						$this->EE->db->where_in($prefix.$field, $parts);
					}
				}
			}
		}
		else
		{	
			if ($null === TRUE)
			{
				// MySQL Only
				if (strncasecmp($str, 'not ', 4) == 0)
				{
					$str = trim(substr($str, 3));
					$sql = "AND ({$prefix}{$field} != '".$this->EE->db->escape_str($str)."' OR {$prefix}{$field} IS NULL)";
				}
				else
				{
					$sql = "AND ({$prefix}{$field} = '".$this->EE->db->escape_str($str)."' OR {$prefix}{$field} IS NULL)";
				}
				
				$this->EE->db->where($sql);
				// END MySQL Only
			}
			else
			{
				if (strncasecmp($str, 'not ', 4) == 0)
				{
					$str = trim(substr($str, 3));

					$this->EE->db->where($prefix.$field.' !=', $str);
				}
				else
				{
					$this->EE->db->where($prefix.$field, $str);
				}
			}
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Assign Conditional Variables
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	array
	 */
	function assign_conditional_variables($str, $slash = '/', $LD = '{', $RD = '}')
	{		
		// The first half of this function simply gathers the openging "if" tags
		// and a numeric value that corresponds to the depth of nesting.
		// The second half parses out the chunks
		
		$conds 		= array();
		$var_cond	= array();
		
		$modified_str = $str; // Not an alias!
		
		// Find the conditionals.
		// Added a \s in there to make sure it does not match {if:elseif} or {if:else} would would give 
		// us a bad array and cause havoc.
		if ( ! preg_match_all("/".$LD."if(\s.*?)".$RD."/s", $modified_str, $eek))
		{
			return $var_cond;
		}
		
		$total_conditionals = count($eek[0]);
		
		// Mark all opening conditionals, sequentially.
		if (count($modified_str) > 0)
		{
			 for ($i = 0; $i < $total_conditionals; $i++)
			 {
				  // Embedded variable fix
				  if ($ld_location = strpos($eek[1][$i],$LD))
				  {
						if (preg_match_all("|".preg_quote($eek[0][$i])."(.*?)".$RD."|s", $modified_str, $fix_eek))
						{
							if (count($fix_eek) > 0)
							{
								$eek[0][$i] = $fix_eek[0][0];
								$eek[1][$i] .= $RD.$fix_eek[1][0];
							}
						}
				  }
			 
				  $modified_string_length = strlen($eek[1][$i]);
				  $replace_value[$i] = $LD.'if'.$i;
				  $p1 = strpos($modified_str,$eek[0][$i]);
				  $p2 = $p1+strlen($replace_value[$i].$eek[1][$i])-strlen($i);
				  $p3 = strlen($modified_str);
				  $modified_str = substr($modified_str,0,$p1).$replace_value[$i].$eek[1][$i].substr($modified_str,$p2, $p3);
			 }
		}
		
		// Mark all closing conditions.
		$closed_position = array();
		for ($t=$i-1; $t >= 0; $t--)
		{
			 // Find the conditional's start
			 $coordinate = strpos($modified_str, $LD.'if'.$t);
			 
			 // Find the shortned string.
			 $shortened = substr($modified_str, $coordinate);
			 
			 // Find the conditional's end. Should be first closing tag.
			 $closed_position = strpos($shortened,$LD.$slash.'if'.$RD);
			 
			 // Location of the next closing tag in main content var
			 $p1 = $coordinate + $closed_position;
			 $p2 = $p1 + strlen($LD.$slash.'if'.$t.$RD) - 1;
			 
			 $modified_str = substr($modified_str,0,$p1).$LD.$slash.'if'.$t.$RD.substr($modified_str,$p2);
		}
		
		// Create Rick's array
		for ($i = 0; $i < $total_conditionals; $i++)
		{
			$p1 = strpos($modified_str, $LD.'if'.$i.' ');
			$p2 = strpos($modified_str, $LD.$slash.'if'.$i.$RD);
			$length = $p2-$p1;
			$text_range = substr($modified_str,$p1,$length);
			
			// We use \d here because we want to look for one of the 'marked' conditionals, but 
			// not an Advanced Conditional, which would have a colon
			if (preg_match_all("/".$LD."if(\d.*?)".$RD."/", $text_range, $depth_check))
			{
				// Depth is minus one, since it counts itself
				$conds[] = array($LD.'if'.$eek[1][$i].$RD, count($depth_check[0]));	
			}
		}

		// Create detailed conditional array
		$float = $str;
		$CE = $LD.$slash.'if'.$RD;
		$offset = strlen($CE);
		$start = 1;
		$duplicates = array();
				
		foreach ($conds as $key => $val)
		{	
			if ($val[1] > $start) $start = $val[1];
			
			$open_tag = strpos($float, $val[0]);
						
			$float = substr($float, $open_tag);
			
			$temp = $float;
			$len  = 0;
			$duplicates = array();
			
			$i = 1;
		
			while (FALSE !== ($in_point = strpos($temp, $CE)))
			{		
				$temp = substr($temp, $in_point + $offset);
				
				$len += $in_point + $offset;
							
				if ($i === $val[1])
				{					
					$tag = str_replace($LD, '', $val[0]);
					$tag = str_replace($RD, '', $tag);
					
					$outer = substr($float, 0, $len);
					
					if (isset($duplicates[$val[1]]) && in_array($outer, $duplicates[$val[1]]))
					{
						break;
					}
					
					$duplicates[$val[1]][] = $outer;
				
					$inner = substr($outer, strlen($val[0]), -$offset);
					
					$tag = str_replace("|", "\|", $tag);
					
					$tagb = preg_replace("/^if/", "", $tag);

					$field = ( ! preg_match("#(\S+?)\s*(\!=|==|<|>|<=|>=|<>)#s", $tag, $match)) ? trim($tagb) : $match[1];  
					
					// Array prototype:
					// offset 0: the full opening tag sans delimiters:  if extended
					// offset 1: the complete conditional chunk
					// offset 2: the inner conditional chunk
					// offset 3: the field name
				
					$var_cond[$val[1]][] = array($tag, $outer, $inner, $field);
					
					$float = substr($float, strlen($val[0]));
				
					break;
				}
			
				$i++;
			}
		}
		
		// Parse Order
		$final_conds = array();
		
		for ($i=$start; $i > 0; --$i)
		{
			if (isset($var_cond[$i])) $final_conds = array_merge($final_conds, $var_cond[$i]);
		}
		
		return $final_conds;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Assign Tag Variables
	 *
	 * This function extracts the variables contained within the current tag 
	 * being parsed and assigns them to one of three arrays.
	 *
	 * There are three types of variables:
	 *
	 * Simple variables: {some_variable}
	 *
	 * Paired variables: {variable} stuff... {/variable}
	 *
	 * Contidionals: {if something != 'val'} stuff... {if something}
	 *
	 * Each of the three variables is parsed slightly different and appears in its own array
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	array
	 */			
	function assign_variables($str = '', $slash = '/')
	{
		$return['var_single']	= array();
		$return['var_pair']		= array();

		if ($str == '')
		{
			return $return;
		}	
			
		// No variables?  No reason to continue...
		if (strpos($str, '{') === FALSE OR ! preg_match_all("/".LD."(.+?)".RD."/", $str, $matches))
		{
			return $return;
		}
		
		$temp_close = array();
		$temp_misc  = array();
		$slash_length = strlen($slash);
		
		foreach($matches[1] as $key => $val)
		{
			if (strncmp($val, 'if ', 3) !== 0 && 
				strncmp($val, 'if:', 3) !== 0 &&
				substr($val, 0, $slash_length+2) != $slash."if")
			{
				if (strpos($val, '{') !== FALSE)
				{
					if (preg_match("/(.+?)".LD."(.*)/s", $val, $matches2))
					{
						$temp_misc[$key] = $matches2[2];
					}
				}
				elseif (strncmp($val, $slash, $slash_length) === 0)
				{
					$temp_close[$key] = str_replace($slash, '', $val);
				}
				else
				{
					$temp_misc[$key] = $val;
				}
			}
			elseif (strpos($val, '{') !== FALSE) // Variable in conditional.  ::sigh::
			{
				$full_conditional = substr($this->full_tag($matches[0][$key], $str), 1, -1);
				
				// We only need the first match here, all others will get caught by our
				// previous code as they won't start with if.
				
				if (preg_match("/".LD."(.*?)".RD."/s", $full_conditional, $cond_vars))
				{
					$temp_misc[$key] = $cond_vars[1];
				}
			}
		}
		
		// $temp_misc contains all (opening) tags
		// $temp_close contains all closing tags
		
		// In 1.x we assumed that a closing tag meant that the variable was
		// a tag pair.  We now have variables that output as pairs and single tags
		// so we need to properly match the pairs.
		
		// In order to find proper pairs, we need to find equivalent opening and
		// closing tags that are closest together (no nesting).
		// The easiest way to go about this is to find all opening tags up to a
		// closing tag - and then just take the last one.
		
		$temp_pair = array();
		$temp_single = array();

		$open_stack = array();

		foreach($temp_misc as $key => $item)
		{
			foreach($temp_close as $idx => $row)
			{
				// Find the closest (potential) closing tag following it
				if (($idx > $key) && substr($item, 0, strlen($row)) == $row)
				{
					// There could be another opening tag between these
					// so we create a stack of opening tag values
					$open_stack[$idx][] = $key;
					continue;
				}
			}
		}
		
		// Pop the last item off each stack of opening tags - these are pairs
		foreach($open_stack as $potential_openings)
		{
			$open_tag_key = array_pop($potential_openings);
			
			if (isset($temp_misc[$open_tag_key]))
			{
				$temp_pair[] = $temp_misc[$open_tag_key];
				unset($temp_misc[$open_tag_key]);
			}
		}

		// The rest of them are single tags
		$temp_single = array_values($temp_misc);

		// Weed out the duplicatess
		$temp_single	= array_unique($temp_single);
		$temp_pair		= array_unique($temp_pair);

		// Assign Single Variables
		$var_single = array();
						
		foreach($temp_single as $val)
		{  
			// simple conditionals
			if (stristr($val, '\|') && substr($val, 0, 6) != 'switch' && substr($val, 0, 11) != 'multi_field')
			{
				$var_single[$val] = $this->fetch_simple_conditions($val);
			}
			
			// date variables
			elseif (strpos($val, 'format') !== FALSE && preg_match("/.+?\s+?format/", $val))
			{
				$var_single[$val] = $this->fetch_date_variables($val);  
			}
			else  // single variables
			{
				$var_single[$val] = $val;
			}
		}

		// Assign Variable Pairs		
		$var_pair = array();
			
		foreach($temp_pair as $val)
		{
			$var_pair[$val] = $this->assign_parameters($val);		
		}
		
		$return['var_single']	= $var_single;
		$return['var_pair']		= $var_pair;
		
		return $return;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Find the Full Opening Tag
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function full_tag($str, $chunk='', $open='', $close='')
	{	
		if ($chunk == '') $chunk = (isset($this->EE->TMPL) && is_object($this->EE->TMPL)) ? $this->EE->TMPL->fl_tmpl : '';
		if ($open == '')  $open  = LD;
		if ($close == '') $close = RD;

		// Warning: preg_match() Compilation failed: regular expression is too large at offset #
		// This error will occur if someone tries to stick over 30k-ish strings as tag parameters that also happen to include curley brackets.
		// Instead of preventing the error, we let it take place, so the user will hopefully visit the forums seeking assistance	
		if ( ! preg_match("/".preg_quote($str, '/')."(.*?)".$close."/s", $chunk, $matches))
		{
			return $str;
		}
		
		if (isset($matches[1]) && $matches[1] != '' && stristr($matches[1], $open) !== false)
		{
			$matches[0] = $this->full_tag($matches[0], $chunk, $open, $close);
		}
		
		return $matches[0];
	}
	
	// --------------------------------------------------------------------

	/**
	 * Fetch simple conditionals
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function fetch_simple_conditions($str)
	{
		if ($str == '')
		{
			return;			
		}
		
		$str = str_replace(' ', '', trim($str, '|'));		
		
		return explode('|', $str);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Fetch date variables
	 *
	 *  This function looks for a variable that has this prototype:
	 * 
	 * {date format="%Y %m %d"}
	 *
	 * If found, returns only the datecodes: %Y %m %d
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function fetch_date_variables($datestr)
	{
		if ($datestr == '')
			return;
		
		if ( ! preg_match("/format\s*=\s*[\'|\"](.*?)[\'|\"]/s", $datestr, $match))
				return FALSE;
		
		return $match[1];
	}

	// --------------------------------------------------------------------

	/**
	 * Return parameters as an array
	 *
	 * Creates an associative array from a string
	 * of parameters: sort="asc" limit="2" etc.
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */	
	function assign_parameters($str)
	{
		if ($str == "")
			return FALSE;
						
		// \047 - Single quote octal
		// \042 - Double quote octal
		
		// I don't know for sure, but I suspect using octals is more reliable than ASCII.
		// I ran into a situation where a quote wasn't being matched until I switched to octal.
		// I have no idea why, so just to be safe I used them here. - Rick
		
		// matches[0] => attribute and value
		// matches[1] => attribute name
		// matches[2] => single or double quote
		// matches[3] => attribute value
		preg_match_all("/(\S+?)\s*=\s*(\042|\047)([^\\2]*?)\\2/is",  $str, $matches, PREG_SET_ORDER);

		if (count($matches) > 0)
		{
			$result = array();
		
			foreach($matches as $match)
			{
				$result[$match[1]] = (trim($match[3]) == '') ? $match[3] : trim($match[3]);
			}

			return $result;
		}

		return FALSE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Prep conditional
	 *
	 * This function lets us do a little prepping before
	 * running any conditionals through eval()
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function prep_conditional($cond = '')
	{
		$cond = preg_replace("/^if/", "", $cond);
		
		if (preg_match("/(\S+)\s*(\!=|==|<=|>=|<>|<|>)\s*(.+)/", $cond, $match))
		{
			$cond = trim($match[1]).' '.trim($match[2]).' '.trim($match[3]);
		}
			
		$rcond	= substr($cond, strpos($cond, ' '));
		$cond	= str_replace($rcond, $rcond, $cond);
			
		// Since we allow the following shorthand condition: {if username}
		// but it's not legal PHP, we'll correct it by adding:  != ''
		
		if ( ! preg_match("/(\!=|==|<|>|<=|>=|<>)/", $cond))
		{
			$cond .= ' != "" ';
		}				
	
		return trim($cond);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Reverse Key Sort
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function reverse_key_sort($a, $b) {return strlen($b) > strlen($a);}

	// --------------------------------------------------------------------

	/**
	 * Prep conditionals
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	array
	 */
	function prep_conditionals($str, $vars, $safety='n', $prefix='')
	{				
		if (count($vars) == 0) return $str;

		$switch  = array();
		$protect = array();
		$prep_id = $this->random('alpha', 3);
		$embedded_tags = (stristr($str, LD.'exp:')) ? TRUE : FALSE;
		
		$valid = array('!=','==','<=','>=','<','>','<>',
						'AND', 'XOR', 'OR','&&','||',
						')','(',
						'TRUE', 'FALSE');
		
		$str = str_replace(LD.'if:else'.RD, 'c831adif9wel5ed9e', $str);
		
		// The ((else)*if) is actually faster than (elseif|if) in PHP 5.0.4, 
		// but only by a half a thousandth of a second.  However, why not be
		// as efficient as possible?  It also gives me a chance to catch some
		// user error mistakes.

		if (preg_match_all("/".preg_quote(LD)."((if:else)*if)\s+(.*?)".preg_quote(RD)."/", $str, $matches))
		{
			// PROTECT QUOTED TEXT
			//  That which is in quotes should be protected and ignored as it will screw
			//  up the parsing if the variable is found within a string
			
			if (preg_match_all('/([\"\'])([^\\1]*?)\\1/s', implode(' ', $matches[3]), $quote_matches))
			{
				foreach($quote_matches[0] as $quote_match)
				{
					$md5_key = (string) hexdec($prep_id.md5($quote_match));
					$protect[$quote_match] = $md5_key;
					$switch[$md5_key] = $quote_match;
				}
				
				$matches[3] = str_replace(array_keys($protect), array_values($protect), $matches[3]);
				
				// Remove quoted values altogether to find variables...
				$matches['t'] = str_replace($valid, ' ', str_replace(array_values($protect), '', $matches[3]));
			}
			else
			{
				$matches['t'] = str_replace($valid, ' ', $matches[3]);
			}
			
			// FIND WHAT WE NEED, NOTHING MORE!
			// On reedmaniac.com with no caching this code below knocked off, 
			// on average, about .07 seconds on a .34 page load. Not too shabby.
			// Sadly, its influence is far less on a cached page.  Ah well...			
			$data		= array();

			foreach($matches['t'] as $cond)
			{
				if (trim($cond) == '') continue;
				
				$x = preg_split("/\s+/", trim($cond)); $i=0;
				
				do
				{
					if (array_key_exists($x[$i], $vars))
					{
						$data[$x[$i]] = $vars[$x[$i]];
					}
					elseif($embedded_tags === TRUE && ! is_numeric($x[$i]))
					{
						$data[$x[$i]] = $x[$i];
					}
					elseif(strncmp($x[$i], 'embed:', 6) == 0)
					{
						$data[$x[$i]] = '';
					}
					
					if ($i > 500) break; ++$i;
				}	
				while(isset($x[$i]));
			}

			// This should prevent, for example, the variable 'comment' from 
			// overwriting the variable 'comments'.  
			
			uksort($data, array($this, 'reverse_key_sort'));

			if ($safety == 'y')
			{
				// Make sure we have the same amount of opening conditional tags 
				// as closing conditional tags.
				$tstr = preg_replace("/<script.*?".">.*?<\/script>/is", '', $str);
				
				$opening = substr_count($tstr, LD.'if') - substr_count($tstr, LD.'if:elseif');
				$closing = substr_count($tstr, LD.'/if'.RD);
				
				if ($opening > $closing)
				{
					$str .= str_repeat(LD.'/if'.RD, $opening-$closing);
				}
			}
		
			// Prep the data array to remove characters we do not want
			// And also just add the quotes around the value for good measure.
			while (list($key) = each($data))
			{
				if ( is_array($data[$key])) continue;
			
				// TRUE AND FALSE values are for short hand conditionals,
				// like {if logged_in} and so we have no need to remove
				// unwanted characters and we do not quote it.
				
				if ($data[$key] != 'TRUE' && $data[$key] != 'FALSE' && ($key != $data[$key] OR $embedded_tags !== TRUE))
				{
					if (stristr($data[$key], '<script'))
					{
						$data[$key] = preg_replace("/<script.*?".">.*?<\/script>/is", '', $data[$key]); // <? Fixes BBEdit display bug
					}
					
					$data[$key] = '"'.
								  str_replace(array("'", '"', '(', ')', '$', '{', '}', "\n", "\r", '\\'), 
											  array('&#39;', '&#34;', '&#40;', '&#41;', '&#36;', '', '', '', '', '&#92;'), 
											  (strlen($data[$key]) > 100) ? substr(htmlspecialchars($data[$key]), 0, 100) : $data[$key]
											  ).
								  '"';
				}
				
				$md5_key = (string) hexdec($prep_id.md5($key));
				$protect[$key] = $md5_key;
				$switch[$md5_key] = $data[$key];
				
				if ($prefix != '')
				{
					$md5_key = (string) hexdec($prep_id.md5($prefix.$key));
					$protect[$prefix.$key] = $md5_key;
					$switch[$md5_key] = $data[$key];
				}
			}
			
			$matches[3] = str_replace(array_keys($protect), array_values($protect), $matches[3]);
			
			if ($safety == 'y')
			{
				$matches['s'] = str_replace($protect, '^', $matches[3]);
				$matches['s'] = preg_replace('/"(.*?)"/s', '^', $matches['s']);
				$matches['s'] = preg_replace("/'(.*?)'/s", '^', $matches['s']);
				$matches['s'] = str_replace($valid, '  ', $matches['s']);
				$matches['s'] = preg_replace("/(^|\s+)[0-9]+(\s|$)/", ' ', $matches['s']); // Remove unquoted numbers
				$done = array();
			}
			
			for($i=0, $s = count($matches[0]); $i < $s; ++$i)
			{	
				if ($safety == 'y' && ! in_array($matches[0][$i], $done))
				{
					$done[] = $matches[0][$i];
					
					//  Make sure someone did put in an {if:else conditional}
					//  when they likely meant to have an {if:elseif conditional}					
					if ($matches[2][$i] == '' && 
						substr($matches[3][$i], 0, 5) == ':else' && 
						$matches[1][$i] == 'if')
					{
						$matches[3][$i] = substr($matches[3][$i], 5);
						$matches[2][$i] == 'elseif';
						
						trigger_error('Invalid Conditional, Assumed ElseIf : '.str_replace(' :else', 
																							':else', 
																							$matches[0][$i]), 
									  E_USER_WARNING);
					}
				
					//  If there are parentheses, then we
					//  try to make sure they match up correctly.					
					$left  = substr_count($matches[3][$i], '(');
					$right = substr_count($matches[3][$i], ')');
					
					if ($left > $right)
					{
						$matches[3][$i] .= str_repeat(')', $left-$right);
					}
					elseif ($right > $left)
					{
						$matches[3][$i] = str_repeat('(', $right-$left).$matches[3][$i];
					}
					
					// Check for unparsed variables
					if (trim($matches['s'][$i]) != '' && trim($matches['s'][$i]) != '^')
					{
						$x = preg_split("/\s+/", trim($matches['s'][$i]));
					
						for($j=0, $sj=count($x); $j < $sj; ++$j)
						{
							if ($x[$j] == '^') continue;
													
							if (substr($x[$j], 0, 1) != '^')
							{
								// We have an unset variable in the conditional.  
								// Set the unparsed variable to FALSE
								$matches[3][$i] = str_replace($x[$j], 'FALSE', $matches[3][$i]);
								
								if ($this->conditional_debug === TRUE)
								{
									trigger_error('Unset EE Conditional Variable ('.$x[$j].') : '.$matches[0][$i], 
												  E_USER_WARNING);
								}
							}
							else
							{	
								// There is a partial variable match being done
								// because they are doing something like segment_11
								// when there is no such variable but there is a segment_1
								// echo  $x[$j]."\n<br />\n";
								trigger_error('Invalid EE Conditional Variable: '.
											  $matches[0][$i], 
											  E_USER_WARNING);
								
								// Set entire conditional to FALSE since it fails
								$matches[3][$i] = 'FALSE';
							}
						}
					}
				}
				
				$matches[3][$i] = LD.$matches[1][$i].' '.trim($matches[3][$i]).RD;
			}
			
			$str = str_replace($matches[0], $matches[3], $str);

			$str = str_replace(array_keys($switch), array_values($switch), $str);
		}
		
		unset($data);
		unset($switch);
		unset($matches);
		unset($protect);
		
		$str = str_replace('c831adif9wel5ed9e',LD.'if:else'.RD, $str);
		
		return $str;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Fetch file upload paths
	 *
	 * @access	public
	 * @return	array
	 */
	function fetch_file_paths()
	{
		if (count($this->file_paths) > 0)
		{
			return $this->file_paths;
		}
		
		$this->EE->db->select('id, url');
		$query = $this->EE->db->get('upload_prefs');

		if ($query->num_rows() == 0)
		{
			return array();
		}
				
		foreach ($query->result_array() as $row)
		{			
			$this->file_paths[$row['id']] = $row['url'];
		}
		
		return $this->file_paths;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Clones an Object
	 * 
	 * This is required because of the way PHP 5 handles the passing of objects
	 * @php4
	 * 
	 * @deprecated as of EE 2.1.2
	 * @param	object
	 * @return	object
	 */
	function clone_object($object)
	{
		return clone $object;
	}
}
// END CLASS

/* End of file Functions.php */
/* Location: ./system/expressionengine/libraries/Functions.php */