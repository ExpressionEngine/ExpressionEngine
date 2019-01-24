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
 * Update
 */
class Updater {

	var $version_suffix = '';

    function do_update()
    {
		// update docs location
		if (ee()->config->item('doc_url') == 'http://expressionengine.com/public_beta/docs/')
		{
			ee()->config->update_site_prefs(array('doc_url' => 'https://docs.expressionengine.com/latest'), 1);
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

// EOF
