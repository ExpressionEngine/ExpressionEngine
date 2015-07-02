<?php

namespace EllisLab\ExpressionEngine\Controllers\Members;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Library\CP\Table;

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

		$this->base_url = ee('CP/URL', 'members/fields');
	}

	/**
	 * Field List Index
	 */
	public function index()
	{
		$table = ee('CP/Table', array(
			'sortable' => FALSE,
			'reorder' => TRUE,
			'save' => ee('CP/URL', "members/fields/order")
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
					'href' => ee('CP/URL', 'members/fields/edit/', array('field' => $field->m_field_id)),
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
		$data['form_url'] = ee('CP/URL', 'members/fields/delete');
		$data['new'] = ee('CP/URL', 'members/fields/create');

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
		$this->form();
	}

	public function edit($id)
	{
		$this->form($id);
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

	private function form($field_id = NULL)
	{
		if ($field_id)
		{
			$field = ee('Model')->get('MemberField', array($field_id))->first();

			ee()->view->save_btn_text = 'btn_edit_field';
			ee()->view->cp_page_title = lang('edit_member_field');
			ee()->view->base_url = ee('CP/URL', 'members/fields/edit/' . $field_id);
		}
		else
		{
			// Only auto-complete field short name for new fields
			ee()->cp->add_js_script('plugin', 'ee_url_title');
			ee()->javascript->output('
				$("input[name=field_label]").bind("keyup keydown", function() {
					$(this).ee_url_title("input[name=field_name]");
				});
			');

			$field = ee('Model')->make('MemberField');
			$field->field_type = 'text';

			ee()->view->save_btn_text = 'btn_create_field';
			ee()->view->cp_page_title = lang('create_member_field');
			ee()->view->base_url = ee('CP/URL', 'members/fields/create');
		}

		if ( ! $field)
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->lang->loadfile('admin_content');

		$vars['sections'] = array(
			array(
				array(
					'title' => 'type',
					'desc' => '',
					'fields' => array(
						'm_field_type' => array(
							'type' => 'select',
							'choices' => array(
								'text'     => lang('text_input'),
								'textarea' => lang('textarea'),
								'select'   => lang('select_dropdown')
							),
							'group_toggle' => array(
								'text' => 'text',
								'textarea' => 'textarea',
								'select' => 'select'
							),
							'value' => $field->field_type
						)
					)
				),
				array(
					'title' => 'label',
					'desc' => 'field_label_desc',
					'fields' => array(
						'm_field_label' => array(
							'type' => 'text',
							'value' => $field->field_label,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'short_name',
					'desc' => 'field_short_name_desc',
					'fields' => array(
						'm_field_name' => array(
							'type' => 'text',
							'value' => $field->field_name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'require_field',
					'desc' => 'cat_require_field_desc',
					'fields' => array(
						'm_field_required' => array(
							'type' => 'yes_no',
							'value' => $field->field_required
						)
					)
				)
			)
		);

		$vars['sections'] += $field->getSettingsForm();

		// These are currently the only fieldtypes we allow; get their settings forms
		foreach (array('text', 'textarea', 'select') as $fieldtype)
		{
			if ($field->field_type != $fieldtype)
			{
				$dummy_field = ee('Model')->make('MemberField');
				$dummy_field->field_type = $fieldtype;
				$vars['sections'] += $dummy_field->getSettingsForm();
			}
		}

		if ( ! empty($_POST))
		{
			$field->set($_POST);
			$field->field_fmt = isset($_POST['field_fmt']) ? $_POST['field_fmt'] : NULL;
			$result = $field->validate();

			if (AJAX_REQUEST)
			{
				$field = ee()->input->post('ee_fv_field');

				if ($result->hasErrors($field))
				{
					ee()->output->send_ajax_response(array('error' => $result->renderError($field)));
				}
				else
				{
					ee()->output->send_ajax_response('success');
				}
				exit;
			}

			if ($result->isValid())
			{
				$field_id = $field->save()->getId();

				ee('Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('member_field_saved'))
					->addToBody(lang('member_field_saved_desc'))
					->defer();

				ee()->functions->redirect(ee('CP/URL', '/members/fields/edit/' . $field_id));
			}
			else
			{
				ee()->load->library('form_validation');
				ee()->form_validation->_error_array = $result->renderErrors();
				ee('Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('member_field_not_saved'))
					->addToBody(lang('member_field_not_saved_desc'))
					->now();
			}
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->set_breadcrumb(ee('CP/URL', 'members/fields/edit'), lang('member_fields'));

		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/form_group'),
		));

		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

/* End of file Members.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Members/Fields.php */
