<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\Table;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Moblog Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Moblog_mcp {

	var $channel_array		= array();
	var $status_array 		= array();
	var $field_array  		= array();
	var $author_array 		= array();
	var $image_dim_array	= array();
	var $upload_loc_array	= array();

	var $default_template 	= '';
	var $default_channel_cat	= '';


	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		$this->default_template = <<<EOT
{text}

{images}
<img src="{file}" width="{width}" height="{height}" alt="pic" />
{/images}

{files match="audio|files|movie"}
<a href="{file}">Download File</a>
{/files}
EOT;

	}

	// --------------------------------------------------------------------

	/**
	 * Moblog Homepage
	 *
	 * @access	public
	 * @return	string
	 */
	function index()
	{
		$table = ee('CP/Table');
		$table->setColumns(array(
			'col_id',
			'moblog',
			'manage' => array(
				'type'	=> Table::COL_TOOLBAR
			),
			array(
				'type'	=> Table::COL_CHECKBOX
			)
		));

		$table->setNoResultsText(sprintf(lang('no_found'), lang('moblogs')), 'create_moblog', ee('CP/URL')->make('addons/settings/moblog/create'));

		$sort_map = array(
			'col_id' => 'moblog_id',
			'moblog' => 'moblog_full_name',
		);

		$moblogs = ee()->db->select('moblog_id, moblog_full_name')
			->order_by($sort_map[$table->sort_col], $table->sort_dir)
			->get('moblogs')
			->result_array();

		$data = array();
		foreach ($moblogs as $moblog)
		{
			$edit_url = ee('CP/URL')->make('addons/settings/moblog/edit/'.$moblog['moblog_id']);
			$columns = array(
				$moblog['moblog_id'],
				array(
					'content' => $moblog['moblog_full_name'],
					'href' => $edit_url
				),
				array('toolbar_items' => array(
					'edit' => array(
						'href' => $edit_url,
						'title' => lang('edit')
					),
					'copy' => array(
						'href' => ee('CP/URL')->make('addons/settings/moblog/create/'.$moblog['moblog_id']),
						'title' => lang('copy')
					),
					'txt-only' => array(
						'href' => ee('CP/URL')->make('addons/settings/moblog/check/'.$moblog['moblog_id']),
						'title' => (lang('check_now')),
						'content' => strtolower(lang('check_now'))
					)
				)),
				array(
					'name' => 'moblogs[]',
					'value' => $moblog['moblog_id'],
					'data'	=> array(
						'confirm' => lang('moblog') . ': <b>' . htmlentities($moblog['moblog_full_name'], ENT_QUOTES, 'UTF-8') . '</b>'
					)
				)
			);

			$attrs = array();
			if (ee()->session->flashdata('highlight_id') == $moblog['moblog_id'])
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs' => $attrs,
				'columns' => $columns
			);
		}

		$table->setData($data);

		$vars['base_url'] = ee('CP/URL')->make('addons/settings/moblog');
		$vars['table'] = $table->viewData($vars['base_url']);

		$vars['pagination'] = ee('CP/Pagination', count($moblogs))
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($vars['table']['base_url']);

		ee()->javascript->set_global('lang.remove_confirm', lang('moblogs') . ': <b>### ' . lang('moblogs') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove'),
		));

		return ee('View')->make('moblog:index')->render($vars);
	}

	/**
	 * Remove moblogs handler
	 */
	public function remove()
	{
		$moblog_ids = ee()->input->post('moblogs');

		if ( ! empty($moblog_ids) && ee()->input->post('bulk_action') == 'remove')
		{
			// Filter out junk
			$moblog_ids = array_filter($moblog_ids, 'is_numeric');

			if ( ! empty($moblog_ids))
			{
				ee('Model')->get('moblog:Moblog', $moblog_ids)->delete();

				ee('CP/Alert')->makeInline('moblogs-table')
					->asSuccess()
					->withTitle(lang('moblogs_removed'))
					->addToBody(sprintf(lang('moblogs_removed_desc'), count($moblog_ids)))
					->defer();
			}
		}
		else
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->functions->redirect(ee('CP/URL')->make('addons/settings/moblog', ee()->cp->get_url_state()));
	}

	/**
	 * New moblog form
	 */
	public function create($moblog_id = NULL)
	{
		$duplicate = ! is_null($moblog_id);
		return $this->form($moblog_id, $duplicate);
	}

	/**
	 * Edit moblog form
	 */
	public function edit($moblog_id)
	{
		return $this->form($moblog_id);
	}

	/**
	 * Moblog creation/edit form
	 *
	 * @param	int		$moblog_id	ID of moblog to edit
	 * @param	boolean	$duplicate	Whether or not to duplicate the passed moblog
	 */
	private function form($moblog_id = NULL, $duplicate = FALSE)
	{
		$vars = array();
		if (is_null($moblog_id) OR $duplicate)
		{
			ee()->cp->add_js_script('plugin', 'ee_url_title');
			ee()->javascript->output('
				$("input[name=moblog_full_name]").bind("keyup keydown", function() {
					$(this).ee_url_title("input[name=moblog_short_name]");
				});
			');

			$alert_key = 'created';
			$vars['cp_page_title'] = lang('create_moblog');
			$vars['base_url'] = ee('CP/URL')->make('addons/settings/moblog/create');

			$moblog = ee('Model')->make('moblog:Moblog');
		}
		else
		{
			$moblog = ee('Model')->get('moblog:Moblog', $moblog_id)->first();

			if ( ! $moblog)
			{
				show_error(lang('unauthorized_access'));
			}

			$alert_key = 'updated';
			$vars['cp_page_title'] = lang('edit_moblog');
			$vars['base_url'] = ee('CP/URL')->make('addons/settings/moblog/edit/'.$moblog_id);
		}

		if ($duplicate)
		{
			$moblog = ee('Model')->get('moblog:Moblog', $moblog_id)->first();
			$vars['base_url'] = ee('CP/URL')->make('addons/settings/moblog/create/'.$moblog_id);
		}

		if ( ! empty($_POST))
		{
			if ($duplicate)
			{
				$moblog = ee('Model')->make('moblog:Moblog');
			}

			$moblog->set($_POST);

			// Need to convert this field from its presentation serialization
			$moblog->moblog_valid_from = explode(',', trim(preg_replace("/[\s,|]+/", ',', $_POST['moblog_valid_from']), ','));

			$result = $moblog->validate();

			if ($result->isValid())
			{
				$moblog = $moblog->save();

				if (is_null($moblog_id) OR $duplicate)
				{
					ee()->session->set_flashdata('highlight_id', $moblog->getId());
				}

				ee('CP/Alert')->makeInline('moblogs-table')
					->asSuccess()
					->withTitle(lang('moblog_'.$alert_key))
					->addToBody(sprintf(lang('moblog_'.$alert_key.'_desc'), $moblog->moblog_full_name))
					->defer();

				ee()->functions->redirect(ee('CP/URL')->make('addons/settings/moblog'));
			}
			else
			{
				$vars['errors'] = $result;
				ee('CP/Alert')->makeInline('moblogs-table')
					->asIssue()
					->withTitle(lang('moblog_not_'.$alert_key))
					->addToBody(lang('moblog_not_'.$alert_key.'_desc'))
					->now();
			}
		}

		$channels = ee('Model')->get('Channel')->with('Site');

		if (ee()->config->item('multiple_sites_enabled') !== 'y')
		{
			$channels = $channels->filter('site_id', 1);
		}
		$channels = $channels->all();

		$channels_options = array();
		foreach ($channels as $channel)
		{
			$channels_options[$channel->channel_id] = (ee()->config->item('multiple_sites_enabled') === 'y')
				? $channel->Site->site_label.NBS.'-'.NBS.$channel->channel_title : $channel->channel_title;
		}

		$author_options = array();

		// First, get member groups who should be in the list
		$member_groups = ee('Model')->get('MemberGroup')
			->with('AssignedChannels')
			->filter('include_in_authorlist', 'y')
			->fields('group_id')
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		// Then authors who are individually selected to appear in author list
		$authors = ee('Model')->get('Member')
			->fields('username', 'screen_name')
			->filter('in_authorlist', 'y');

		// Then grab any members that are part of the member groups we found
		if ($member_groups->count())
		{
			$authors->orFilter('group_id', 'IN', $member_groups->pluck('group_id'));
		}

		$authors->order('screen_name');
		$authors->order('username');

		foreach ($authors->all() as $author)
		{
			$author_options[$author->getId()] = $author->getMemberName();
		}

		$moblog_authors = $author_options;// ee('Model')->get('Member')->fields('member_id', 'screen_name')->limit(100)->all()->getDictionary('member_id', 'screen_name');

		$vars['sections'] = array(
			array(
				array(
					'title' => 'moblog_name',
					'fields' => array(
						'moblog_full_name' => array(
							'type' => 'text',
							'value' => $moblog->moblog_full_name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'moblog_short_name',
					'desc' => 'alphadash_desc',
					'fields' => array(
						'moblog_short_name' => array(
							'type' => 'text',
							'value' => $moblog->moblog_short_name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'moblog_check_interval',
					'desc' => 'moblog_check_interval_desc',
					'fields' => array(
						'moblog_time_interval' => array(
							'type' => 'text',
							'value' => $moblog->moblog_time_interval,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'moblog_enabled',
					'fields' => array(
						'moblog_enabled' => array(
							'type' => 'yes_no',
							'value' => is_null($moblog->moblog_enabled) ? TRUE : $moblog->moblog_enabled
						)
					)
				),
				array(
					'title' => 'file_archive_mode',
					'desc' => 'file_archive_mode_desc',
					'fields' => array(
						'moblog_file_archive' => array(
							'type' => 'yes_no',
							'value' => $moblog->moblog_file_archive
						)
					)
				)
			),
			'channel_entry_settings' => array(
				array(
					'title' => 'channel',
					'desc' => 'moblog_channel_desc',
					'fields' => array(
						'moblog_channel_id' => array(
							'type' => 'select',
							'choices' => $channels_options,
							'value' => $moblog->moblog_channel_id
						)
					)
				),
				array(
					'title' => 'cat_id',
					'fields' => array(
						'moblog_categories' => array(
							'type' => 'checkbox',
							'choices' => ee('Model')->get('Category')->fields('cat_id', 'cat_name')->all()->getDictionary('cat_id', 'cat_name'),
							'value' => $moblog->moblog_categories
						)
					)
				),
				array(
					'title' => 'field_id',
					'fields' => array(
						'moblog_field_id' => array(
							'type' => 'select',
							'choices' => ee('Model')->get('ChannelField')->fields('field_id', 'label')->all()->getDictionary('field_id', 'field_label'),
							'value' => $moblog->moblog_field_id
						)
					)
				),
				array(
					'title' => 'default_status',
					'fields' => array(
						'moblog_status' => array(
							'type' => 'select',
							'choices' => ee('Model')->get('Status')->fields('status')->all()->getDictionary('status', 'status'),
							'value' => $moblog->moblog_status
						)
					)
				),
				array(
					'title' => 'author_id',
					'fields' => array(
						'moblog_author_id' => array(
							'type' => 'select',
							'choices' => $moblog_authors,
							'value' => $moblog->moblog_author_id
						)
					)
				),
				array(
					'title' => 'moblog_sticky_entry',
					'fields' => array(
						'moblog_sticky_entry' => array(
							'type' => 'yes_no',
							'value' => $moblog->moblog_sticky_entry
						)
					)
				),
				array(
					'title' => 'moblog_allow_overrides',
					'desc' => 'moblog_allow_overrides_subtext',
					'fields' => array(
						'moblog_allow_overrides' => array(
							'type' => 'yes_no',
							'value' => $moblog->moblog_allow_overrides
						)
					)
				),
				array(
					'title' => 'moblog_template',
					'fields' => array(
						'moblog_template' => array(
							'type' => 'textarea',
							'value' => $moblog->moblog_template ?: $this->default_template
						)
					)
				)
			),
			'moblog_email_settings' => array(
				array(
					'title' => 'moblog_email_type',
					'fields' => array(
						'moblog_email_type' => array(
							'type' => 'select',
							'choices' => array('pop3' => lang('pop3')),
							'value' => $moblog->moblog_email_type
						)
					)
				),
				array(
					'title' => 'moblog_email_address',
					'fields' => array(
						'moblog_email_address' => array(
							'type' => 'text',
							'value' => $moblog->moblog_email_address,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'moblog_email_server',
					'desc' => 'server_example',
					'fields' => array(
						'moblog_email_server' => array(
							'type' => 'text',
							'value' => $moblog->moblog_email_server,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'moblog_email_login',
					'desc' => 'data_encrypted',
					'fields' => array(
						'moblog_email_login' => array(
							'type' => 'text',
							'value' => $moblog->moblog_email_login,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'moblog_email_password',
					'desc' => 'data_encrypted',
					'fields' => array(
						'moblog_email_password' => array(
							'type' => 'password',
							'value' => $moblog->moblog_email_password,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'moblog_subject_prefix',
					'desc' => 'moblog_subject_subtext',
					'fields' => array(
						'moblog_subject_prefix' => array(
							'type' => 'text',
							'value' => $moblog->moblog_subject_prefix
						)
					)
				),
				array(
					'title' => 'moblog_auth_required',
					'desc' => 'moblog_auth_subtext',
					'fields' => array(
						'moblog_auth_required' => array(
							'type' => 'yes_no',
							'value' => $moblog->moblog_auth_required
						)
					)
				),
				array(
					'title' => 'moblog_auth_delete',
					'desc' => 'moblog_auth_delete_subtext',
					'fields' => array(
						'moblog_auth_delete' => array(
							'type' => 'yes_no',
							'value' => $moblog->moblog_auth_delete
						)
					)
				),
				array(
					'title' => 'moblog_valid_from',
					'desc' => 'valid_from_subtext',
					'fields' => array(
						'moblog_valid_from' => array(
							'type' => 'textarea',
							'value' => implode("\n", $moblog->moblog_valid_from)
						)
					)
				),
				array(
					'title' => 'moblog_ignore_text',
					'desc' => 'ignore_text_subtext',
					'fields' => array(
						'moblog_ignore_text' => array(
							'type' => 'textarea',
							'value' => $moblog->moblog_ignore_text
						)
					)
				)
			),
			'moblog_file_settings' => array(
				array(
					'title' => 'moblog_upload_directory',
					'fields' => array(
						'moblog_upload_directory' => array(
							'type' => 'select',
							'choices' => ee('Model')->get('UploadDestination')
								->fields('site_id', 'module_id', 'id', 'name')
								->filter('site_id', ee()->config->item('site_id'))
								->filter('module_id', 0)
								->all()
								->getDictionary('id', 'name'),
							'value' => $moblog->moblog_upload_directory
						)
					)
				),
				array(
					'title' => 'moblog_image_size',
					'fields' => array(
						'moblog_image_size' => array(
							'type' => 'select',
							'choices' => array('0'=> lang('none')),
							'value' => $moblog->moblog_image_size
						)
					)
				),
				array(
					'title' => 'moblog_thumb_size',
					'fields' => array(
						'moblog_thumb_size' => array(
							'type' => 'select',
							'choices' => array('0'=> lang('none')),
							'value' => $moblog->moblog_thumb_size
						)
					)
				)
			)
		);

		$this->_filtering_menus('moblog_create');
		ee()->javascript->compile();

		$vars['save_btn_text'] = 'save_moblog';
		$vars['save_btn_text_working'] = 'btn_saving';

		return array(
			'heading'    => $vars['cp_page_title'],
			'breadcrumb' => array(ee('CP/URL')->make('addons/settings/moblog')->compile() => lang('moblog') . ' ' . lang('configuration')),
			'body'       => ee('View')->make('moblog:create')->render($vars)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * JavaScript filtering code
	 *
	 * Creates some javascript functions that are used to switch
	 * various pull-down menus
	 *
	 * @access	public
	 * @return	void
	 */
	function _filtering_menus($form_name)
	{
		// In order to build our filtering options we need to gather
		// all the channels, categories and custom statuses

		/** -----------------------------
		/**  Allowed Channels
		/** -----------------------------*/

		$allowed_channels = ee()->functions->fetch_assigned_channels(TRUE);

		if (count($allowed_channels) > 0)
		{
			// Fetch channel titles
			ee()->db->select('channel_title, channel_id, cat_group, status_group, field_group');

			if ( ! ee()->cp->allowed_group('can_edit_other_entries'))
			{
				ee()->db->where_in('channel_id', $allowed_channels);
			}

			ee()->db->order_by('channel_title');
			$query = ee()->db->get('channels');

			foreach ($query->result_array() as $row)
			{
				$this->channel_array[$row['channel_id']] = array(str_replace('"','',$row['channel_title']), $row['cat_group'], $row['status_group'], $row['field_group']);
			}
		}

		ee()->legacy_api->instantiate('channel_categories');

		//  Category Tree
		$cat_array = ee()->api_channel_categories->category_form_tree('y', FALSE, 'all');

		/** -----------------------------
		/**  Entry Statuses
		/** -----------------------------*/

		ee()->db->select('group_id, status');
		ee()->db->order_by('status_order');
		$query = ee()->db->get('statuses');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$this->status_array[]  = array($row['group_id'], $row['status']);
			}
		}

		/** -----------------------------
		/**  Custom Channel Fields
		/** -----------------------------*/

		/* -------------------------------------
		/*  Hidden Configuration Variable
		/*  - moblog_allow_nontextareas => Removes the textarea only restriction
		/*	for custom fields in the moblog module (y/n)
		/* -------------------------------------*/

		ee()->db->select('group_id, field_label, field_id');
		ee()->db->order_by('field_label');

		if (ee()->config->item('moblog_allow_nontextareas') != 'y')
		{
			ee()->db->where('channel_fields.field_type', 'textarea');
		}

		$query = ee()->db->get('channel_fields');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$this->field_array[]  = array($row['group_id'], $row['field_id'], str_replace('"','',$row['field_label']));
			}
		}

		/** -----------------------------
		/**  SuperAdmins
		/** -----------------------------*/

		ee()->db->select('member_id, username, screen_name');
		ee()->db->where('group_id', '1');
		$query = ee()->db->get('members');

		foreach ($query->result_array() as $row)
			{
				$author = ($row['screen_name'] == '') ? $row['username'] : $row['screen_name'];

				foreach($this->channel_array as $key => $value)
				{
					$this->author_array[]  = array($key, $row['member_id'], str_replace('"','',$author));
				}
			}

		/** -----------------------------
		/**  Assignable Channel Authors
		/** -----------------------------*/
		$dbp = ee()->db->dbprefix;

		ee()->db->select('channels.channel_id, members.member_id, members.group_id, members.username, members.screen_name');
		ee()->db->from(array('channels', 'members', 'channel_member_groups'));
		ee()->db->where("({$dbp}channel_member_groups.channel_id = {$dbp}channels.channel_id OR {$dbp}channel_member_groups.channel_id IS NULL)");
		ee()->db->where("{$dbp}members.group_id", "{$dbp}channel_member_groups.group_id", FALSE);

		$query = ee()->db->get();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$author = ($row['screen_name'] == '') ? $row['username'] : $row['screen_name'];

				$this->author_array[]  = array($row['channel_id'], $row['member_id'], str_replace('"','',$author));
			}
		}

		// Create JSON Reference

		// Mixing php with output buffering was ugly, so we'll build out a js objects with
		// all the information we need and then manipulate that in javascript

		$channel_info = array();

		foreach ($this->channel_array as $key => $val)
		{
			$any = 0;
			$cats = array();

			if (count($cat_array) > 0)
			{
				$last_group = 0;

				foreach ($cat_array as $k => $v)
				{
					if (in_array($v['0'], explode('|', $val['1'])))
					{
						if ( ! isset($set))
						{
							$cats[] = array('', lang('all'));

							$set = 'y';
						}

						if ($last_group == 0 OR $last_group != $v['0'])
						{
							$last_group = $v['0'];
						}

						$cats[] = array($v['1'], $v['2']);
					}
				}

				if ( ! isset($set))
		        {
					$cats[] = array('none', lang('none'));
		        }
				unset($set);
			}

			$channel_info[$key]['moblog_categories'] = $cats;

			$statuses = array();

			$statuses[] = array('none', lang('none'));

			if (count($this->status_array) > 0)
			{
				foreach ($this->status_array as $k => $v)
				{
					if ($v['0'] == $val['2'])
					{
						$status_name = ($v['1'] == 'closed' OR $v['1'] == 'open') ?  lang($v['1']) : $v['1'];
						$statuses[] = array($v['1'], $status_name);
					}
				}
			}
			else
			{
				$statuses[] = array($v['1'], lang('open'));
				$statuses[] = array($v['1'], lang('closed'));
			}

			$channel_info[$key]['moblog_status'] = $statuses;

			$fields = array();

			$fields[] = array('none', lang('none'));


			if (count($this->field_array) > 0)
			{
				foreach ($this->field_array as $k => $v)
				{
					if ($v['0'] == $val['3'])
					{
						$fields[] = array($v['1'], $v['2']);
					}
				}
			}

			$channel_info[$key]['moblog_field_id'] = $fields;

			$authors = array();

			$authors[] = array('0', lang('none'));

			if (count($this->author_array) > 0)
			{
				$inserted_authors = array();

				foreach ($this->author_array as $k => $v)
				{
					if ($v['0'] == $key && ! in_array($v['1'],$inserted_authors))
					{
						$inserted_authors[] = $v['1'];
						$authors[] = array($v['1'], $v['2']);
					}
				}
			}

			$channel_info[$key]['moblog_author_id'] = $authors;
		}

		$channel_info = json_encode($channel_info);
		$none_text = lang('none');

		$javascript = <<<MAGIC

// An object to represent our channels
var channel_map = $channel_info;

var empty_select =  '<option value="0">$none_text</option>';
var spaceString = new RegExp('!-!', "g");

// We prep the magic array as soon as we can, basically
// converting everything into option elements
(function() {
	jQuery.each(channel_map, function(key, details) {

		// Go through each of the individual settings and build a proper dom element
		jQuery.each(details, function(group, values) {
			var html = new String();

			if (group == 'moblog_categories') {
				var checkbox_values = [];
				// Categories are checkboxes
				$('input[name="moblog_categories[]"]:checked').each(function() {
					checkbox_values.push(this.value);
				});
				jQuery.each(values, function(a, b) {
					var checked = '',
						chosen = '';
					if ($.inArray(b[0], checkbox_values) > -1) {
						checked = ' checked';
						chosen = ' chosen';
					}
					html += '<label class="choice block'+chosen+'"><input type="checkbox" name="moblog_categories[]" value ="' + b[0] + '"'+checked+'>' + b[1].replace(spaceString, String.fromCharCode(160)) + "</label>";
				});
			} else {
				var value = $('select[name="'+group+'"]').val();
				// Add the new option fields
				jQuery.each(values, function(a, b) {
					var selected = (value == b[0]) ? ' selected' : '';console.log(value + ' ' + b[0]);
					html += '<option value="' + b[0] + '"'+selected+'>' + b[1].replace(spaceString, String.fromCharCode(160)) + "</option>";
					//console.log(html);
				});
			}

			channel_map[key][group] = html;
		});
	});
})();

// Change the submenus
// Gets passed the channel id
function changemenu(index)
{
	var channels = 'null';

	if (channel_map[index] === undefined) {
		$('select[name=moblog_field_id], select[name="moblog_categories"], select[name=moblog_status], select[name=moblog_author_id]').empty().append(empty_select);
	}
	else {
		jQuery.each(channel_map[index], function(key, val) {
			switch(key) {
				case 'moblog_field_id':		$('select[name=moblog_field_id]').empty().append(val);
					break;
				case 'moblog_categories':	$('input[name="moblog_categories[]"]').parents('.setting-field').empty().append(val);
					break;
				case 'moblog_status':	$('select[name=moblog_status]').empty().append(val);
					break;
				case 'moblog_author_id':		$('select[name=moblog_author_id]').empty().append(val);
					break;
			}
		});
	}
}

$('select[name=moblog_channel_id]').change(function() {
	changemenu(this.value);
}).change();

MAGIC;

		// And same idea for file upload dirs and dimensions
		$this->upload_loc_array = array('0' => lang('none'));
		$this->image_dim_array = array('0' => $this->upload_loc_array);

		// Fetch Upload Directories
		ee()->load->model(array('file_model', 'file_upload_preferences_model'));

		$sizes_q = ee()->file_model->get_dimensions_by_dir_id();
		$sizes_array = array();

		foreach ($sizes_q->result_array() as $row)
		{
			$sizes_array[$row['upload_location_id']][$row['id']] = $row['title'];
		}

		$upload_q = ee()->file_upload_preferences_model->get_file_upload_preferences(ee()->session->userdata['group_id']);

		foreach ($upload_q as $row)
		{
			$this->image_dim_array[$row['id']] = array('0' => lang('none'));
			$this->upload_loc_array[$row['id']] = $row['name'];

			// Get sizes
			if (isset($sizes_array[$row['id']]))
			{
				foreach ($sizes_array[$row['id']] as $id => $title)
				{
					$this->image_dim_array[$row['id']][$id] = $title;
				}
			}
		}

		$upload_info = json_encode($this->image_dim_array);

		$javascript .= <<<MAGIC

// An object to represent our channels
var upload_info = $upload_info;

var empty_select =  '<option value="0">$none_text</option>';
var spaceString = new RegExp('!-!', "g");

// We prep the magic array as soon as we can, basically
// converting everything into option elements
(function(undefined) {
	jQuery.each(upload_info, function(key, options) {

		var html = '';

		// add option fields
		jQuery.each(options, function(k, v) {

			html += '<option value="' + k + '">' + v.replace(spaceString, String.fromCharCode(160)) + "</option>";
		});

		if (html) {
			upload_info[key] = html;
		}
	});
})();

// Change the submenus
// Gets passed the channel id
function upload_changemenu(index)
{
	$('select[name=moblog_image_size]').empty().append(upload_info[index]);
	$('select[name=moblog_thumb_size]').empty().append(upload_info[index]);
}

$('select[name=moblog_upload_directory]').change(function() {
	upload_changemenu(this.value);
}).change();

MAGIC;



		ee()->javascript->output($javascript);
	}

	/** -------------------------
	/**  Check Moblog
	/** -------------------------*/

	function check($moblog_id)
	{
		$where = array(
			'moblog_enabled'	=> 'y',
			'moblog_id'			=> $moblog_id
		);

		$query = ee()->db->get_where('moblogs', $where);

		if ($query->num_rows() == 0)
		{
			return ee()->output->show_user_error('submission', array(lang('invalid_moblog')));
		}

		if ( ! class_exists('Moblog'))
		{
			require PATH_ADDONS.'moblog/mod.moblog.php';
		}

		$MP = new Moblog();
		$MP->moblog_array = $query->row_array();

		$error = FALSE;

		if ($MP->moblog_array['moblog_email_type'] == 'imap')
		{
			$this->_moblog_check_return($MP->check_imap_moblog(), $MP);
		}
		else
		{
			$this->_moblog_check_return($MP->check_pop_moblog(), $MP);
		}
	}

	/** -------------------------
	/**  Moblog Check Return
	/** -------------------------*/

	function _moblog_check_return($response, $MP)
	{
		if ( ! $response)
		{
			ee('CP/Alert')->makeInline('moblogs-table')
				->asIssue()
				->withTitle(lang('moblog_check_failure'))
				->addToBody($MP->errors())
				->defer();
		}
		else
		{
			ee('CP/Alert')->makeInline('moblogs-table')
				->asSuccess()
				->withTitle(lang('moblog_check_success'))
				->addToBody(lang('emails_done').NBS.$MP->emails_done)
				->addToBody(lang('entries_added').NBS.$MP->entries_added)
				->addToBody(lang('attachments_uploaded').NBS.$MP->uploads)
				->defer();
		}

		ee()->functions->redirect(ee('CP/URL')->make('addons/settings/moblog', ee()->cp->get_url_state()));
	}

}
// END CLASS

// EOF
