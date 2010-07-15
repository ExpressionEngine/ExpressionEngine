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
 * ExpressionEngine ExpressionEngine Info Accessory
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Accessories
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Expressionengine_info_acc {

	var $name			= 'ExpressionEngine Info';
	var $id				= 'expressionengine_info';
	var $version		= '1.0';
	var $description	= 'Links and Information about ExpressionEngine';
	var $sections		= array();

	/**
	 * Constructor
	 */
	function Expressionengine_info_acc()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Set Sections
	 *
	 * Set content for the accessory
	 *
	 * @access	public
	 * @return	void
	 */
	 function set_sections()
	{
		$this->EE->lang->loadfile('expressionengine_info');
		
		// localize Accessory display name
		$this->name = $this->EE->lang->line('expressionengine_info');
		
		// set the sections
		$this->sections[$this->EE->lang->line('resources')] = $this->_fetch_resources();
		$this->sections[$this->EE->lang->line('version_and_build')] = $this->_fetch_version();
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Resources
	 *
	 * @access	public
	 * @return	string
	 */
	function _fetch_resources()
	{
		return '
		<ul>
			<li><a href="'.$this->EE->cp->masked_url('http://expressionengine.com').'" title="ExpressionEngine.com">ExpressionEngine.com</a></li>
			<li><a href="'.$this->EE->cp->masked_url('http://expressionengine.com/user_guide').'">'.$this->EE->lang->line('documentation').'</a></li>
			<li><a href="'.$this->EE->cp->masked_url('http://expressionengine.com/forums').'">'.$this->EE->lang->line('support_forums').'</a></li>
			<li><a href="'.$this->EE->cp->masked_url('https://secure.expressionengine.com/download.php').'">'.$this->EE->lang->line('downloads').'</a></li>
			<li><a href="'.$this->EE->cp->masked_url('http://expressionengine.com/support').'">'.$this->EE->lang->line('support_resources').'</a></li>
		</ul>
		';
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Version
	 *
	 * @access	public
	 * @return	string
	 */
	function _fetch_version()
	{
		// check cache first
		$cache_expire = 60 * 60 * 24;	// only do this once per day
		
		$this->EE->load->helper('file');	
		$contents = read_file(APPPATH.'cache/expressionengine_info/version');

		if ($contents !== FALSE)
		{
			$details = unserialize($contents);
			if (isset($details['timestamp'])) 
			{
				if (($details['timestamp'] + $cache_expire) > $this->EE->localize->now)
				{
					if (isset($details['error']))
					{
						return $details['error'];
					}
					else
					{
						return str_replace(array('%v', '%b'), array($details['version'], $details['build']), $this->EE->lang->line('version_info'));
					}
				}
			}
			else
			{
				return $details['error'];
			}
		}
		
		// no cache, so get current downloadable version
		$version = $this->_fsockopen_process('http://expressionengine.com/eeversion2.txt');
		
		$version = 'v'.trim(str_replace('Version:', '', $version));
		
		$build = $this->_fsockopen_process('https://secure.expressionengine.com/extra/ee_current_build/v2');

		$details = array(
							'timestamp'	=> $this->EE->localize->now,
							'version'	=> $version,
							'build'		=> $build
						);

		$this->_write_cache($details);
		return str_replace(array('%v', '%b'), array($details['version'], $details['build']), $this->EE->lang->line('version_info'));
	}

	// --------------------------------------------------------------------
	
	/**
	 * fsockopen process
	 *
	 * Someday I'll write a proper Connection library
	 *
	 * @access	public
	 * @param	string	url
	 * @return	string
	 */
	function _fsockopen_process($url)
	{
		$parts	= parse_url($url);
		$host	= $parts['host'];
		$path	= ( ! isset($parts['path'])) ? '/' : $parts['path'];
		$port	= ($parts['scheme'] == "https") ? '443' : '80';
		$ssl	= ($parts['scheme'] == "https") ? 'ssl://' : '';

		$ret = '';

		$fp = @fsockopen($ssl.$host, $port, $error_num, $error_str, 4); 

		if (is_resource($fp))
		{
			fwrite($fp,"GET {$path} HTTP/1.0\r\n" );
			fwrite($fp,"Host: {$host}\r\n" );
			fwrite($fp,"User-Agent: EE/EllisLab PHP/\r\n\r\n");
			
			// There is evidently a bug in PHP < 5.2 with SSL and fsockopen() when the $length is
			// greater than the remaining data - so distasteful as it is, we'll suppress errors
			while($datum = @fread($fp, 4096))
			{
				$ret .= $datum;
			}

			@fclose($fp); 
		}
		else
		{
			$this->_write_cache(array('error' => $this->EE->lang->line('error_getting_version')));
			return $this->EE->lang->line('error_getting_version');
		}

		// and get rid of headers
		if ($pos = strpos($ret, "\r\n\r\n"))
		{
			$ret = substr($ret, $pos);
		}
		
		return trim($ret);
	}

	// --------------------------------------------------------------------

	/**
	 * Write Cache
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function _write_cache($details)
	{
		if ( ! is_dir(APPPATH.'cache/expressionengine_info'))
		{
			mkdir(APPPATH.'cache/expressionengine_info', DIR_WRITE_MODE);
			@chmod(APPPATH.'cache/expressionengine_info', DIR_WRITE_MODE);
		}
		
		if (write_file(APPPATH.'cache/expressionengine_info/version', serialize($details)))
		{
			@chmod(APPPATH.'cache/expressionengine_info/version', FILE_WRITE_MODE);			
		}
	}

	// --------------------------------------------------------------------
	
}
// END CLASS

/* End of file acc.expressionengine_info.php */
/* Location: ./system/expressionengine/accessories/acc.expressionengine_info.php */