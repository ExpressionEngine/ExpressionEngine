<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Core Functions Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Functions {

	public $seed               = FALSE; // Whether we've seeded our rand() function.  We only seed once per script execution
	public $cached_url         = array();
	public $cached_path        = array();
	public $cached_index       = array();
	public $cached_captcha     = '';
	public $template_map       = array();
	public $template_type      = '';
	public $action_ids         = array();
	public $file_paths         = array();
	public $conditional_debug  = FALSE;
	public $catfields          = array();

	/**
	 * Constructor
	 */
	public function __construct()
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
	public function fetch_site_index($add_slash = FALSE, $sess_id = TRUE)
	{
		if (isset($this->cached_index[$add_slash.$sess_id.$this->template_type]))
		{
			return $this->cached_index[$add_slash.$sess_id.$this->template_type];
		}

		$url = ee()->config->slash_item('site_url');

		$url .= ee()->config->item('site_index');

		if (ee()->config->item('force_query_string') == 'y')
		{
			$url .= '?';
		}

		if (ee()->config->item('website_session_type') != 'c' && is_object(ee()->session) && REQ != 'CP' && $sess_id == TRUE && $this->template_type == 'webpage')
		{
			$url .= (ee()->session->session_id('user')) ? "/S=".ee()->session->session_id('user')."/" : '';
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
	 * Create a URL for a Template Route
	 *
	 * The input to this function is parsed and added to the
	 * full site URL to create a full URL/URI
	 *
	 * @access	public
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	public function create_route($segment, $sess_id = TRUE)
	{
		if (is_array($segment))
		{
			$tag = trim($segment[0], "{}");
			$segment = $segment[1];
		}

		if (isset($this->cached_url[$segment]))
		{
			return $this->cached_url[$segment];
		}

		$full_segment = $segment;
		$parts = $this->assign_parameters($tag);

		$template = $parts['route'];
		$template = trim($template, '"\' ');
		list($group, $template) = explode('/', $template);

		if ( ! empty($group) && ! empty($template) && ! IS_CORE)
		{
			ee()->load->library('template_router');
			$route = ee()->template_router->fetch_route($group, $template);

			if (empty($route))
			{
				return "{route=$segment}";
			}
			else
			{
				unset($parts['route']);
				$segment = $route->build($parts);
			}
		}

		$base = $this->fetch_site_index(0, $sess_id).'/'.trim_slashes($segment);

		$out = reduce_double_slashes($base);

		$this->cached_url[$full_segment] = $out;

		return $out;
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
	public function create_url($segment, $sess_id = TRUE)
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
			$qs = (ee()->config->item('force_query_string') == 'y') ? '' : '?';
			$xid = bool_config_item('disable_csrf_protection') ? '' : AMP.'csrf_token='.CSRF_TOKEN;

			return $this->fetch_site_index(0, 0).$qs.'ACT='.$this->fetch_action_id('Member', 'member_logout').$xid;
		}

		// END Specials

		$base = $this->fetch_site_index(0, $sess_id).'/'.trim_slashes($segment);

		$out = reduce_double_slashes($base);

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
	public function create_page_url($base_url, $segment, $trailing_slash = FALSE)
	{
		if (ee()->config->item('force_query_string') == 'y')
		{
			if (strpos($base_url, ee()->config->item('index_page') . '/') !== FALSE)
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

       $out = reduce_double_slashes($base);

       return $out;
	}


	// --------------------------------------------------------------------

	/**
	 * Fetch site index with URI query string
	 *
	 * @access	public
	 * @return	string
	 */
	public function fetch_current_uri()
	{
		return rtrim(reduce_double_slashes($this->fetch_site_index(1).ee()->uri->uri_string), '/');
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
	public function prep_query_string($str)
	{
		if (stristr($str, '.php') && substr($str, -7) == '/index/')
		{
			$str = substr($str, 0, -6);
		}

		if (strpos($str, '?') === FALSE && ee()->config->item('force_query_string') == 'y')
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
	public function encode_ee_tags($str, $convert_curly = FALSE)
	{
		if ($str != '' && strpos($str, '{') !== FALSE)
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
				$str = str_replace(array('{if', '{/if'), array('&#123;if', '&#123;/if'), $str);
			}
		}

		return $str;
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
	public function extract_path($str)
	{
		if (preg_match("#=(.*)#", $str, $match))
		{
			$match[1] = trim($match[1], '}');

			if (isset($this->cached_path[$match[1]]))
			{
				return $this->cached_path[$match[1]];
			}

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
	public function var_swap($str, $data)
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
	public function redirect($location, $method = FALSE, $status_code=NULL)
	{
		// Remove hard line breaks and carriage returns
		$location = str_replace(array("\n", "\r"), '', $location);

		// Remove any and all line breaks
		while (stripos($location, '%0d') !== FALSE OR stripos($location, '%0a') !== FALSE)
		{
			$location = str_ireplace(array('%0d', '%0a'), '', $location);
		}

		$location = $this->insert_action_ids($location);
		$location = ee()->uri->reformat($location);

		if (count(ee()->session->flashdata))
		{
			// Ajax requests don't redirect - serve the flashdata

			if (ee()->input->is_ajax_request())
			{
				// We want the data that would be available for the next request
				ee()->session->_age_flashdata();

				die(json_encode(ee()->session->flashdata));
			}
		}

		if ($method === FALSE)
		{
			$method = ee()->config->item('redirect_method');
		}

		switch($method)
		{
			case 'refresh':
				$header = "Refresh: 0;url=$location";
				break;
			default:
				$header = "Location: $location";
				break;
		}

		if($status_code !== NULL && $status_code >= 300 && $status_code <= 308)
		{
			header($header, TRUE, $status_code);
		}
		else
		{
			header($header);
		}

		exit;
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
	public function random($type = 'encrypt', $len = 8)
	{
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
	public function form_declaration($data)
	{
		// Load the form helper
		ee()->load->helper('form');

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
			$data['hidden_fields']['site_id'] = ee()->config->item('site_id');
		}

		// -------------------------------------------
		// 'form_declaration_modify_data' hook.
		//  - Modify the $data parameters before they are processed
		//  - Added EE 1.4.0
		//
		if (ee()->extensions->active_hook('form_declaration_modify_data') === TRUE)
		{
			$data = ee()->extensions->call('form_declaration_modify_data', $data);
		}
		//
		// -------------------------------------------

		// -------------------------------------------
		// 'form_declaration_return' hook.
		//  - Take control of the form_declaration function
		//  - Added EE 1.4.0
		//
		if (ee()->extensions->active_hook('form_declaration_return') === TRUE)
		{
			$form = ee()->extensions->call('form_declaration_return', $data);
			if (ee()->extensions->end_script === TRUE) return $form;
		}
		//
		// -------------------------------------------


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
			unset($data['hidden_fields']['XID']);
			$data['hidden_fields']['csrf_token'] = '{csrf_token}'; // we use the tag instead of the constant to allow caching of the template
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
	public function form_backtrack($offset = '')
	{
		$ret = $this->fetch_site_index();

		if ($offset != '')
		{
			if (isset(ee()->session->tracker[$offset]))
			{
				if (ee()->session->tracker[$offset] != 'index')
				{
					return reduce_double_slashes($this->fetch_site_index().'/'.ee()->session->tracker[$offset]);
				}
			}
		}

		if (isset($_POST['RET']))
		{
			if (strncmp($_POST['RET'], '-', 1) == 0)
			{
				$return = str_replace("-", "", $_POST['RET']);

				if (isset(ee()->session->tracker[$return]))
				{
					if (ee()->session->tracker[$return] != 'index')
					{
						$ret = $this->fetch_site_index().'/'.ee()->session->tracker[$return];
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
			// their site using sessions only.  Normally the ee()->functions->fetch_site_index()
			// function adds the session ID automatically, except in cases when the
			// $_POST['RET'] variable is set. Since the login routine relies on the RET
			// info to know where to redirect back to we need to sandwich in the session ID.
			if (ee()->config->item('website_session_type') != 'c')
			{
				$id = ee()->session->session_id('user');

				if ($id != '' && ! stristr($ret, $id))
				{
					$url = ee()->config->slash_item('site_url');

					$url .= ee()->config->item('site_index');

					if (ee()->config->item('force_query_string') == 'y')
					{
						$url .= '?';
					}

					$sess_id = "/S=".$id."/";

					$ret = str_replace($url, $url.$sess_id, $ret);
				}
			}
		}

		return reduce_double_slashes($ret);
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
	public function evaluate($str)
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
	public function encode_email($str)
	{
		if (isset(ee()->session->cache['functions']['emails'][$str]))
		{
			return preg_replace("/(eeEncEmail_)\w+/", '\\1'.ee()->functions->random('alpha', 10), ee()->session->cache['functions']['emails'][$str]);
		}

		$email = (is_array($str)) ? trim($str[1]) : trim($str);

		$title = '';
		$email = str_replace(array('"', "'"), '', $email);

		if ($p = strpos($email, "title="))
		{
			$title = substr($email, $p + 6);
			$email = trim(substr($email, 0, $p));
		}

		ee()->load->library('typography');
		ee()->typography->initialize();

		$encoded = ee()->typography->encode_email($email, $title, TRUE);

		ee()->session->cache['functions']['emails'][$str] = $encoded;

		return $encoded;
	}

	// --------------------------------------------------------------------

	/**
	 * Delete spam prevention hashes
	 *
	 * @access	public
	 * @return	void
	 */
	public function clear_spam_hashes()
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('2.8');

		// if (ee()->config->item('secure_forms') == 'y')
		// {
		// 	ee()->security->garbage_collect_xids();
		// }
	}

	// --------------------------------------------------------------------

	/**
	 * Set Cookie
	 *
	 * @access	public
	 * @deprecated 2.8
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	public function set_cookie($name = '', $value = '', $expire = '')
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('2.8', 'EE_Input::set_cookie()');

		return ee()->input->set_cookie($name, $value, $expire);
	}

	// --------------------------------------------------------------------

	/**
	 * Character limiter
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function char_limiter($str, $num = 500)
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
	public function word_limiter($str, $num = 100)
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
	public function fetch_email_template($name)
	{
		$query = ee()->db->query("SELECT template_name, data_title, template_data, enable_template FROM exp_specialty_templates WHERE site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."' AND template_name = '".ee()->db->escape_str($name)."'");

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

		if (ee()->session->userdata['language'] != '')
		{
			$user_lang = ee()->session->userdata['language'];
		}
		else
		{
			if (ee()->input->cookie('language'))
			{
				$user_lang = ee()->input->cookie('language');
			}
			elseif (ee()->config->item('deft_lang') != '')
			{
				$user_lang = ee()->config->item('deft_lang');
			}
			else
			{
				$user_lang = 'english';
			}
		}

		$user_lang = ee()->security->sanitize_filename($user_lang);

		if ( function_exists($name))
		{
			$title = $name.'_title';

			return array('title' => $title(), 'data' => $name());
		}
		else
		{
			if ( ! @include(APPPATH.'language/'.$user_lang.'/email_data.php'))
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
	 * Create pull-down optios from dirctory map
	 *
	 * @access	public
	 * @param	array
	 * @param	string
	 * @return	string
	 */
	public function render_map_as_select_options($zarray, $array_name = '')
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
	public function language_pack_names($default)
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
	public function clear_caching($which, $sub_dir = '')
	{
		$options = array('page', 'db', 'tag', 'sql');

		if (in_array($which, $options))
		{
			ee()->cache->delete('/'.$which.'_cache/');
		}
		elseif ($which == 'all')
		{
			foreach ($options as $option)
			{
				ee()->cache->delete('/'.$option.'_cache/');
			}
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
	public function delete_directory($path, $del_root = FALSE)
	{
		$path = rtrim($path, '/');
		$path_delete = $path.'_delete';

		if ( ! is_dir($path))
		{
			return FALSE;
		}

		// Delete temporary directory if it happens to exist from a previous attempt
		if (is_dir($path_delete))
		{
			@exec("rm -r -f {$path_delete}");
		}

		// let's try this the sane way first
		@exec("mv {$path} {$path_delete}", $out, $ret);

		if (isset($ret) && $ret == 0)
		{
			if ($del_root === FALSE)
			{
				@mkdir($path, DIR_WRITE_MODE);

				if ($fp = @fopen($path.'/index.html', FOPEN_WRITE_CREATE_DESTRUCTIVE))
				{
					fclose($fp);
				}
			}

			@exec("rm -r -f {$path_delete}");
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
	public function fetch_assigned_channels($all_sites = FALSE)
	{
		$allowed_channels = array();

		if (REQ == 'CP' AND isset(ee()->session->userdata['assigned_channels']) && $all_sites === FALSE)
		{
			$allowed_channels = array_keys(ee()->session->userdata['assigned_channels']);
		}
		elseif (ee()->session->userdata['group_id'] == 1)
		{
			if ($all_sites === TRUE)
			{
				ee()->db->select('channel_id');
				$query = ee()->db->get('channels');
			}
			else
			{
				ee()->db->select('channel_id');
				ee()->db->where('site_id', ee()->config->item('site_id'));
				$query = ee()->db->get('channels');
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
				$result = ee()->db->query("SELECT exp_channel_member_groups.channel_id FROM exp_channel_member_groups
									  WHERE exp_channel_member_groups.group_id = '".ee()->db->escape_str(ee()->session->userdata['group_id'])."'");
			}
			else
			{
				$result = ee()->db->query("SELECT exp_channels.channel_id FROM exp_channels, exp_channel_member_groups
									  WHERE exp_channels.channel_id = exp_channel_member_groups.channel_id
									  AND exp_channels.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'
									  AND exp_channel_member_groups.group_id = '".ee()->db->escape_str(ee()->session->userdata['group_id'])."'");
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
	public function log_search_terms($terms = '', $type = 'site')
	{
		if ($terms == '' OR ee()->db->table_exists('exp_search_log') === FALSE)
			return;

		if (ee()->config->item('enable_search_log') == 'n')
			return;

		ee()->load->helper('xml');

		$search_log = array(
								'member_id'		=> ee()->session->userdata('member_id'),
								'screen_name'	=> ee()->session->userdata('screen_name'),
								'ip_address'	=> ee()->input->ip_address(),
								'search_date'	=> ee()->localize->now,
								'search_type'	=> $type,
								'search_terms'	=> xml_convert(ee()->functions->encode_ee_tags(ee()->security->xss_clean($terms), TRUE)),
								'site_id'		=> ee()->config->item('site_id')
							);

		ee()->db->query(ee()->db->insert_string('exp_search_log', $search_log));

		// Prune Database
		srand(time());
		if ((rand() % 100) < 5)
		{
			$max = ( ! is_numeric(ee()->config->item('max_logged_searches'))) ? 500 : ee()->config->item('max_logged_searches');

			$query = ee()->db->query("SELECT MAX(id) as search_id FROM exp_search_log WHERE site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'");

			$row = $query->row_array();

			if (isset($row['search_id'] ) && $row['search_id'] > $max)
			{
				ee()->db->query("DELETE FROM exp_search_log WHERE site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."' AND id < ".($row['search_id'] -$max)."");
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
	public function fetch_action_id($class, $method)
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
	public function insert_action_ids($str)
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
				$sql .= " (class= '".ee()->db->escape_str($key)."' AND method = '".ee()->db->escape_str($v)."') OR";
			}
		}

		$query = ee()->db->query(substr($sql, 0, -3));

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
	 * Get Categories for Channel Entry/Entries
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	array
	 */
	public function get_categories($cat_group, $entry_id)
	{
		// fetch the custom category fields
		$field_sqla = '';
		$field_sqlb = '';

		$query = ee()->db->query("SELECT field_id, field_name FROM exp_category_fields WHERE group_id IN ('".str_replace('|', "','", ee()->db->escape_str($cat_group))."')");

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
				WHERE		c.group_id	IN ('".str_replace('|', "','", ee()->db->escape_str($cat_group))."')
				AND			p.entry_id	= '".$entry_id."'
				AND			c.cat_id 	= p.cat_id
				ORDER BY	c.parent_id, c.cat_order";

		$sql = str_replace("\t", " ", $sql);
		$query = ee()->db->query($sql);

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
	public function process_subcategories($parent_id)
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
	public function add_form_security_hash($str)
	{
		// Add security hash. Need to replace the legacy XID one as well.
		$str = str_replace('{csrf_token}', CSRF_TOKEN, $str);
		$str = str_replace('{XID_HASH}', CSRF_TOKEN, $str);

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Generate CAPTCHA
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function create_captcha($old_word = '', $force_word = FALSE)
	{
		if (ee()->config->item('captcha_require_members') == 'n' && ee()->session->userdata['member_id'] != 0 && $force_word == FALSE)
		{
			return '';
		}

		// -------------------------------------------
		// 'create_captcha_start' hook.
		//  - Allows rewrite of how CAPTCHAs are created
		//
			if (ee()->extensions->active_hook('create_captcha_start') === TRUE)
			{
				$edata = ee()->extensions->call('create_captcha_start', $old_word);
				if (ee()->extensions->end_script === TRUE) return $edata;
			}
		// -------------------------------------------

		$img_path	= ee()->config->slash_item('captcha_path', 1);
		$img_url	= ee()->config->slash_item('captcha_url');
		$use_font	= (ee()->config->item('captcha_font') == 'y') ? TRUE : FALSE;

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

		if ( ! file_exists(APPPATH.'config/captcha.php'))
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
		if (ee()->db->cache_on == TRUE)
		{
			ee()->db->cache_off();
			$db_reset = TRUE;
		}

		// Remove old images - add a bit of randomness so we aren't doing this every page access

		list($usec, $sec) = explode(" ", microtime());
		$now = ((float)$usec + (float)$sec);

		if ((mt_rand() % 100) < ee()->session->gc_probability)
		{
			$old = time() - $expiration;
			ee()->db->query("DELETE FROM exp_captcha WHERE date < ".$old);

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
			require APPPATH.'config/captcha.php';
			$word = $words[array_rand($words)];

			if (ee()->config->item('captcha_rand') == 'y')
			{
				$word .= $this->random('nozero', 2);
			}

			ee()->db->query("INSERT INTO exp_captcha (date, ip_address, word) VALUES (UNIX_TIMESTAMP(), '".ee()->input->ip_address()."', '".ee()->db->escape_str($word)."')");
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
			ee()->db->cache_on();
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
	public function sql_andor_string($str, $field, $prefix = '', $null=FALSE)
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
			$parts = array_map('trim', array_map(array(ee()->db, 'escape_str'), $parts));

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
				$sql .= "AND ({$prefix}{$field} {$not}= '".ee()->db->escape_str($str)."' OR {$prefix}{$field} IS NULL)";
			}
			else
			{
				$sql .= "AND {$prefix}{$field} {$not}= '".ee()->db->escape_str($str)."'";
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
	public function ar_andor_string($str, $field, $prefix = '', $null=FALSE)
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
			$parts = array_map('trim', array_map(array(ee()->db, 'escape_str'), $parts));

			if (count($parts) > 0)
			{
				if ($null === TRUE)
				{
					// MySQL Only
					if (strncasecmp($parts[0], 'not ', 4) == 0)
					{
						$parts[0] = substr($parts[0], 4);
						$sql = "({$prefix}{$field} NOT IN ('".implode("','", $parts)."') OR {$prefix}{$field} IS NULL)";
					}
					else
					{
						$sql = "({$prefix}{$field} IN ('".implode("','", $parts)."') OR {$prefix}{$field} IS NULL)";
					}

					ee()->db->where($sql);
					// END MySQL Only
				}
				else
				{
					if (strncasecmp($parts[0], 'not ', 4) == 0)
					{
						$parts[0] = substr($parts[0], 4);
						ee()->db->where_not_in($prefix.$field, $parts);
					}
					else
					{
						ee()->db->where_in($prefix.$field, $parts);
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
					$sql = "({$prefix}{$field} != '".ee()->db->escape_str($str)."' OR {$prefix}{$field} IS NULL)";
				}
				else
				{
					$sql = "({$prefix}{$field} = '".ee()->db->escape_str($str)."' OR {$prefix}{$field} IS NULL)";
				}

				ee()->db->where($sql);
				// END MySQL Only
			}
			else
			{
				if (strncasecmp($str, 'not ', 4) == 0)
				{
					$str = trim(substr($str, 3));

					ee()->db->where($prefix.$field.' !=', $str);
				}
				else
				{
					ee()->db->where($prefix.$field, $str);
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
	public function assign_conditional_variables($str, $slash = '/', $LD = '{', $RD = '}')
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

					$field = ( ! preg_match("#(\S+?)\s*(\!=|==|<|>|<=|>=|<>|%)#s", $tag, $match)) ? trim($tagb) : $match[1];

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
	public function assign_variables($str = '', $slash = '/')
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

		foreach($temp_misc as $open_key => $open_tag)
		{

			if (preg_match("#(.+?)(\s+|=)(.+?)#", $open_tag, $matches))
			{
				$open_tag = $matches[1];
			}

			foreach($temp_close as $close_key => $close_tag)
			{

				// Find the closest (potential) closing tag following it
				if (($close_key > $open_key) && $open_tag == $close_tag)
				{
					// There could be another opening tag between these
					// so we create a stack of opening tag values
					$open_stack[$close_key][] = $open_key;
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
	public function full_tag($str, $chunk='', $open='', $close='')
	{
		if ($chunk == '') $chunk = (isset(ee()->TMPL) && is_object(ee()->TMPL)) ? ee()->TMPL->fl_tmpl : '';
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
	public function fetch_simple_conditions($str)
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
	public function fetch_date_variables($datestr)
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
	 * @param String $str String of parameters (e.g. sort="asc" limit="2")
	 * @param array $defaults Associative array of defaults with the name as the
	 *                        key and the value as the default value
	 * @return Mixed FALSE if there's no matches, otherwise the associative
	 *               array containing the parameters and their values
	 */
	public function assign_parameters($str, $defaults = array())
	{
		if ($str == "")
		{
			return FALSE;
		}

		// remove comments before assigning
		$str = preg_replace("/\{!--.*?--\}/s", '', $str);

		// \047 - Single quote octal
		// \042 - Double quote octal

		// I don't know for sure, but I suspect using octals is more reliable
		// than ASCII. I ran into a situation where a quote wasn't being matched
		// until I switched to octal. I have no idea why, so just to be safe I
		// used them here. - Rick

		// matches[0] => attribute and value
		// matches[1] => attribute name
		// matches[2] => single or double quote
		// matches[3] => attribute value

		$bs = '\\'; // single backslash
		preg_match_all("/(\S+?)\s*=\s*($bs$bs?)(\042|\047)([^\\3]*?)\\2\\3/is", $str, $matches, PREG_SET_ORDER);

		if (count($matches) > 0)
		{
			$result = array();

			foreach($matches as $match)
			{
				$result[$match[1]] = (trim($match[4]) == '') ? $match[4] : trim($match[4]);
			}

			foreach ($defaults as $name => $default_value)
			{
				if ( ! isset($result[$name])
					OR (is_numeric($default_value) && ! is_numeric($result[$name])))
				{
					$result[$name] = $default_value;
				}
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
	public function prep_conditional($cond = '')
	{
		$cond = preg_replace("/^if/", "", $cond);

		if (preg_match("/(\S+)\s*(\!=|==|<=|>=|<>|<|>|%)\s*(.+)/", $cond, $match))
		{
			$cond = trim($match[1]).' '.trim($match[2]).' '.trim($match[3]);
		}

		$rcond	= substr($cond, strpos($cond, ' '));
		$cond	= str_replace($rcond, $rcond, $cond);

		// Since we allow the following shorthand condition: {if username}
		// but it's not legal PHP, we'll correct it by adding:  != ''

		if ( ! preg_match("/(\!=|==|<|>|<=|>=|<>|%)/", $cond))
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
	public function reverse_key_sort($a, $b) {return strlen($b) > strlen($a);}

	// --------------------------------------------------------------------

	/**
	 * Prep conditionals
	 *
	 * @access	public
	 * @param	string $str		The template string containing conditionals
	 * @param	string $vars	The variables to look for in the conditionals
	 * @param	string $safety	If y, make sure conditionals are fully parseable
	 *							by replacing unknown variables with FALSE. This
	 *							defaults to n so that conditionals are slowly
	 *							filled and then turned into safely executable
	 *							ones with the safety on at the end.
	 * @param	string $prefix	Prefix for the variables in $vars.
	 * @return	string The new template to use instead of $str.
	 */
	public function prep_conditionals($str, $vars, $safety = 'n', $prefix = '')
	{
		if ( ! stristr($str, LD.'if'))
		{
			return $str;
		}

		if (isset(ee()->TMPL->embed_vars))
		{
			// If this is being called from a module tag, embedded variables
			// aren't going to be available yet.  So this is a quick workaround
			// to ensure advanced conditionals using embedded variables can do
			// their thing in mod tags.
			$vars = array_merge($vars, ee()->TMPL->embed_vars);
		}

		$bool_safety = ($safety == 'n') ? FALSE : TRUE;

		$runner = \EllisLab\ExpressionEngine\Library\Parser\ParserFactory::createConditionalRunner();

		if ($bool_safety === TRUE)
		{
			$runner->safetyOn();
		}

		if ($prefix)
		{
			$runner->setPrefix($prefix);
		}

		/* ---------------------------------
		/*	Hidden Configuration Variables
		/*  - protect_javascript => Prevents advanced conditional parser from processing anything in <script> tags
		/* ---------------------------------*/

		if (isset(ee()->TMPL) && ee()->TMPL->protect_javascript)
		{
			$runner->enableProtectJavascript();
		}

		try
		{
			return $runner->processConditionals($str, $vars);
		}
		catch (\EllisLab\ExpressionEngine\Library\Parser\Conditional\Exception\ConditionalException $e)
		{
			$thrower = str_replace(
				array('\\', 'Conditional', 'Exception'),
				'',
				strrchr(get_class($e), '\\')
			);

			if (ee()->config->item('debug') == 2
				OR (ee()->config->item('debug') == 1
					&& ee()->session->userdata('group_id') == 1))
			{
				$error = lang('error_invalid_conditional') . "\n\n";
				$error .= '<strong>' . $thrower . ' State:</strong> ' . $e->getMessage();
			}
			else
			{
				$error = lang('generic_fatal_error');
			}

			ee()->output->set_status_header(500);
			ee()->output->fatal_error(nl2br($error));

			exit;
		}

		return $prepped_string;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch file upload paths
	 *
	 * @access	public
	 * @return	array
	 */
	public function fetch_file_paths()
	{
		ee()->load->model('file_upload_preferences_model');
		$this->file_paths = ee()->file_upload_preferences_model->get_paths();
		return $this->file_paths;
	}

	// --------------------------------------------------------------------

	/**
	 * bookmarklet qstr decode
	 *
	 * @param 	string
	 */
	public function bm_qstr_decode($str)
	{
		$str = str_replace("%20",	" ",		$str);
		$str = str_replace("%uFFA5", "&#8226;",	$str);
		$str = str_replace("%uFFCA", " ",		$str);
		$str = str_replace("%uFFC1", "-",		$str);
		$str = str_replace("%uFFC9", "...",		$str);
		$str = str_replace("%uFFD0", "-",		$str);
		$str = str_replace("%uFFD1", "-",		$str);
		$str = str_replace("%uFFD2", "\"",		$str);
		$str = str_replace("%uFFD3", "\"",		$str);
		$str = str_replace("%uFFD4", "\'",		$str);
		$str = str_replace("%uFFD5", "\'",		$str);

		$str =	preg_replace("/\%u([0-9A-F]{4,4})/e","'&#'.base_convert('\\1',16,10).';'", $str);

		$str = $this->security->xss_clean(stripslashes(urldecode($str)));

		return $str;
	}

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file Functions.php */
/* Location: ./system/expressionengine/libraries/Functions.php */