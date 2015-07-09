<?php

namespace EllisLab\ExpressionEngine\Controllers\Channels;

use CP_Controller;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Abstract Channel Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
abstract class AbstractChannels extends CP_Controller {

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		// Allow AJAX requests for category editing
		if (AJAX_REQUEST && in_array(ee()->router->method, array('createCat', 'editCat')))
		{
			if ( ! $this->cp->allowed_group('can_edit_categories'))
			{
				show_error(lang('unauthorized_access'));
			}
		}
		elseif ( ! ee()->cp->allowed_group('can_access_admin', 'can_admin_channels', 'can_access_content_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->lang->loadfile('channel');
		ee()->load->library('form_validation');

		// Register our menu
		ee()->menu->register_left_nav(array(
			'channels' => array(
				'href' => ee('CP/URL', 'channels'),
				'button' => array(
					'href' => ee('CP/URL', 'channels/create'),
					'text' => 'new'
				)
			),
			'custom_fields' => array(
				'href' => ee('CP/URL', 'channels/fields'),
				'button' => array(
					'href' => ee('CP/URL', 'channels/fields/create'),
					'text' => 'new'
				)
			),
			array(
				'field_groups' => ee('CP/URL', 'channels/fields/groups')
			),
			'category_groups' => array(
				'href' => ee('CP/URL', 'channels/cat'),
				'button' => array(
					'href' => ee('CP/URL', 'channels/cat/create'),
					'text' => 'new'
				)
			),
			'status_groups' => array(
				'href' => ee('CP/URL', 'channels/status'),
				'button' => array(
					'href' => ee('CP/URL', 'channels/status/create'),
					'text' => 'new'
				)
			)
		));

		// This header is section-wide
		ee()->view->header = array(
			'title' => lang('channel_manager'),
			'form_url' => ee('CP/URL', 'channels/search'),
			'toolbar_items' => array(
				'settings' => array(
					'href' => ee('CP/URL', 'settings/content-design'),
					'title' => lang('settings')
				)
			)
		);
	}

}
// EOF