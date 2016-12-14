<?php

namespace EllisLab\ExpressionEngine\Library\CP;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Grid Input Class
 *
 * @package		ExpressionEngine
 * @subpackage	Library
 * @category	CP
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class MiniGridInput extends GridInput {

	/**
	 * Loads necessary JS and CSS, override's parent
	 */
	public function loadAssets()
	{
		static $assets_loaded;

		if ( ! $assets_loaded)
		{
			$this->cp->add_js_script('ui', 'sortable');
			$this->cp->add_js_script('file', 'cp/sort_helper');
			$this->cp->add_js_script('plugin', 'ee_table_reorder');
			$this->cp->add_js_script('file', 'cp/grid');

			$assets_loaded = TRUE;
		}

		$settings = array(
			'grid_min_rows' => $this->config['grid_min_rows'],
			'grid_max_rows' => $this->config['grid_max_rows']
		);

		$name = $this->config['field_name'];

		if (REQ == 'CP')
		{
			// getElementById instead of $('#...') for field names that have
			// brackets in them
			$this->javascript->output('$(".keyvalue").miniGrid('.json_encode($settings).');');
		}
	}
}

// EOF
