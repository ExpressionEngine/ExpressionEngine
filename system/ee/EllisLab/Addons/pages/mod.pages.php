<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Pages Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Pages {

	/**
	 * Output Javascript
	 *
	 * Outputs Javascript files, triggered most commonly by an action request,
	 * but also available as a tag if desired.
	 *
	 * @access	public
	 * @return	string
	 */
	function load_site_pages()
	{
        $sites			= ee()->TMPL->fetch_param('site', '');
		$current_site	= ee()->config->item('site_short_name');

		// Always include the current site

		$site_names = explode('|', $sites);

		if ( ! in_array($current_site, $site_names))
		{
			$site_names[] = $current_site;
		}

		// Fetch all pages
		$sites = ee('Model')->get('Site')
			->fields('site_id', 'site_name', 'site_pages')
			->filter('siten_name', $site_names)
			->all();

		$new_pages = array();

		foreach($sites as $site)
		{
			if (is_array($site->site_pages))
			{
				$new_pages += $site->site_pages;
			}
		}

		// Update config

		ee()->config->set_item('site_pages', $new_pages);

		return '';
	}
}
// End Pages Class

/* End of file mod.pages.php */
/* Location: ./system/expressionengine/modules/pages/mod.pages.php */