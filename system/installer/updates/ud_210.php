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
		// update docs location
		if (ee()->config->item('doc_url') == 'http://expressionengine.com/public_beta/docs/')
		{
			ee()->config->update_site_prefs(array('doc_url' => 'http://ellislab.com/expressionengine/user-guide/'), 1);
		}

		ee()->smartforge->add_column(
			'member_groups',
			array(
				'can_access_fieldtypes' => array(
					'type'			=> 'char',
					'constraint'	=> 1,
					'default'		=> 'n',
					'null'			=> FALSE
				)
			),
			'can_access_files'
		);

		ee()->db->set('can_access_fieldtypes', 'y');
		ee()->db->where('group_id', '1');
		ee()->db->update('member_groups');

		ee()->db->set('class', 'Channel');
		ee()->db->where('class', 'channel');
		ee()->db->update('actions');

		return TRUE;
	}
}
/* END CLASS */

/* End of file ud_210.php */
/* Location: ./system/expressionengine/installer/updates/ud_210.php */