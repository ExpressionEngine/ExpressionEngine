<?php

namespace EllisLab\ExpressionEngine\Controllers\Channels;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Controllers\Channels\AbstractChannel as AbstractChannelController;

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
class Channels extends AbstractChannelController {

	/**
	 * Channel Manager
	 */
	public function index()
	{
		$table = ee('CP/Table');
		$table->setColumns(
			array(
				'col_id',
				'channel',
				'short_name',
				'manage' => array(
					'type'	=> CP\Table::COL_TOOLBAR
				),
				array(
					'type'	=> CP\Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_channels', 'create_channel', cp_url('channels/create'));

		$sort_map = array(
			'col_id' => 'channel_id',
			'channel' => 'channel_title',
			'short_name' => 'channel_name'
		);

		$channels = ee('Model')->get('Channel')
			->filter('site_id', ee()->config->item('site_id'));
		$total_rows = $channels->all()->count();

		$channels = $channels->order($sort_map[$table->sort_col], $table->sort_dir)
			->limit(20)
			->offset(($table->config['page'] - 1) * 20)
			->all();

		$data = array();
		foreach ($channels as $channel)
		{
			$data[] = array(
				$channel->channel_id,
				htmlentities($channel->channel_title, ENT_QUOTES),
				htmlentities($channel->channel_name, ENT_QUOTES),
				array('toolbar_items' => array(
					'edit' => array(
						'href' => cp_url('channels/edit/'.$channel->channel_id),
						'title' => lang('edit')
					),
					'settings' => array(
						'href' => cp_url('channels/settings/'.$channel->channel_id),
						'title' => lang('settings')
					),
					'layout' => array(
						'href' => cp_url('channels/layout/'.$channel->channel_id),
						'title' => lang('layout')
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
			$total_rows,
			$vars['table']['page']
		);
		$vars['pagination'] = $pagination->cp_links($vars['table']['base_url']);

		ee()->view->cp_page_title = lang('manage_channels');

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

		ee()->functions->redirect(cp_url('channels', ee()->cp->get_url_state()));
	}

	/**
	 * New channel form
	 */
	public function create()
	{
		$this->form();
	}

	/**
	 * Edit channel form
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
			ee()->cp->add_js_script('plugin', 'ee_url_title');
			ee()->javascript->output('
				$("input[name=channel_title]").bind("keyup keydown", function() {
					$(this).ee_url_title("input[name=channel_name]");
				});
			');

			ee()->view->cp_page_title = lang('create_new_channel');
			ee()->view->base_url = cp_url('channels/create');
			ee()->view->save_btn_text = 'create_channel';
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
			ee()->view->base_url = cp_url('channels/edit/'.$channel_id);
			ee()->view->save_btn_text = 'edit_channel';
		}

		// Channel duplicate preferences menu
		$channels = ee('Model')->get('Channel')
			->filter('site_id', ee()->config->item('site_id'))
			->order('channel_title')
			->all();
		$duplicate_channel_prefs_options[''] = lang('channel_do_not_duplicate');
		foreach($channels as $dupe_channel)
		{
			$duplicate_channel_prefs_options[$dupe_channel->channel_id] = $dupe_channel->channel_title;
		}

		// Category group options
		$cat_group_options = array();
		$category_groups = ee('Model')->get('CategoryGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->order('group_name')
			->all();
		foreach ($category_groups as $group)
		{
			$cat_group_options[$group->group_id] = $group->group_name;
		}

		// Status group options
		$status_group_options[''] = lang('none');
		$status_groups = ee('Model')->get('StatusGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->order('group_name')
			->all();
		foreach ($status_groups as $group)
		{
			$status_group_options[$group->group_id] = $group->group_name;
		}

		// Field group options
		$field_group_options[''] = lang('none');
		$field_groups = ee('Model')->get('ChannelFieldGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->order('group_name')
			->all();
		foreach ($field_groups as $group)
		{
			$field_group_options[$group->group_id] = $group->group_name;
		}

		// Alert to show only for new channels
		$alert = (is_null($channel_id)) ? ee('Alert')->makeInline('permissions-warn')
			->asWarning()
			->addToBody(lang('channel_publishing_options_warning'))
			->addToBody(sprintf(lang('channel_publishing_options_warning2'), cp_url('channels/field')))
			->cannotClose()
			->render() : '';

		$vars['sections'] = array(
			array(
				array(
					'title' => 'channel_title',
					'desc' => 'channel_title_desc',
					'fields' => array(
						'channel_title' => array(
							'type' => 'text',
							'value' => $channel->channel_title,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'short_name',
					'desc' => 'channel_short_name_desc',
					'fields' => array(
						'channel_name' => array(
							'type' => 'text',
							'value' => $channel->channel_name,
							'required' => TRUE
						)
					)
				)
			)
		);

		// Only show duplicate channel option for new channels
		if (is_null($channel_id))
		{
			$vars['sections'][0][] = array(
				'title' => 'channel_duplicate',
				'desc' => 'channel_duplicate_desc',
				'fields' => array(
					'duplicate_channel_prefs' => array(
						'type' => 'dropdown',
						'choices' => $duplicate_channel_prefs_options
					)
				)
			);
		}

		$vars['sections']['channel_publishing_options'] = array(
			$alert,
			array(
				'title' => ucfirst(strtolower(lang('status_groups'))),
				'desc' => 'status_groups_desc',
				'fields' => array(
					'status_group' => array(
						'type' => 'dropdown',
						'choices' => $status_group_options,
						'value' => $channel->status_group,
						'no_results' => array(
							'text' => 'status_groups_not_found',
							'link_text' => 'create_new_status_group',
							'link_href' => cp_url('channels/status/create')
						)
					)
				)
			),
			array(
				'title' => 'custom_field_group',
				'desc' => 'custom_field_group_desc',
				'fields' => array(
					'field_group' => array(
						'type' => 'dropdown',
						'choices' => $field_group_options,
						'value' => $channel->field_group,
						'no_results' => array(
							'text' => 'custom_field_groups_not_found',
							'link_text' => 'create_new_field_group',
							'link_href' => cp_url('channels/groups/create')
						)
					)
				)
			),
			array(
				'title' => ucfirst(strtolower(lang('category_groups'))),
				'desc' => 'category_groups_desc',
				'fields' => array(
					'cat_group' => array(
						'type' => 'checkbox',
						'choices' => $cat_group_options,
						'value' => explode('|', $channel->cat_group),
						'no_results' => array(
							'text' => 'category_groups_not_found',
							'link_text' => 'create_new_category_group',
							'link_href' => cp_url('channels/cat/create')
						)
					)
				)
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				'field' => 'channel_title',
				'label' => 'lang:channel_title',
				'rules' => 'required|strip_tags|trim|valid_xss_check'
			),
			array(
				'field' => 'channel_name',
				'label' => 'lang:channel_short_name',
				'rules' => 'required|strip_tags|callback__validChannelName['.$channel_id.']'
			)
		));

		ee()->form_validation->validateNonTextInputs($vars['sections']);

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$channel_id = $this->saveChannel($channel);

			ee('Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('channel_saved'))
				->addToBody(lang('channel_saved_desc'))
				->defer();

			ee()->functions->redirect(cp_url('channels/edit/' . $channel_id));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('channel_not_saved'))
				->addToBody(lang('channel_not_saved_desc'))
				->now();
		}

		ee()->view->header = array(
			'title' => lang('channel_manager'),
			'form_url' => cp_url('channels/search'),
			'toolbar_items' => array(
				'settings' => array(
					'href' => cp_url('settings/content-design'),
					'title' => lang('settings')
				)
			)
		);

		ee()->view->cp_page_title = is_null($channel_id) ? lang('create_channel') : lang('edit_channel');
		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->set_breadcrumb(cp_url('channels'), lang('channels'));

		ee()->cp->render('settings/form', $vars);
	}

	/**
	 * Custom validator for channel short name
	 */
	public function _validChannelName($str, $channel_id = NULL)
	{
		// Check short name characters
		if (preg_match('/[^a-z0-9\-\_]/i', $str))
		{
			ee()->form_validation->set_message('_validChannelName', lang('invalid_short_name'));
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
			ee()->form_validation->set_message('_validChannelName', lang('taken_channel_name'));
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
	private function saveChannel($channel)
	{
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

		$channel->set($_POST);

		// Make sure these are the correct NULL value if they are not set.
		$channel->status_group = ($channel->status_group !== FALSE
			&& $channel->status_group != '')
			? $channel->status_group : NULL;
		$channel->field_group = ($channel->field_group !== FALSE &&
			$channel->field_group != '')
			? $channel->field_group : NULL;

		// Create Channel
		if ($channel->isNew())
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
				$dupe_channel = ee('Model')->get('Channel')
					->filter('channel_id', $dupe_id)
					->first();
				$channel->duplicatePreferences($dupe_channel);
			}

			$channel->save();

			if ($dupe_id !== FALSE AND is_numeric($dupe_id))
			{
				// Duplicate layouts
				ee()->load->library('layout');
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
			$channel->save();
		}

		return $channel->channel_id;
	}

	/**
	 * Channel settings
	 */
	public function settings($channel_id)
	{
		$channel = ee('Model')->get('Channel', $channel_id)->first();

		if ( ! $channel)
		{
			show_error(lang('unauthorized_access'));
		}

		$templates = ee('Model')->get('Template')
			->with('TemplateGroup')
			->all();

		$live_look_template_options[0] = lang('no_live_look_template');

		if ( count($templates) > 0)
		{
			foreach ($templates as $template)
			{
				$live_look_template_options[$template->template_id] = $template->getTemplateGroup()->group_name.'/'.$template->template_name;
			}
		}

		// Default status menu
		$statuses = ee('Model')->get('Status')
			->with('StatusGroup')
			->filter('Status.group_id', $channel->status_group)
			->all();

		// These will always be there, and also need extra processing.
		$deft_status_options['open'] = lang('open');
		$deft_status_options['closed'] = lang('closed');
		if (count($statuses) > 0)
		{
			foreach ($statuses as $status)
			{
				// We already did these ones, so skip em.
				if ($status->status == 'open' || $status->status == 'closed')
				{
					continue;
				}

				$deft_status_options[$status->status] = $status->status;
			}
		}

		$deft_category_options[''] = lang('none');

		$category_group_ids = $channel->cat_group ? explode('|', $channel->cat_group) : array();

		if (count($category_group_ids))
		{
			$categories = ee('Model')->get('Category')
				->with('CategoryGroup')
				->filter('CategoryGroup.group_id', 'IN', $category_group_ids)
				->order('CategoryGroup.group_name')
				->order('Category.cat_name')
				->all();

			if (count($categories) > 0)
			{
				foreach ($categories as $category)
				{
					$deft_category_options[$category->cat_id] = $category->getCategoryGroup()->group_name . ': ' . $category->cat_name;
				}
			}
		}

		$channel_fields = ee('Model')->get('ChannelField')
			->filter('ChannelField.group_id', $channel->field_group)
			->all();

		$search_excerpt_options = array();

		if (count($channel_fields) > 0)
		{
			foreach ($channel_fields as $channel_field)
			{
				$search_excerpt_options[$channel_field->field_id] = $channel_field->field_label;
			}
		}

		// HTML formatting
		$channel_html_formatting_options = array(
			'none'	=> lang('convert_to_entities'),
			'safe'	=> lang('allow_safe_html'),
			'all'	=> lang('allow_all_html')
		);

		// Default comment text formatting
		$comment_text_formatting_options = array(
			'none'	=> lang('none'),
			'xhtml'	=> lang('xhtml'),
			'br'	=> lang('auto_br')
		);

		// Comment HTML formatting
		$comment_html_formatting_options = array(
			'none'	=> lang('convert_to_entities'),
			'safe'	=> lang('allow_safe_html'),
			'all'	=> lang('allow_all_html_not_recommended')
		);

		if ( ! $channel_form = $channel->getChannelFormSettings())
		{
			$channel_form = ee('Model')->make('ChannelFormSettings');
		}

		ee()->load->model('member_model');
		$authors = ee()->member_model->get_authors()->result();

		$all_authors = array();
		foreach ($authors as $author)
		{
			$all_authors[$author->member_id] = $author->username;
		}

		if (empty($all_authors))
		{
			foreach ($this->member_model->get_members(1)->result_array() as $member)
			{
				$all_authors[$member['member_id']] = $member['username'];
			}
		}

		// Add "Use Channel Default" option for channel form default status
		$channel_form_statuses = array('' => lang('channel_form_default_status_empty'));
		$channel_form_statuses = array_merge($channel_form_statuses, $deft_status_options);

		$vars['sections'] = array(
			array(
				array(
					'title' => 'channel_description',
					'desc' => 'channel_description_desc',
					'fields' => array(
						'channel_description' => array(
							'type' => 'textarea',
							'value' => $channel->channel_description
						)
					)
				),
				array(
					'title' => 'xml_language',
					'desc' => 'xml_language_desc',
					'fields' => array(
						'channel_lang' => array(
							'type' => 'dropdown',
							'choices' => ee()->lang->language_pack_names(),
							'value' => $channel->channel_lang ?: 'english'
						)
					)
				),
			),
			'url_path_settings' => array(
				array(
					'title' => 'channel',
					'desc' => 'channel_url_desc',
					'fields' => array(
						'channel_url' => array(
							'type' => 'text',
							'value' => $channel->channel_url
						)
					)
				),
				array(
					'title' => 'comment_form',
					'desc' => 'comment_form_desc',
					'fields' => array(
						'comment_url' => array(
							'type' => 'text',
							'value' => $channel->comment_url
						)
					)
				),
				array(
					'title' => 'search_results',
					'desc' => 'search_results_desc',
					'fields' => array(
						'search_results_url' => array(
							'type' => 'text',
							'value' => $channel->search_results_url
						)
					)
				),
				array(
					'title' => 'rss_feed',
					'desc' => 'rss_feed_desc',
					'fields' => array(
						'rss_url' => array(
							'type' => 'text',
							'value' => $channel->rss_url
						)
					)
				),
				array(
					'title' => 'live_look_template',
					'desc' => 'live_look_template_desc',
					'fields' => array(
						'live_look_template' => array(
							'type' => 'dropdown',
							'choices' => $live_look_template_options,
							'value' => $channel->live_look_template
						)
					)
				)
			),
			'channel_defaults' => array(
				array(
					'title' => 'default_title',
					'desc' => 'default_title_desc',
					'fields' => array(
						'default_entry_title' => array(
							'type' => 'text',
							'value' => $channel->default_entry_title
						)
					)
				),
				array(
					'title' => 'url_title_prefix',
					'desc' => 'url_title_prefix_desc',
					'fields' => array(
						'url_title_prefix' => array(
							'type' => 'text',
							'value' => $channel->url_title_prefix
						)
					)
				),
				array(
					'title' => 'default_status',
					'desc' => 'default_status_desc',
					'fields' => array(
						'deft_status' => array(
							'type' => 'dropdown',
							'choices' => $deft_status_options,
							'value' => $channel->deft_status
						)
					)
				),
				array(
					'title' => 'default_category',
					'desc' => 'default_category_desc',
					'fields' => array(
						'deft_category' => array(
							'type' => 'dropdown',
							'choices' => $deft_category_options,
							'value' => $channel->deft_category
						)
					)
				),
				array(
					'title' => 'search_excerpt',
					'desc' => 'search_excerpt_desc',
					'fields' => array(
						'search_excerpt' => array(
							'type' => 'dropdown',
							'choices' => $search_excerpt_options,
							'value' => $channel->search_excerpt
						)
					)
				)
			),
			'publishing' => array(
				array(
					'title' => 'html_formatting',
					'desc' => 'html_formatting_desc',
					'fields' => array(
						'channel_html_formatting' => array(
							'type' => 'dropdown',
							'choices' => $channel_html_formatting_options,
							'value' => $channel->channel_html_formatting
						)
					)
				),
				array(
					'title' => 'convert_image_urls',
					'desc' => 'convert_image_urls_desc',
					'fields' => array(
						'channel_allow_img_urls' => array(
							'type' => 'yes_no',
							'value' => $channel->channel_allow_img_urls
						)
					)
				),
				array(
					'title' => 'convert_urls_emails_to_links',
					'desc' => 'convert_urls_emails_to_links_desc',
					'fields' => array(
						'channel_auto_link_urls' => array(
							'type' => 'yes_no',
							'value' => $channel->channel_auto_link_urls
						)
					)
				),
				array(
					'title' => 'allow_rich_text_editing',
					'desc' => 'allow_rich_text_editing_desc',
					'fields' => array(
						'show_button_cluster' => array(
							'type' => 'yes_no',
							'value' => $channel->show_button_cluster
						)
					)
				)
			),
			'channel_form' => array(
				array(
					'title' => 'default_status',
					'desc' => 'channel_form_status_desc',
					'fields' => array(
						'default_status' => array(
							'type' => 'dropdown',
							'choices' => $channel_form_statuses,
							'value' => $channel_form->default_status
						)
					)
				),
				array(
					'title' => 'channel_form_default_author',
					'desc' => 'channel_form_default_author_desc',
					'fields' => array(
						'default_author' => array(
							'type' => 'dropdown',
							'choices' => $all_authors,
							'value' => $channel_form->default_author
						)
					)
				),
				array(
					'title' => 'allow_guest_submission',
					'desc' => 'allow_guest_submission_desc',
					'fields' => array(
						'allow_guest_posts' => array(
							'type' => 'yes_no',
							'value' => $channel_form->allow_guest_posts
						)
					)
				)
			),
			'versioning' => array(
				array(
					'title' => 'enable_versioning',
					'desc' => 'enable_versioning_desc',
					'fields' => array(
						'enable_versioning' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => $channel->enable_versioning
						)
					)
				),
				array(
					'title' => 'max_versions',
					'desc' => 'max_versions_desc',
					'fields' => array(
						'max_revisions' => array(
							'type' => 'text',
							'value' => $channel->max_revisions,
							'note' => form_label(
								form_checkbox('clear_versioning_data', 'y')
								.lang('clear_versioning_data')
							)
						)
					)
				)
			),
			'notifications' => array(
				array(
					'title' => 'enable_author_notification',
					'desc' => 'enable_author_notification_desc',
					'fields' => array(
						'comment_notify_authors' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => $channel->comment_notify_authors
						)
					)
				),
				array(
					'title' => 'enable_channel_entry_notification',
					'desc' => 'enable_channel_entry_notification_desc',
					'fields' => array(
						'channel_notify' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => $channel->channel_notify
						),
						'channel_notify_emails' => array(
							'type' => 'text',
							'value' => $channel->channel_notify_emails
						)
					)
				),
				array(
					'title' => 'enable_comment_notification',
					'desc' => 'enable_comment_notification_desc',
					'fields' => array(
						'comment_notify' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => $channel->comment_notify
						),
						'comment_notify_emails' => array(
							'type' => 'text',
							'value' => $channel->comment_notify_emails
						)
					)
				)
			),
			'commenting' => array(
				array(
					'title' => 'allow_comments',
					'desc' => 'allow_comments_desc',
					'fields' => array(
						'comment_system_enabled' => array(
							'type' => 'yes_no',
							'value' => $channel->comment_system_enabled,
							'note' => form_label(
								form_checkbox('apply_comment_enabled_to_existing', 'y')
								.lang('apply_comment_enabled_to_existing')
							)
						)
					)
				),
				array(
					'title' => 'allow_comments_checked',
					'desc' => 'allow_comments_checked_desc',
					'fields' => array(
						'deft_comments' => array(
							'type' => 'yes_no',
							'value' => $channel->deft_comments
						)
					)
				),
				array(
					'title' => 'require_membership',
					'desc' => 'require_membership_desc',
					'fields' => array(
						'comment_require_membership' => array(
							'type' => 'yes_no',
							'value' => $channel->comment_require_membership
						)
					)
				),
				array(
					'title' => 'require_email',
					'desc' => 'require_email_desc',
					'fields' => array(
						'comment_require_email' => array(
							'type' => 'yes_no',
							'value' => $channel->comment_require_email
						)
					)
				),
				array(
					'title' => 'moderate_comments',
					'desc' => 'moderate_comments_desc',
					'fields' => array(
						'comment_moderate' => array(
							'type' => 'yes_no',
							'value' => $channel->comment_moderate
						)
					)
				),
				array(
					'title' => 'max_characters',
					'desc' => 'max_characters_desc',
					'fields' => array(
						'comment_max_chars' => array(
							'type' => 'text',
							'value' => $channel->comment_max_chars
						)
					)
				),
				array(
					'title' => 'comment_time_limit',
					'desc' => 'comment_time_limit_desc',
					'fields' => array(
						'comment_timelock' => array(
							'type' => 'text',
							'value' => $channel->comment_timelock
						)
					)
				),
				array(
					'title' => 'comment_expiration',
					'desc' => 'comment_expiration_desc',
					'fields' => array(
						'comment_expiration' => array(
							'type' => 'text',
							'value' => $channel->comment_expiration,
							'note' => form_label(
								form_checkbox('apply_expiration_to_existing', 'y')
								.lang('apply_expiration_to_existing')
							)
						)
					)
				),
				array(
					'title' => 'text_formatting',
					'desc' => 'text_formatting_desc',
					'fields' => array(
						'comment_text_formatting' => array(
							'type' => 'dropdown',
							'choices' => $comment_text_formatting_options,
							'value' => $channel->comment_text_formatting
						)
					)
				),
				array(
					'title' => 'html_formatting',
					'desc' => 'html_formatting_desc',
					'fields' => array(
						'comment_html_formatting' => array(
							'type' => 'dropdown',
							'choices' => $comment_html_formatting_options,
							'value' => $channel->comment_html_formatting
						)
					)
				),
				array(
					'title' => 'convert_image_urls',
					'desc' => 'comment_convert_image_urls_desc',
					'fields' => array(
						'comment_allow_img_urls' => array(
							'type' => 'yes_no',
							'value' => $channel->comment_allow_img_urls
						)
					)
				),
				array(
					'title' => 'convert_urls_emails_to_links',
					'desc' => 'comment_convert_urls_emails_to_links_desc',
					'fields' => array(
						'comment_auto_link_urls' => array(
							'type' => 'yes_no',
							'value' => $channel->comment_auto_link_urls
						)
					)
				)
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				'field' => 'channel_description',
				'label' => 'lang:channel_description',
				'rules' => 'strip_tags|valid_xss_check'
			),
			array(
				'field' => 'channel_url',
				'label' => 'lang:channel',
				'rules' => 'trim|strip_tags|valid_xss_check'
			),
			array(
				'field' => 'comment_url',
				'label' => 'lang:comment_form',
				'rules' => 'trim|strip_tags|valid_xss_check'
			),
			array(
				'field' => 'search_results_url',
				'label' => 'lang:search_results',
				'rules' => 'trim|strip_tags|valid_xss_check'
			),
			array(
				'field' => 'rss_url',
				'label' => 'lang:rss_feed',
				'rules' => 'trim|strip_tags|valid_xss_check'
			),
			array(
				'field' => 'default_entry_title',
				'label' => 'lang:default_title',
				'rules' => 'valid_xss_check'
			),
			array(
				'field' => 'url_title_prefix',
				'label' => 'lang:url_title_prefix',
				'rules' => 'strtolower|trim|strip_tags|valid_xss_check|callback__validPrefix'
			),
			array(
				'field' => 'max_revisions',
				'label' => 'lang:max_versions',
				'rules' => 'trim|integer'
			),
			array(
				'field' => 'channel_notify_emails',
				'label' => 'lang:enable_channel_entry_notification',
				'rules' => 'trim|valid_emails'
			),
			array(
				'field' => 'comment_notify_emails',
				'label' => 'lang:enable_comment_notification',
				'rules' => 'trim|valid_emails'
			),
			array(
				'field' => 'comment_max_chars',
				'label' => 'lang:max_characters',
				'rules' => 'trim|integer'
			),
			array(
				'field' => 'comment_timelock',
				'label' => 'lang:comment_time_limit',
				'rules' => 'trim|integer'
			),
			array(
				'field' => 'comment_expiration',
				'label' => 'lang:comment_expiration',
				'rules' => 'trim|integer'
			)
		));

		ee()->form_validation->validateNonTextInputs($vars['sections']);

		$base_url = cp_url('channels/settings/'.$channel_id);

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$this->saveChannelSettings($channel_id, $vars['sections']);

			ee()->view->set_message('success', lang('channel_saved'), lang('channel_saved_desc'), TRUE);

			ee()->functions->redirect(cp_url('channels/settings/' . $channel_id));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee()->view->set_message('issue', lang('channel_not_saved'), lang('channel_not_saved_desc'));
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->base_url = $base_url;
		ee()->view->cp_page_title = $channel->channel_title . ' &mdash; ' . lang('channel_settings');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->set_breadcrumb(cp_url('channels'), lang('channels'));

		ee()->cp->render('settings/form', $vars);
	}

	/**
	 * Custom validator for URL title prefix
	 */
	public function _validPrefix($str)
	{
		if ($str == '')
		{
			return TRUE;
		}

		ee()->form_validation->set_message('_validPrefix', lang('invalid_url_title_prefix'));

		return preg_match('/^[\w\-]+$/', $str) ? TRUE : FALSE;
	}

	/**
	 * POST handler for saving channel settings
	 *
	 * @param	int	$channel_id	ID of channel to save settings for
	 */
	private function saveChannelSettings($channel_id, $sections)
	{
		if (isset($_POST['comment_expiration']) && $_POST['comment_expiration'] == '')
		{
			$_POST['comment_expiration'] = 0;
		}

		ee()->load->model('channel_model');

		if (ee()->input->post('apply_comment_enabled_to_existing'))
		{
			if (ee()->input->post('comment_system_enabled') == 'y')
			{
				ee()->channel_model->update_comments_allowed($channel_id, 'y');
			}
			elseif ($this->input->post('comment_system_enabled') == 'n')
			{
				ee()->channel_model->update_comments_allowed($channel_id, 'n');
			}
		}

		if (ee()->input->post('apply_expiration_to_existing'))
		{
			if (ee()->input->post('comment_expiration') == 0)
			{
				ee()->channel_model->update_comment_expiration($channel_id, 0, TRUE);
			}
			else
			{
				ee()->channel_model->update_comment_expiration(
					$channel_id,
					ee()->input->post('comment_expiration') * 86400
				);
			}
		}

		if (ee()->input->post('clear_versioning_data'))
		{
			ee()->channel_model->clear_versioning_data($channel_id);
		}

		// Only one possible is revisions- enabled or disabled.
		// We treat as installed/not and delete the whole tab.
		ee()->load->library('layout');
		ee()->layout->sync_layout($_POST, $channel_id);

		// Make sure we only got the fields we asked for
		foreach ($sections as $settings)
		{
			foreach ($settings as $setting)
			{
				foreach ($setting['fields'] as $field_name => $field)
				{
					$fields[$field_name] = ee()->input->post($field_name);
				}
			}
		}

		$channel = ee('Model')->get('Channel', $channel_id)->first();

		foreach ($fields as $key => $value)
		{
			if ($channel->hasProperty($key))
			{
				$channel->$key = $value;
			}
		}

		if ( ! $channel_form = $channel->getChannelFormSettings())
		{
			$channel_form = ee('Model')->make('ChannelFormSettings');
			$channel_form->site_id = $channel->site_id;
		}

		$channel_form->default_status = $fields['default_status'];
		$channel_form->allow_guest_posts = $fields['allow_guest_posts'];
		$channel_form->default_author = $fields['default_author'];
		$channel->setChannelFormSettings($channel_form);
		$channel->save();
	}
}
// EOF
