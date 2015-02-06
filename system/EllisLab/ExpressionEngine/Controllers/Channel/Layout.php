<?php

namespace EllisLab\ExpressionEngine\Controllers\Channel;

use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Model\Content\Display\DefaultLayout;
use EllisLab\ExpressionEngine\Controllers\Channel\AbstractChannel as AbstractChannelController;

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
 * ExpressionEngine CP Channel\Layout Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Layout extends AbstractChannelController {

	public function __construct()
	{
		parent::__construct();
		ee()->lang->loadfile('content');
	}

	public function layout($channel_id)
	{
		$channel = ee('Model')->get('Channel', $channel_id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $channel)
		{
			show_error(lang('unauthorized_access'));
		}

		$vars['create_url'] = cp_url('channel/layout/create/' . $channel_id);

		$table = Table::create();
		$table->setColumns(
			array(
				'name',
				'member_group',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=>
						Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_layouts', 'create_new', $vars['create_url']);

		$data = array();

		$layout_id = ee()->session->flashdata('layout_id');

		foreach ($channel->getChannelLayouts() as $layout)
		{
			$column = array(
				$layout->layout_name,
				$layout->getMemberGroup()->group_title,
				array('toolbar_items' => array(
					'edit' => array(
						'href' => cp_url('channel/layout/edit/' . $layout->layout_id),
						'title' => lang('edit')
					)
				)),
				array(
					'name' => 'selection[]',
					'value' => $layout->layout_id,
					'data' => array(
						'confirm' => lang('layout') . ': <b>' . htmlentities('A Layout', ENT_QUOTES) . '</b>'
					)
				)
			);

			$attrs = array();

			if ($layout_id && $layout->layout_id == $layout_id)
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs'		=> $attrs,
				'columns'	=> $column
			);

		}

		$table->setData($data);

		$base_url = new URL('channel', ee()->session->session_id());
		$vars['table'] = $table->viewData($base_url);

		$pagination = new Pagination(
			$vars['table']['limit'],
			$vars['table']['total_rows'],
			$vars['table']['page']
		);
		$vars['pagination'] = $pagination->cp_links($vars['table']['base_url']);

		ee()->view->cp_page_title = sprintf(lang('channel_form_layouts'), $channel->channel_title);
		ee()->cp->set_breadcrumb(cp_url('channel'), lang('channels'));

		ee()->cp->render('channel/layout/index', $vars);
	}

	public function create($channel_id)
	{
		$channel = ee('Model')->get('Channel', $channel_id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $channel)
		{
			show_error(lang('unauthorized_access'));
		}

		$entry = ee('Model')->make('ChannelEntry')->setChannel($channel);

		$member_gropus = ee('Model')->get('MemberGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		$default_layout = new DefaultLayout();
		$channel_layout = ee('Model')->make('ChannelLayout');
		$field_layout = $default_layout->getLayout();

		foreach($channel->getCustomFields() as $custom_field)
		{
			$field_layout[0]['fields'][] = array(
				'field' => $entry->getCustomFieldPrefix() . $custom_field->field_id,
				'visible' => TRUE,
				'collapsed' => FALSE
			);
		}

		$channel_layout->field_layout = $field_layout;

		$vars = array(
			'channel' => $channel,
			'form_url' => cp_url('channel/layout/create/' . $channel_id),
			'layout' => $entry->getDisplay(),
			'channel_layout' => $channel_layout,
			'selected_member_groups' => array(),
			'member_groups' => $member_gropus,
		);

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'layout_name',
				'label' => 'lang:layout_name',
				'rules' => 'required'
			),
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			if (ee()->input->post('submit') == 'create')
			{
				// Crate the layout!
			}
			else
			{
				// Preview it...somehow
			}
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('layout-form')
				->asIssue()
				->withTitle(lang('create_layout_error'))
				->addToBody(lang('create_layout_error_desc'));
		}

		ee()->view->cp_breadcrumbs = array(
			cp_url('channel') => lang('channels'),
			cp_url('channel/layout/' . $channel_id) => lang('form_layouts')
		);

		ee()->view->cp_page_title = lang('create_form_layout');

		ee()->view->header = NULL;
		ee()->view->left_nav = NULL;

		ee()->javascript->set_global('publish_layout', $channel_layout->field_layout);
		ee()->cp->add_js_script('ui', 'sortable');
		ee()->cp->add_js_script('file', 'cp/channel/layout');

		ee()->cp->render('channel/layout/create', $vars);
	}

	public function edit($layout_id)
	{

	}

}
// EOF
