<?php

namespace EllisLab\ExpressionEngine\Controllers\Members;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Controllers\Members;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Member Fields Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Fields extends Members\Members {

	private $base_url;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		$this->base_url = new URL('members/fields', ee()->session->session_id());
	}

	/**
	 * Field List Index
	 */
	public function index()
	{
		$table = ee('CP/GridInput', array(
			'sortable' => FALSE,
			'reorder' => TRUE,
			'namespace' => FALSE,
			'input' => FALSE,
			'grid_remove' => FALSE,
			'grid_add' => FALSE,
			'save' => cp_url("members/fields/order")
		));

		$table->setColumns(
			array(
				'id' => array(
					'type'	=> Table::COL_ID
				),
				'name',
				'short_name',
				'type',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);

		$data = array();
		$fieldData = array();
		$total = ee()->api->get('MemberField')->count();
		$fields = ee()->api->get('MemberField')->order('m_field_order', 'asc')->all();

		foreach ($fields as $field)
		{
			$toolbar = array('toolbar_items' => array(
				'edit' => array(
					'href' => cp_url('members/fields/edit/', array('field' => $field->m_field_id)),
					'title' => strtolower(lang('edit'))
				)
			));

			$fieldData[] = array(
				'id' => $field->m_field_id,
				'm_field_name' => $field->m_field_name,
				'm_field_label' => "<var>{{$field->m_field_label}}</var>",
				'm_field_type' => $field->m_field_type,
				$toolbar,
				array(
					'name' => 'selection[]',
					'value' => $field->m_field_id,
					'data'	=> array(
						'confirm' => lang('field') . ': <b>' . htmlentities($field->m_field_name, ENT_QUOTES) . '</b>'
					)
				)
			);
		}

		$table->setNoResultsText('no_search_results');
		$table->setData($fieldData);
		$data['table'] = $table->viewData($this->base_url);
		$data['form_url'] = cp_url('members/fields/delete');
		$data['new'] = cp_url('members/fields/create');

		$base_url = $data['table']['base_url'];

		// Set search results heading
		if ( ! empty($data['table']['search']))
		{
			ee()->view->cp_heading = sprintf(
				lang('search_results_heading'),
				$data['table']['total_rows'],
				$data['table']['search']
			);
		}

		ee()->javascript->set_global('lang.remove_confirm', lang('members') . ': <b>### ' . lang('members') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/confirm_remove'),
		));

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('member_fields');
		ee()->cp->render('members/custom_profile_fields', $data);
	}

	public function create()
	{
		$vars = array(
			'cp_page_title' => lang('create_member_field'),
			'save_btn_text' => lang('save_member_field')
		);

		$this->form($vars);
	}

	public function edit()
	{
		$vars = array(
			'cp_page_title' => lang('create_member_field'),
			'save_btn_text' => lang('save_member_field')
		);

		$this->form($vars);
	}

	public function delete()
	{
		$selected = ee()->input->post('selection');
		ee()->api->get('MemberField', $selected)->delete();

		ee()->functions->redirect($this->base_url);
	}

	public function order()
	{
		$order = array_flip(ee()->input->post('order'));
		$ids = array_keys($order);
		$fields = ee()->api->get('MemberField', $ids)->all();

		foreach($fields as $field)
		{
			$field->m_field_order = $order[$field->m_field_id];
			$field->save();
		}
	}

	private function form($vars = array(), $values = array())
	{
		$vars['sections'] = array(
			array(
				array(
					'title' => 'type',
					'desc' => 'type_desc',
					'fields' => array(
						'type' => array(
							'type' => 'dropdown',
							'choices' => $field_types,
							'value' => element('type', $values)
						)
					)
				),
				array(
					'title' => 'label',
					'desc' => 'label_desc',
					'fields' => array(
						'label' => array('type' => 'text', 'value' => element('label', $values))
					)
				),
				array(
					'title' => 'short_name',
					'desc' => 'short_name_desc',
					'fields' => array(
						'short_name' => array('type' => 'text', 'value' => element('short_name', $values))
					)
				),
				array(
					'title' => 'instructions',
					'desc' => 'instructions_desc',
					'fields' => array(
						'instructions' => array('type' => 'textarea', 'value' => element('instructions', $values))
					)
				),
				array(
					'title' => 'require_field',
					'desc' => 'require_field_desc',
					'fields' => array(
						'require_field' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'yes',
								'n' => 'no'
							),
							'value' => element('require_field', $values)
						)
					)
				),
				array(
					'title' => 'include_in_search',
					'desc' => 'include_in_search_desc',
					'fields' => array(
						'include_in_search' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'yes',
								'n' => 'no'
							),
							'value' => element('include_in_search', $values)
						)
					)
				),
				array(
					'title' => 'hide_field',
					'desc' => 'hide_field_desc',
					'fields' => array(
						'hide_field' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'yes',
								'n' => 'no'
							),
							'value' => element('hide_field', $values)
						)
					)
				)
			),
			'field_options' => array(
				array(
					'title' => 'maximum_characters',
					'desc' => 'maximum_characters_desc',
					'fields' => array(
						'maximum_characters' => array('type' => 'text', 'value' => element('maximum_characters', $values))
					)
				),
				array(
					'title' => 'text_formatting',
					'desc' => 'text_formatting_desc',
					'fields' => array(
						'text_formatting' => array(
							'type' => 'dropdown',
							'choices' => $text_formatting,
							'value' => element('text_formatting', $values)
						)
					)
				),
				array(
					'title' => 'allow_override',
					'desc' => 'allow_override_desc',
					'fields' => array(
						'allow_override' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'yes',
								'n' => 'no'
							),
							'value' => element('allow_override', $values)
						)
					)
				),
				array(
					'title' => 'text_direction',
					'desc' => 'text_direction_desc',
					'fields' => array(
						'text_direction' => array(
							'type' => 'dropdown',
							'choices' => $text_direction,
							'value' => element('text_direction', $values)
						)
					)
				),
				array(
					'title' => 'allowed_content',
					'desc' => 'allowed_content_desc',
					'fields' => array(
						'allowed_content' => array(
							'type' => 'dropdown',
							'choices' => $allowed_content,
							'value' => element('allowed_content', $values)
						)
					)
				),
				array(
					'title' => 'field_tools',
					'desc' => 'field_tools_desc',
					'fields' => array(
						'field_tools' => array(
							'type' => 'checkbox',
							'choices' => array(
								'emoji' => 'emoji',
								'glossary' => 'glossary',
								'spellcheck' => 'spellcheck',
								'asset_manager' => 'asset_manager'
							),
							'value' => $settings
						),
					)
				),
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'name',
				 'label'   => 'lang:quicklink_name',
				 'rules'   => 'required|valid_xss_check'
			),
			array(
				 'field'   => 'url',
				 'label'   => 'lang:quicklink_url',
				 'rules'   => 'required|valid_xss_check'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			if ($this->saveQuicklinks())
			{
				ee()->functions->redirect(cp_url($this->index_url, $this->query_string));
			}
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee()->view->set_message('issue', lang('settings_save_error'), lang('settings_save_error_desc'));
		}

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text_working = 'btn_save_working';
		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

/* End of file Members.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Members/Fields.php */
