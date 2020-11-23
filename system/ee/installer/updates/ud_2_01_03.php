<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_2_1_3;

/**
 * Update
 */
class Updater {

	var $version_suffix = '';

    function do_update()
    {
		ee()->load->library('layout');

		$layouts = ee()->db->get('layout_publish');

		if ($layouts->num_rows() === 0)
		{
			return TRUE;
		}

		$layouts = $layouts->result_array();

		foreach ($layouts as &$layout)
		{
			$old_layout = unserialize($layout['field_layout']);

			foreach ($old_layout as $tab => &$fields)
			{
				$field_keys = array_keys($fields);

				foreach ($field_keys as &$key)
				{
					if ($key == 'channel')
					{
						$key = 'new_channel';
					}
				}

				$fields = array_combine($field_keys, $fields);
			}

			$layout['field_layout'] = serialize($old_layout);

		}

		ee()->db->update_batch('layout_publish', $layouts, 'layout_id');

		return TRUE;
	}
}
/* END CLASS */

// EOF
