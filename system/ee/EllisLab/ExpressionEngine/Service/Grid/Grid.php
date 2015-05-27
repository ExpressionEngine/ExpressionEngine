<?php
namespace EllisLab\ExpressionEngine\Service\Grid;
use EllisLab\ExpressionEngine\Library\CP\GridInput;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Grid Service
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Grid {

	/**
	 * Creates a new GridInput object with the config protocol outlined
	 * in the GridInput class, but also loads the necessary front-end
	 * assets and performs JavaScript initialization for both control
	 * panel and front-end use
	 * 
	 * @param	array 	$config	See Table constructor for options
	 */
	public function make($config)
	{
		$grid = GridInput::create($config);

		$this->loadAssets($grid);

		return $grid;
	}

	/**
	 * Called from make(), 'oads the necessary front-end assets and
	 * performs JavaScript initialization for both control panel and
	 * front-end use
	 * 
	 * @param	array 	$config	See Table constructor for options
	 */
	private function loadAssets($grid)
	{
		static $assets_loaded;

		if ( ! $assets_loaded)
		{
			if (REQ == 'CP')
			{
				$css_link = ee()->view->head_link('css/v3/grid.css');
			}
			// Channel Form
			else
			{
				$css_link = '<link rel="stylesheet" href="'.ee()->config->slash_item('theme_folder_url').'cp_themes/default/css/v3/grid.css" type="text/css" media="screen" />'.PHP_EOL;
			}

			ee()->cp->add_to_head($css_link);

			ee()->cp->add_js_script('ui', 'sortable');
			ee()->cp->add_js_script('file', 'cp/sort_helper');
			ee()->cp->add_js_script('plugin', 'ee_table_reorder');
			ee()->cp->add_js_script('file', 'cp/grid');

			$assets_loaded = TRUE;
		}

		$settings = array(
			'grid_min_rows' => $grid->config['grid_min_rows'],
			'grid_max_rows' => $grid->config['grid_max_rows']
		);

		$name = $grid->config['field_name'];

		if (REQ == 'CP')
		{
			// Set settings as a global for easy reinstantiation of field
			// by third parties
			ee()->javascript->set_global('grid_field_settings.'.$name, $settings);

			// getElementById instead of $('#...') for field names that have
			// brackets in them
			ee()->javascript->output('EE.grid(document.getElementById("'.$name.'"));');
		}
		// Channel Form
		else
		{
			ee()->javascript->output('EE.grid(document.getElementById("'.$name.'"), '.json_encode($settings).');');
		}
	}
}
// EOF