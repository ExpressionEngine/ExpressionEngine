<?php

namespace EllisLab\ExpressionEngine\Controllers\Channels\Fields;

use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Controllers\Channels\AbstractChannels as AbstractChannelsController;

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
 * ExpressionEngine CP Channel\Fields\Groups Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Groups extends AbstractChannelsController {

	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group(
			'can_access_admin',
			'can_admin_channels',
			'can_access_content_prefs'
		))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->lang->loadfile('admin');
		ee()->lang->loadfile('admin_content');
	}

	public function groups()
	{
		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
			ee()->functions->redirect(ee('CP/URL', 'channels/groups/groups'));
		}

		$groups = ee('Model')->get('ChannelFieldGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		$vars = array(
			'create_url' => ee('CP/URL', 'channels/groups/create')
		);

		$table = ee('CP/Table');
		$table->setColumns(
			array(
				'group_name',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=>
						Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_group_groups', 'create_new', $vars['create_url']);

		$data = array();

		$group_id = ee()->session->flashdata('group_id');

		foreach ($groups as $group)
		{
			$column = array(
				$group->group_name,
				array('toolbar_items' => array(
					'edit' => array(
						'href' => cp_url('channels/groups/edit/' . $group->group_id),
						'title' => lang('edit')
					)
				)),
				array(
					'name' => 'selection[]',
					'value' => $group->group_id,
					'data' => array(
						'confirm' => lang('group') . ': <b>' . htmlentities($group->group_name, ENT_QUOTES) . '</b>'
					)
				)
			);

			$attrs = array();

			if ($group_id && $group->group_id == $group_id)
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs'		=> $attrs,
				'columns'	=> $column
			);
		}

		$table->setData($data);

		$vars['table'] = $table->viewData(ee('CP/URL', 'channels/groups'));

		$pagination = new Pagination(
			$vars['table']['limit'],
			$vars['table']['total_rows'],
			$vars['table']['page']
		);
		$vars['pagination'] = $pagination->cp_links($vars['table']['base_url']);

		ee()->javascript->set_global('lang.remove_confirm', lang('group') . ': <b>### ' . lang('groups') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/v3/confirm_remove',
			),
		));

		ee()->view->cp_page_title = lang('field_groups');
		ee()->view->cp_page_title_desc = lang('field_groups_desc');

		ee()->cp->render('channels/groups/index', $vars);
	}

}