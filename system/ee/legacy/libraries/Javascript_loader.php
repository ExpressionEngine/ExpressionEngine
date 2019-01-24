<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Core Javascript Loader
 */
class Javascript_loader {

	/**
	 * Constructor
	 */
	public function __construct()
	{
		define('PATH_JAVASCRIPT', PATH_THEMES_GLOBAL_ASSET.'javascript/'.PATH_JS.'/');
	}

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
		ee()->output->enable_profiler(FALSE);

		$contents	= '';
		$types		= array(
			'ui'		=> PATH_JAVASCRIPT.'jquery/ui/jquery.ui.',
			'plugin'	=> PATH_JAVASCRIPT.'jquery/plugins/',
			'file'		=> PATH_JAVASCRIPT,
			'package'	=> PATH_THIRD,
			'fp_module'	=> PATH_ADDONS
		);

		$mock_name = '';

		foreach($types as $type => $path)
		{
			$mock_name .= ee()->input->get_post($type);
			$files = explode(',', ee()->input->get_post($type));

			foreach($files as $file)
			{
				if ($type == 'package' OR $type == 'fp_module')
				{
					if (strpos($file, ':') !== FALSE)
					{
						list($package, $file) = explode(':', $file);
					}
					else
					{
						$package = $file;
					}

					$file = $package.'/javascript/'.$file;
				}
				elseif ($type == 'file')
				{
					$parts = explode('/', $file);
					$file = array();

					foreach ($parts as $part)
					{
						if ($part != '..')
						{
							$file[] = ee()->security->sanitize_filename($part);
						}
					}

					$file = implode('/', $file);
				}
				else
				{
					$file = ee()->security->sanitize_filename($file);
				}

				$file = $path.$file.'.js';

				if (file_exists($file))
				{
					$contents .= file_get_contents($file)."\n\n";
				}
			}
		}

		$modified = ee()->input->get_post('v');
		$this->set_headers($mock_name, $modified);

		ee()->output->set_header('Content-Length: '.strlen($contents));
		ee()->output->set_output($contents);
	}

	/**
	 * Set Headers
	 *
	 * @access	private
     * @param	string
	 * @return	string
	 */
    function set_headers($file, $mtime = FALSE)
    {
		ee()->output->out_type = 'cp_asset';
		ee()->output->set_header("Content-Type: text/javascript");

		if (ee()->config->item('send_headers') != 'y')
		{
			// All we need is content type - we're done
			return;
		}

		$max_age		= 5184000;
		$modified		= ($mtime !== FALSE) ? $mtime : @filemtime($file);
		$modified_since	= ee()->input->server('HTTP_IF_MODIFIED_SINCE');

		// Remove anything after the semicolon

		if ($pos = strrpos($modified_since, ';') !== FALSE)
		{
			$modified_since = substr($modified_since, 0, $pos);
		}

		// If the file is in the client cache, we'll
		// send a 304 and be done with it.

		if ($modified_since && (strtotime($modified_since) == $modified))
		{
			ee()->output->set_status_header(304);
			exit;
		}

		// Send a custom ETag to maintain a useful cache in
		// load-balanced environments

        ee()->output->set_header("ETag: ".md5($modified.$file));

		// All times GMT
		$modified = gmdate('D, d M Y H:i:s', $modified).' GMT';
		$expires = gmdate('D, d M Y H:i:s', time() + $max_age).' GMT';

		ee()->output->set_status_header(200);
		ee()->output->set_header("Cache-Control: max-age={$max_age}, must-revalidate");
		ee()->output->set_header('Vary: Accept-Encoding');
		ee()->output->set_header('Last-Modified: '.$modified);
		ee()->output->set_header('Expires: '.$expires);
	}
}

// EOF
