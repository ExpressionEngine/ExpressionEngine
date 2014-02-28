<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license     http://ellislab.com/expressionengine/user-guide/license.html
 * @link        http://ellislab.com
 * @since       Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package     ExpressionEngine
 * @subpackage  Core
 * @category    Core
 * @author      EllisLab Dev Team
 * @link        http://ellislab.com
 */
class Updater {

	var $version_suffix = '';

    function Updater()
    {
        $this->EE =& get_instance();
    }

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

/* End of file ud_213.php */
/* Location: ./system/expressionengine/installer/updates/ud_213.php */