<?php

namespace EllisLab\ExpressionEngine\Controllers\Channel;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;

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
 * ExpressionEngine CP Channel Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Channel extends CP_Controller {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group(
			'can_access_admin',
			'can_admin_channels',
			'can_access_content_prefs'
		))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->lang->loadfile('channel');

		// Register our menu
		ee()->menu->register_left_nav(array(
			'channels' => array(
				'href' => cp_url('channel'),
				'button' => array(
					'href' => cp_url('channel/create'),
					'text' => 'new'
				)
			),
			'custom_fields' => array(
				'href' => cp_url('channel/field'),
				'button' => array(
					'href' => cp_url('channel/field/create'),
					'text' => 'new'
				)
			),
			array(
				'field_groups' => cp_url('channel/field-group')
			),
			'category_groups' => array(
				'href' => cp_url('channel/cat'),
				'button' => array(
					'href' => cp_url('channel/cat/create'),
					'text' => 'new'
				)
			),
			'status_groups' => array(
				'href' => cp_url('channel/status'),
				'button' => array(
					'href' => cp_url('channel/status/create'),
					'text' => 'new'
				)
			)
		));
	}

	/**
	 * Channel Manager
	 */
	public function index()
	{
		$table = CP\Table::create();
		$table->setColumns(
			array(
				'channel',
				'channel_short_name',
				'channel_manage' => array(
					'type'	=> CP\Table::COL_TOOLBAR
				),
				array(
					'type'	=> CP\Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_channels', 'create_channel', cp_url('channel/create'));

		$sort_map = array(
			'channel' => 'channel_title',
			'channel_short_name' => 'channel_name'
		);

		$channels = ee('Model')->get('Channel')
			->order($sort_map[$table->sort_col], $table->sort_dir)
			->limit(20)
			->offset(($table->config['page'] - 1) * 20)
			->all();

		$data = array();
		foreach ($channels as $channel)
		{
			$data[] = array(
				htmlentities($channel->channel_title, ENT_QUOTES),
				htmlentities($channel->channel_name, ENT_QUOTES),
				array('toolbar_items' => array(
					'edit' => array(
						'href' => cp_url('channel/edit/'.$channel->channel_id),
						'title' => lang('upload_btn_edit')
					),
					'settings' => array(
						'href' => cp_url('channel/edit/'.$channel->channel_id),
						'title' => lang('upload_btn_sync')
					)
				)),
				array(
					'name' => 'channels[]',
					'value' => $channel->channel_id,
					'data'	=> array(
						'confirm' => lang('channel') . ': <b>' . htmlentities($channel->channel_title, ENT_QUOTES) . '</b>'
					)
				)
			);
		}

		$table->setData($data);

		$base_url = new CP\URL('channel', ee()->session->session_id());
		$vars['table'] = $table->viewData($base_url);

		$pagination = new CP\Pagination(
			$vars['table']['limit'],
			$vars['table']['total_rows'],
			$vars['table']['page']
		);
		$vars['pagination'] = $pagination->cp_links($vars['table']['base_url']);

		ee()->view->cp_page_title = lang('manage_channels');
		ee()->view->header = array(
			'title' => lang('channel_manager'),
			'form_url' => cp_url('channel/search'),
			'toolbar_items' => array(
				'settings' => array(
					'href' => cp_url('settings/content-design'),
					'title' => lang('settings')
				)
			)
		);

		ee()->javascript->set_global('lang.remove_confirm', lang('channels') . ': <b>### ' . lang('channels') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/confirm_remove'),
		));

		ee()->cp->render('channel/index', $vars);
	}

	/**
	 * Remove channels handler
	 */
	public function remove()
	{
		$channel_ids = ee()->input->post('channels');

		if ( ! empty($channel_ids) && ee()->input->post('bulk_action') == 'remove')
		{
			// Filter out junk
			$channel_ids = array_filter($channel_ids, 'is_numeric');

			if ( ! empty($channel_ids))
			{
				// Do each channel individually because the old channel_model only
				// accepts one channel at a time to delete
				foreach ($channel_ids as $channel_id)
				{
					// Need to get arrays of entry IDs and author IDs to pass
					// to channel_model
					$entries = ee('Model')->get('ChannelEntry')
						->filter('channel_id', $channel_id)
						->all();

					ee()->load->model('channel_model');
					ee()->channel_model->delete_channel(
						$channel_id,
						$entries->pluck('entry_id'),
						$entries->pluck('author_id')
					);
				}

				ee()->view->set_message('success', lang('channels_removed'), sprintf(lang('channels_removed_desc'), count($channel_ids)), TRUE);
			}
		}
		else
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->functions->redirect(cp_url('channel', ee()->cp->get_url_state()));
	}

	/**
	 * New channel form
	 */
	public function create()
	{
		$this->form();
	}

	/**
	 * Channel creation/edit form
	 */
	private function form()
	{
		ee()->load->helper('snippets');
		ee()->cp->add_js_script('plugin', 'ee_url_title');
		ee()->javascript->output('
			$("input[name=channel_title]").bind("keyup keydown", function() {
				$(this).ee_url_title("input[name=channel_name]");
			});
		');

		ee()->view->cp_page_title = lang('create_new_channel');

		$vars = array();
		
		$channels = ee()->api
			->get('Channel')
			->filter('site_id', ee()->config->item('site_id'))
			->order('channel_title')
			->all();
		$vars['duplicate_channel_prefs_options'][''] = lang('channel_do_not_duplicate');
		if ( ! empty($channels))
		{
			foreach($channels as $channel)
			{
				$vars['duplicate_channel_prefs_options'][$channel->channel_id] = $channel->channel_title;
			}
		}

		$vars['cat_group_options'][''] = lang('none');
		$category_groups = ee('Model')->get('CategoryGroup')
			->filter('site_id', $this->config->item('site_id'))
			->order('group_name')
			->all();
		if ( ! empty($category_groups))
		{
			foreach ($category_groups as $group)
			{
				$vars['cat_group_options'][$group->group_id] = $group->group_name;
			}
		}

		$vars['status_group_options'][''] = lang('none');
		$status_groups = ee('Model')->get('StatusGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->order('group_name')
			->all();
		if ( ! empty($status_groups))
		{
			foreach ($status_groups as $group)
			{
				$vars['status_group_options'][$group->group_id] = $group->group_name;
			}
		}

		$vars['field_group_options'][''] = lang('none');
		$field_groups = ee('Model')->get('ChannelFieldGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->order('group_name')
			->all();
		if ( ! empty($field_groups))
		{
			foreach ($field_groups as $group)
			{
				$vars['field_group_options'][$group->group_id] = $group->group_name;
			}
		}

		ee()->view->header = array(
			'title' => lang('channel_manager'),
			'form_url' => cp_url('channel/search'),
			'toolbar_items' => array(
				'settings' => array(
					'href' => cp_url('settings/content-design'),
					'title' => lang('settings')
				)
			)
		);

		ee()->view->cp_page_title = lang('create_channel');
		ee()->cp->set_breadcrumb(cp_url('channel'), lang('channels'));

		ee()->cp->render('channel/edit', $vars);
	}
}
// EOF