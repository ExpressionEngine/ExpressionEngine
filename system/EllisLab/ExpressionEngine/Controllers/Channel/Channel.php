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
					'href' => cp_url('channel/new-channel'),
					'text' => 'new'
				)
			),
			'custom_fields' => array(
				'href' => cp_url('channel/field'),
				'button' => array(
					'href' => cp_url('channel/field/new-field'),
					'text' => 'new'
				)
			),
			array(
				'field_groups' => cp_url('channel/field-group')
			),
			'category_groups' => array(
				'href' => cp_url('channel/cat'),
				'button' => array(
					'href' => cp_url('channel/cat/new-cat'),
					'text' => 'new'
				)
			),
			'status_groups' => array(
				'href' => cp_url('channel/status'),
				'button' => array(
					'href' => cp_url('channel/status/new-status'),
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
		$table->setNoResultsText('no_channels', 'create_channel', cp_url('channel/new-channel'));

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
}
// EOF