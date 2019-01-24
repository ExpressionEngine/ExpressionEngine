<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Library\CP;

/**
 * CP MiniGrid Input Table
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
			$this->javascript->output('
				$(".fields-keyvalue").miniGrid('.json_encode($settings).');

				FieldManager.on("fieldModalDisplay", function(modal) {
					$(".fields-keyvalue").miniGrid('.json_encode($settings).');
				});
			');
		}
	}
}

// EOF
