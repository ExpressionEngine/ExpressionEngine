<?php

namespace EllisLab\ExpressionEngine\Controllers\Channels\Fields;

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
 * ExpressionEngine CP Channel\Fields\Fields Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Fields extends AbstractChannelsController {

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

	public function fields()
	{
		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
			ee()->functions->redirect(ee('CP/URL', 'channels/fields'));
		}

		$fields = ee('Model')->get('ChannelField')
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		$vars = array(
			'create_url' => ee('CP/URL', 'channels/fields/create')
		);

		$table = ee('CP/Table');
		$table->setColumns(
			array(
				'id',
				'name',
				'short_name',
				'type',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=>
						Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_fields', 'create_new', $vars['create_url']);

		$data = array();

		$field_id = ee()->session->flashdata('field_id');

		foreach ($fields as $field)
		{
			$column = array(
				$field->field_id,
				htmlentities($field->field_label, ENT_QUOTES),
				'<var>{' . $field->field_name . '}</var>',
				htmlentities($field->field_type, ENT_QUOTES),
				array('toolbar_items' => array(
					'edit' => array(
						'href' => cp_url('channels/fields/edit/' . $field->field_id),
						'title' => lang('edit')
					)
				)),
				array(
					'name' => 'selection[]',
					'value' => $field->field_id,
					'data' => array(
						'confirm' => lang('field') . ': <b>' . htmlentities($field->field_label, ENT_QUOTES) . '</b>'
					)
				)
			);

			$attrs = array();

			if ($field_id && $field->field_id == $field_id)
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs'		=> $attrs,
				'columns'	=> $column
			);
		}

		$table->setData($data);

		$vars['table'] = $table->viewData(ee('CP/URL', 'channels/fields'));

		$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($vars['table']['base_url']);

		ee()->javascript->set_global('lang.remove_confirm', lang('field') . ': <b>### ' . lang('fields') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/v3/confirm_remove',
			),
		));

		ee()->view->cp_page_title = lang('custom_fields');

		ee()->cp->render('channels/fields/index', $vars);
	}

}