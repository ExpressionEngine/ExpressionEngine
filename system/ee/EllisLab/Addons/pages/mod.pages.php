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
 * Pages Module
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
			->filter('site_name', $site_names)
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

// EOF
