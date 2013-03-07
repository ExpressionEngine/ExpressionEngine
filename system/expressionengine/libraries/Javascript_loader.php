<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.5
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Javascript Loader Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Assets
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Javascript_loader {
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$dir = ($this->config->item('use_compressed_js') == 'n') ? 'src' : 'compressed';
		define('PATH_JAVASCRIPT', PATH_THEMES.'javascript/'.$dir.'/');
	}

	// --------------------------------------------------------------------

	/**
	 * Javascript Combo Loader 
	 *
	 * Combo load multiple javascript files to reduce HTTP requests
	 * BASE.AMP.'C=javascript&M=combo&ui=ui,packages&file=another&plugin=plugins&package=third,party,packages'
	 * 
	 * @access public
	 * @return string
	 */
	public function combo_load()
	{
		$this->output->enable_profiler(FALSE);

		$contents	= '';
		$types		= array(
			'ui'		=> PATH_JAVASCRIPT.'jquery/ui/jquery.ui.',
			'plugin'	=> PATH_JAVASCRIPT.'jquery/plugins/',
			'file'		=> PATH_JAVASCRIPT,
			'package'	=> PATH_THIRD,
			'fp_module'	=> PATH_MOD
		);
		
		$mock_name = '';

		foreach($types as $type => $path)
		{
			$mock_name .= $this->input->get_post($type);
			$files = explode(',', $this->input->get_post($type));
			
			foreach($files as $file)
			{
				if ($type == 'package' OR $type == 'fp_module')
				{
					$file = $file.'/javascript/'.$file;
				}
				elseif ($type == 'file')
				{
					$parts = explode('/', $file);
					$file = array();
					
					foreach ($parts as $part)
					{
						if ($part != '..')
						{
							$file[] = $this->security->sanitize_filename($part);
						}
					}
								
					$file = implode('/', $file);
				}
				else
				{
					$file = $this->security->sanitize_filename($file);
				}
				
				$file = $path.$file.'.js';

				if (file_exists($file))
				{
					$contents .= file_get_contents($file)."\n\n";
				}
			}
		}

		$modified = $this->input->get_post('v');
		$this->set_headers($mock_name, $modified);
		
		$this->output->set_header('Content-Length: '.strlen($contents));
		$this->output->set_output($contents);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Set Headers
	 *
	 * @access	private
     * @param	string 
	 * @return	string
	 */
    function set_headers($file, $mtime = FALSE)
    {
		$this->output->out_type = 'cp_asset';
		$this->output->set_header("Content-Type: text/javascript");

		if ($this->config->item('send_headers') != 'y')
		{
			// All we need is content type - we're done
			return;
		}

		$max_age		= 5184000;
		$modified		= ($mtime !== FALSE) ? $mtime : @filemtime($file);
		$modified_since	= $this->input->server('HTTP_IF_MODIFIED_SINCE');

		// Remove anything after the semicolon

		if ($pos = strrpos($modified_since, ';') !== FALSE)
		{
			$modified_since = substr($modified_since, 0, $pos);
		}
		
		// If the file is in the client cache, we'll
		// send a 304 and be done with it.

		if ($modified_since && (strtotime($modified_since) == $modified))
		{
			$this->output->set_status_header(304);
			exit;
		}
		
		// Send a custom ETag to maintain a useful cache in
		// load-balanced environments
		
        $this->output->set_header("ETag: ".md5($modified.$file));

		// All times GMT
		$modified = gmdate('D, d M Y H:i:s', $modified).' GMT';
		$expires = gmdate('D, d M Y H:i:s', time() + $max_age).' GMT';

		$this->output->set_status_header(200);
		$this->output->set_header("Cache-Control: max-age={$max_age}, must-revalidate");
		$this->output->set_header('Vary: Accept-Encoding');
		$this->output->set_header('Last-Modified: '.$modified);
		$this->output->set_header('Expires: '.$expires);        
	}

	// --------------------------------------------------------------------
	
	/**
	 * Avoid get_instance()
	 */
	public function __get($key)
	{
		$EE =& get_instance();
		return $EE->$key;
	}
}


/* End of file  */
/* Location:  */