<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Controller\Fields;

use CP_Controller;

/**
 * Abstract Categories
 */
abstract class AbstractFields extends CP_Controller {

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group_any(
			'can_create_channel_fields',
			'can_edit_channel_fields',
			'can_delete_channel_fields'
		))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->lang->loadfile('admin');
		ee()->lang->loadfile('admin_content');
	}

	/**
	 * AJAX endpoint for Relationship field settings author list
	 *
	 * @return	array
	 */
	public function relationshipMemberFilter()
	{
		ee()->load->add_package_path(PATH_ADDONS.'relationship');

		ee()->load->library('Relationships_ft_cp');
		$util = ee()->relationships_ft_cp;

		$author_list = $util->all_authors(ee('Request')->get('search'));

		ee()->load->remove_package_path(PATH_ADDONS.'relationship');

		return ee('View/Helpers')->normalizedChoices($author_list, TRUE);
	}
}

// EOF
