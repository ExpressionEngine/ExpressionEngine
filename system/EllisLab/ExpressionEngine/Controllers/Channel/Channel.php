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

		if ( ! ee()->cp->allowed_group(
			'can_access_admin',
			'can_admin_channels',
			'can_access_content_prefs'
		))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->lang->loadfile('channel');
		ee()->load->library('form_validation');

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
			->filter('site_id', ee()->config->item('site_id'))
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
						'href' => cp_url('channel/settings/'.$channel->channel_id),
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
	 * New channel form
	 */
	public function edit($channel_id)
	{
		$this->form($channel_id);
	}

	/**
	 * Channel creation/edit form
	 *
	 * @param	int	$channel_id	ID of Channel to edit
	 */
	private function form($channel_id = NULL)
	{
		if (is_null($channel_id))
		{
			// Only auto-complete channel short name for new channels
			ee()->load->helper('snippets');
			ee()->cp->add_js_script('plugin', 'ee_url_title');
			ee()->javascript->output('
				$("input[name=channel_title]").bind("keyup keydown", function() {
					$(this).ee_url_title("input[name=channel_name]");
				});
			');

			ee()->view->cp_page_title = lang('create_new_channel');
			ee()->view->form_url = cp_url('channel/create');
			$channel = ee('Model')->make('Channel');
		}
		else
		{
			$channel = ee('Model')->get('Channel')->filter('channel_id', (int) $channel_id)->first();
			
			if ( ! $channel)
			{
				show_error(lang('unauthorized_access'));
			}

			ee()->view->cp_page_title = lang('edit_channel');
			ee()->view->form_url = cp_url('channel/edit/'.$channel_id);
		}

		ee()->view->channel = $channel;
		$vars = array();
		
		$channels = ee('Model')->get('Channel')
			->filter('site_id', ee()->config->item('site_id'))
			->order('channel_title')
			->all();
		$vars['duplicate_channel_prefs_options'][''] = lang('channel_do_not_duplicate');
		if ( ! empty($channels))
		{
			foreach($channels as $dupe_channel)
			{
				$vars['duplicate_channel_prefs_options'][$dupe_channel->channel_id] = $dupe_channel->channel_title;
			}
		}

		$category_groups = ee('Model')->get('CategoryGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->order('group_name')
			->all();
		if ( ! empty($category_groups))
		{
			foreach ($category_groups as $group)
			{
				$vars['cat_group_options'][$group->group_id] = $group->group_name;
			}
		}
		
		// Populate selected categories based on POST or database
		if ( ! empty($_POST) && ! isset($_POST['cat_group']))
		{
			$vars['selected_cats'] = array();
		}
		elseif (isset($_POST['cat_group']))
		{
			$vars['selected_cats'] = $_POST['cat_group'];
		}
		else
		{
			$vars['selected_cats'] = explode('|', $channel->cat_group);
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

		ee()->form_validation->set_rules(array(
			array(
				'field' => 'channel_title',
				'label' => 'lang:channel_title',
				'rules' => 'required|strip_tags|trim|valid_xss_check'
			),
			array(
				'field' => 'channel_name',
				'label' => 'lang:channel_short_name',
				'rules' => 'required|strip_tags|callback__valid_channel_name['.$channel_id.']'
			),
			array(
				'field' => 'duplicate_channel_prefs',
				'label' => 'lang:channel_duplicate',
				'rules' => 'enum[' . implode(array_keys($vars['duplicate_channel_prefs_options']), ',') . ']'
			),
			array(
				'field' => 'status_group',
				'label' => 'lang:status_groups',
				'rules' => 'enum[' . implode(array_keys($vars['status_group_options']), ',') . ']'
			),
			array(
				'field' => 'field_group',
				'label' => 'lang:custom_field_group',
				'rules' => 'enum[' . implode(array_keys($vars['field_group_options']), ',') . ']'
			),
			array(
				'field' => 'cat_group',
				'label' => 'lang:category_groups',
				'rules' => 'enum[' . implode(array_keys($vars['cat_group_options']), ',') . ']'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$channel_id = $this->save_channel($channel_id);

			ee()->view->set_message('success', lang('directory_saved'), lang('directory_saved_desc'), TRUE);

			ee('Alert')->makeInline('channel-form')
				->asSuccess()
				->withTitle(lang('channel_saved'))
				->addToBody(lang('channel_saved_desc'))
				->defer();

			ee()->functions->redirect(cp_url('channel/edit/' . $channel_id));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('channel-form')
				->asIssue()
				->withTitle(lang('channel_not_saved'))
				->addToBody(lang('channel_not_saved_desc'));
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

		ee()->view->cp_page_title = is_null($channel_id) ? lang('create_channel') : lang('edit_channel');
		ee()->view->edit = ! is_null($channel_id);
		ee()->cp->set_breadcrumb(cp_url('channel'), lang('channels'));

		ee()->cp->render('channel/edit', $vars);
	}

	/**
	 * Custom validator for channel short name
	 */
	function _valid_channel_name($str, $channel_id = NULL)
	{
		// Check short name characters
		if (preg_match('/[^a-z0-9\-\_]/i', $str))
		{
			ee()->form_validation->set_message('_valid_channel_name', lang('invalid_short_name'));
			return FALSE;
		}

		$channel = ee('Model')->get('Channel')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('channel_name', $str);

		if ( ! empty($channel_id))
		{
			$channel->filter('channel_id', '!=', $channel_id);
		}

		if ($channel->all()->count() > 0)
		{
			ee()->form_validation->set_message('_valid_channel_name', lang('taken_channel_name'));
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Channel preference submission handler, copied from old
	 * admin_content controller
	 *
	 * This function receives the submitted channel preferences
	 * and stores them in the database.
	 */
	private function save_channel($channel_id = NULL)
	{
		// Load the layout Library & update the layouts
		ee()->load->library('layout');

		$dupe_id = ee()->input->get_post('duplicate_channel_prefs');
		unset($_POST['duplicate_channel_prefs']);

		if (isset($_POST['cat_group']) && is_array($_POST['cat_group']))
		{
			$_POST['cat_group'] = implode('|', $_POST['cat_group']);
		}
		else
		{
			$_POST['cat_group'] = '';
		}

		$channel = ee('Model')->make('Channel', $_POST);
		$channel->channel_id = $channel_id;
		
		// Make sure these are the correct NULL value if they are not set.
		$channel->status_group = ($channel->status_group !== FALSE
			&& $channel->status_group != '')
			? $channel->status_group : NULL;
		$channel->field_group = ($channel->field_group !== FALSE &&
			$channel->field_group != '')
			? $channel->field_group : NULL;

		// Create Channel
		if (empty($channel_id))
		{
			$channel->default_entry_title = '';
			$channel->url_title_prefix = '';
			$channel->channel_url = ee()->functions->fetch_site_index();
			$channel->channel_lang = ee()->config->item('xml_lang');
			$channel->site_id = ee()->config->item('site_id');
			
			// Assign field group if there is only one
			if ($dupe_id != ''
				&& ( $channel->field_group === NULL || ! is_numeric($channel->field_group)))
			{
				$field_groups = ee('Model')->get('ChannelFieldGroup')
					->filter('site_id', $channel->site_id)
					->all();

				if (count($field_groups) === 1)
				{
					$channel->field_group = $field_groups[0]->group_id;
				}
			}

			// duplicating preferences?
			if ($dupe_id !== FALSE AND is_numeric($dupe_id))
			{
				$dupe_channel = ee()->api
					->get('Channel')
					->filter('channel_id', $dupe_id)
					->first();
				$channel->duplicatePreferences($dupe_channel);
			}

			$channel->save();

			if ($dupe_id !== FALSE AND is_numeric($dupe_id))
			{
				// Duplicate layouts
				ee()->layout->duplicate_layout($dupe_id, $channel->channel_id);
			}

			// If they made the channel?  Give access to that channel to the member group?
			// If member group has ability to create the channel, they should be
			// able to access it as well
			if (ee()->session->userdata('group_id') != 1)
			{
				$data = array(
					'group_id'		=> ee()->session->userdata('group_id'),
					'channel_id'	=> $channel->channel_id
				);

				ee()->db->insert('channel_member_groups', $data);
			}

			$success_msg = lang('channel_created');

			ee()->logger->log_action($success_msg.NBS.NBS.$_POST['channel_title']);
		}
		else
		{
			if (isset($_POST['clear_versioning_data']))
			{
				ee()->db->delete('entry_versioning', array('channel_id' => $_POST['channel_id']));

				unset($_POST['clear_versioning_data']);
			}

			// Only one possible is revisions- enabled or disabled.
			// We treat as installed/not and delete the whole tab.
			ee()->layout->sync_layout($_POST, $channel_id);

			$_POST['channel_id'] = $channel_id;
			$channel->save();
		}

		return $channel->channel_id;
	}
}
// EOF