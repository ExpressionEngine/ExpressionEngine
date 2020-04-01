<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use EllisLab\ExpressionEngine\Library\CP\Table;

/**
 * Rich Text Editor Module control panel
 */
class Rte_mcp {

	public $name = 'Rte';

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	public function __construct()
	{
		// Let's make sure they're allowed...
		$this->_permissions_check();

		// Load it all
		ee()->load->helper('form');
		ee()->load->library('rte_lib');
		ee()->load->model('rte_tool_model');

		// set some properties
		$this->_base_url = ee('CP/URL')->make('addons/settings/rte');
		ee()->rte_lib->form_url = 'addons/settings/rte';

		// Delete missing tools
		ee()->rte_tool_model->delete_missing_tools();
	}

	/**
	 * Homepage
	 *
	 * @access	public
	 * @return	string The page
	 */
	public function index()
	{
		if ( ! empty($_POST))
		{
			$this->prefs_update();
		}

		ee()->load->model('rte_toolset_model');

		$toolsets = ee()->rte_toolset_model->get_toolset_list();

		// prep the Default Toolset dropdown
		$toolset_opts = array();

		$data = array();
		$toolset_id = ee()->session->flashdata('toolset_id');

		$default_toolset_id = ee()->config->item('rte_default_toolset_id');

		foreach ($toolsets as $t)
		{
			$url = ee('CP/URL')->make('addons/settings/rte/edit_toolset', array('toolset_id' => $t['toolset_id']));
			$toolset_name = htmlentities($t['name'], ENT_QUOTES, 'UTF-8');
			$checkbox = array(
				'name' => 'selection[]',
				'value' => $t['toolset_id'],
				'data'	=> array(
					'confirm' => lang('toolset') . ': <b>' . htmlentities($t['name'], ENT_QUOTES, 'UTF-8') . '</b>'
				)
			);

			$toolset_name = '<a href="' . $url->compile() . '">' . $toolset_name . '</a>';
			if ($default_toolset_id == $t['toolset_id'])
			{
				$toolset_name = '<span class="default">' . $toolset_name . ' ✱</span>';
				$checkbox['disabled'] = 'disabled';
			}
			$toolset = array(
				'tool_set' => $toolset_name,
				'status' => lang('disabled'),
				array('toolbar_items' => array(
						'edit' => array(
							'href' => $url,
							'title' => lang('edit'),
						)
					)
				),
				$checkbox
			);

			if ($t['enabled'] == 'y')
			{
				$toolset_opts[$t['toolset_id']] = htmlentities($t['name'], ENT_QUOTES, 'UTF-8');
				$toolset['status'] = lang('enabled');
			}

			$attrs = array();

			if ($toolset_id && $t['toolset_id'] == $toolset_id)
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs'		=> $attrs,
				'columns'	=> $toolset
			);
		}

		$vars = array(
			'cp_page_title' => lang('rte_module_name') . ' ' . lang('configuration'),
			'save_btn_text' => 'btn_save_settings',
			'save_btn_text_working' => 'btn_saving',
			'sections' => array(
				array(
					array(
						'title' => 'enable_rte',
						'desc' => 'enable_rte_desc',
						'fields' => array(
							'rte_enabled' => array('type' => 'yes_no')
						)
					),
					array(
						'title' => 'default_toolset',
						'desc' => '',
						'fields' => array(
							'rte_default_toolset_id' => array(
								'type' => 'radio',
								'choices' => $toolset_opts,
								'no_results' => [
									'text' => sprintf(lang('no_found'), lang('toolsets'))
								]
							)
						)
					)
				)
			)
		);

		$table = ee('CP/Table', array('autosort' => TRUE, 'autosearch' => FALSE, 'limit' => 20));
		$table->setColumns(
			array(
				'tool_set' => array(
					'encode' => FALSE
				),
				'status',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);

		$table->setNoResultsText('no_tool_sets');
		$table->setData($data);

		$vars['table'] = $table->viewData($this->_base_url);
		$vars['base_url'] = clone $vars['table']['base_url'];

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
			$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
				->perPage($vars['table']['limit'])
				->currentPage($vars['table']['page'])
				->render($vars['base_url']);
		}

		ee()->javascript->set_global('lang.remove_confirm', lang('toolset') . ': <b>### ' . lang('toolsets') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove'),
		));

		// return the page
		return ee('View')->make('rte:index')->render($vars);
	}


	/**
	 * Update prefs form action
	 *
	 * @return	void
	 */
	public function prefs_update()
	{
		// set up the validation
		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(
			'rte_enabled',
			lang('enabled_question'),
			'required|enum[y,n]'
		);

		$toolids = array();
		ee()->load->model('rte_toolset_model');
		foreach (ee()->rte_toolset_model->get_toolset_list() as $toolset)
		{
			$toolids[] = $toolset['toolset_id'];
		}

		ee()->form_validation->set_rules(
			'rte_default_toolset_id',
			lang('default_toolset'),
			'required|is_numeric|enum[' . implode(',', $toolids) . ']'
		);

		if (ee()->form_validation->run())
		{
			// update the prefs
			$this->_do_update_prefs();
			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('settings_saved'))
				->addToBody(lang('settings_saved_desc'))
				->now();
		}
		else
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('settings_error'))
				->addToBody(lang('settings_error_desc'))
				->now();
		}
	}

	/**
	 * Provides New Toolset Screen HTML
	 *
	 * @access	public
	 * @param	int $toolset_id The Toolset ID to be edited (optional)
	 * @return	string The page
	 */
	public function new_toolset()
	{
		if ( ! ee()->cp->allowed_group('can_upload_new_toolsets'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		return array(
			'body'			=> ee()->rte_lib->edit_toolset(0),
			'heading'		=> lang('create_tool_set_header'),
			'breadcrumb' 	=> array(
				ee('CP/URL')->make('addons/settings/rte')->compile() => lang('rte_module_name') . ' ' . lang('configuration')
			)
		);
	}

	/**
	 * Provides Edit Toolset Screen HTML
	 *
	 * @access	public
	 * @param	int $toolset_id The Toolset ID to be edited (optional)
	 * @return	string The page
	 */
	public function edit_toolset($toolset_id = FALSE)
	{
		if ( ! ee()->cp->allowed_group('can_edit_toolsets'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		return array(
			'body'			=> ee()->rte_lib->edit_toolset($toolset_id),
			'heading'		=> lang('edit_tool_set_header'),
			'breadcrumb' 	=> array(
				ee('CP/URL')->make('addons/settings/rte')->compile() => lang('rte_module_name') . ' ' . lang('configuration')
			)
		);
	}

	/**
	 * Performs bulk actions (enable, disable, or remove) on tool sets
	 *
	 * @return void
	 */
	public function update_toolsets()
	{
		ee()->load->model('rte_toolset_model');

		$action = ee()->input->post('bulk_action');
		$selection = ee()->input->post('selection');
		$default_toolset_id = ee()->config->item('rte_default_toolset_id');

		$toolsets = array();

		foreach (ee()->rte_toolset_model->get_toolset_list() as $toolset)
		{
			$toolsets[$toolset['toolset_id']] = $toolset['name'];
		}

		$errors = array();
		$successes = array();

		switch ($action)
		{
			case 'enable':
				$message_title = 'toolsets_updated';
				$message_desc = 'toolsets_enabled';
				foreach ($selection as $toolset_id)
				{
					$saved = ee()->rte_toolset_model->save_toolset(array('enabled' => 'y'), $toolset_id);
					if ( ! $saved)
					{
						$errors[] = $toolsets[$toolset_id];
					}
					else
					{
						$successes[] = $toolsets[$toolset_id];
					}
				}
				break;

			case 'disable':
				$message_title = 'toolsets_updated';
				$message_desc = 'toolsets_disabled';
				foreach ($selection as $toolset_id)
				{
					if ($toolset_id == $default_toolset_id)
					{
						$errors[] = $toolsets[$toolset_id] . ' &mdash; ' . lang('cannot_disable_default_toolset');
					}
					else
					{
						$saved = ee()->rte_toolset_model->save_toolset(array('enabled' => 'n'), $toolset_id);
						if ( ! $saved)
						{
							$errors[] = $toolsets[$toolset_id];
						}
						else
						{
							$successes[] = $toolsets[$toolset_id];
						}
					}
				}
				break;

			case 'remove':
				if ( ! ee()->cp->allowed_group('can_delete_toolsets'))
				{
					show_error(lang('unauthorized_access'), 403);
				}

				$message_title = 'toolsets_removed';
				$message_desc = 'toolsets_removed_desc';
				foreach ($selection as $toolset_id)
				{
					if ($toolset_id == $default_toolset_id)
					{
						$errors[] = $toolsets[$toolset_id] . ' &mdash; ' . lang('cannot_remove_default_toolset');
					}
					else
					{
						$removed = ee()->rte_toolset_model->delete($toolset_id);
						if ( ! $removed)
						{
							$errors[] = $toolsets[$toolset_id];
						}
						else
						{
							$successes[] = $toolsets[$toolset_id];
						}
					}
				}
				break;
		}

		if ( ! empty($errors))
		{
			$errorAlert = ee('CP/Alert')->makeInline('toolsets-form')
				->asIssue()
				->withTitle(lang('toolset_error'))
				->addToBody(lang($action . '_fail_desc'))
				->addToBody($errors);
		}

		if (empty($successes) && ! empty($errors))
		{
			$errorAlert->defer();
		}
		else
		{
			$successAlert = ee('CP/Alert')->makeInline('toolsets-form')
				->asSuccess()
				->withTitle(lang($message_title))
				->addToBody(lang($action . '_success_desc'))
				->addToBody($successes);

			if (isset($errorAlert))
			{
				$successAlert->setSubAlert($errorAlert);
			}

			$successAlert->defer();
		}

		ee()->functions->redirect($this->_base_url);
	}

	/**
	 * Enables or disables a tool
	 *
	 * @access	public
	 * @return	void
	 */
	public function toggle_tool()
	{
		ee()->load->model('rte_tool_model');

		$tool_id = ee()->input->get_post('tool_id');
		$enabled = ee()->input->get_post('enabled') != 'n' ? 'y' :'n';

		if (ee()->rte_tool_model->save_tool(array('enabled' => $enabled), $tool_id))
		{
			ee()->session->set_flashdata('message_success', lang('tool_updated'));
		}
		else
		{
			ee()->session->set_flashdata('message_failure', lang('tool_update_failed'));
		}

		ee()->functions->redirect($this->_base_url);
	}

	/**
	 * Actual preference-updating code
	 *
	 * @access	private
	 * @return	void
	 */
	private function _do_update_prefs()
	{
		// update the config
		ee()->config->update_site_prefs(array(
			'rte_enabled'				=> ee()->input->get_post('rte_enabled'),
			'rte_default_toolset_id'	=> ee()->input->get_post('rte_default_toolset_id')
		));
	}

	/**
	 * Makes sure users can access a given method
	 *
	 * @access	private
	 * @return	void
	 */
	private function _permissions_check()
	{
		// super admins always can
		$can_access = (ee()->session->userdata('group_id') == '1');

		if ( ! $can_access)
		{
			// get the group_ids with access
			$result = ee()->db->select('module_member_groups.group_id')
				->from('module_member_groups')
				->join('modules', 'modules.module_id = module_member_groups.module_id')
				->where('modules.module_name',$this->name)
				->get();

			if ($result->num_rows())
			{
				foreach ($result->result_array() as $r)
				{
					if (ee()->session->userdata('group_id') == $r['group_id'])
					{
						$can_access = TRUE;
						break;
					}
				}
			}
		}

		if ( ! $can_access)
		{
			show_error(lang('unauthorized_access'), 403);
		}
	}

}
// END CLASS

// EOF
