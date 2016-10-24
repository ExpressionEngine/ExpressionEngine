<?php

namespace EllisLab\ExpressionEngine\Controller\Settings;

use EllisLab\ExpressionEngine\Library\CP\Table;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP HTML Buttons Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Buttons extends Settings {

	private $base_url = 'settings/buttons';

	// The current HTMLButton object
	private $button;

	public function __construct()
	{
		parent::__construct();

		ee()->lang->load('admin_content');

		// load the predefined buttons
		$button_config = ee()->config->loadFile('html_buttons');
		$this->predefined = $button_config['buttons'];

		$this->index_url = $this->base_url;
		$this->base_url = ee('CP/URL')->make($this->base_url);
	}

	public function index()
	{
		$table = ee('CP/Table', array(
			'sortable' => FALSE,
			'reorder' => TRUE
		));
		$rows = array();
		$data = array();

		// Default HTML buttons simply have a member ID of 0
		$buttons = ee('Model')->get("HTMLButton")
					->filter('member_id', 0)
					->filter('site_id', ee()->config->item('site_id'))
					->order('tag_order', 'asc')
					->all();

		foreach ($buttons as $button)
		{
			$name = (strpos($button->classname, 'html-') !== 0) ? htmlentities($button->tag_name) : '';

			$preview = array('toolbar_items' => array(
				$button->classname => array(
					'href' => ee('CP/URL')->make('settings/buttons/edit/' . $button->id),
					'title' => lang($button->tag_name),
					'content' => $name . form_hidden('order[]', $button->id)
				)
			));
			$toolbar = array('toolbar_items' => array(
				'edit' => array(
					'href' => ee('CP/URL')->make('settings/buttons/edit/' . $button->id),
					'title' => strtolower(lang('edit'))
				)
			));

			$columns = array(
				'preview' => $preview,
				'tag_name' => lang($button->tag_name),
				'accesskey' => $button->accesskey,
				$toolbar,
				array(
					'name' => 'selection[]',
					'value' => $button->id,
					'data'	=> array(
						'confirm' => lang('html_button') . ': <b>' . htmlentities($button->tag_name, ENT_QUOTES, 'UTF-8') . '</b>'
					)
				)
			);

			$attrs = array();

			if (ee()->session->flashdata('button_id') == $button->id)
			{
				$attrs = array('class' => 'selected');
			}

			$rows[] = array(
				'attrs' => $attrs,
				'columns' => $columns
			);
		}

		$table->setColumns(
			array(
				'preview' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				'tag_name',
				'accesskey',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);

		$table->setNoResultsText('no_search_results');
		$table->setData($rows);

		$data['table'] = $table->viewData($this->base_url);
		$data['new'] = ee('CP/URL')->make('settings/buttons/create');
		$data['form_url'] = ee('CP/URL')->make('settings/buttons/delete');
		$data['table']['action_content'] = $this->predefined();

		ee()->javascript->set_global('lang.remove_confirm', lang('html_buttons') . ': <b>### ' . lang('html_buttons') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/confirm_remove',
				'cp/members/html_button_reorder',
			),
			'plugin' => array(
				'ee_table_reorder'
			)
		));

		$reorder_ajax_fail = ee('CP/Alert')->makeBanner('reorder-ajax-fail')
			->asIssue()
			->canClose()
			->withTitle(lang('html_button_ajax_reorder_fail'))
			->addToBody(lang('html_button_ajax_reorder_fail_desc'));

		ee()->javascript->set_global('html_buttons.reorder_url', ee('CP/URL')->make('settings/buttons/order/')->compile());
		ee()->javascript->set_global('alert.reorder_ajax_fail', $reorder_ajax_fail->render());

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('html_buttons');
		ee()->cp->render('account/buttons', $data);
	}

	/**
	 * Create Button
	 *
	 * @access public
	 * @return void
	 */
	public function create($preset = '')
	{
		$this->base_url = ee('CP/URL')->make($this->index_url . '/create');

		$this->button = ee('Model')->make('HTMLButton');

		$last_button = ee('Model')->get('HTMLButton')
			->fields('tag_order')
			->filter('site_id', ee()->config->item('site_id'))
			->order('tag_order', 'desc')
			->first();

		$this->button->tag_order = $last_button->tag_order + 1;

		$values = array();

		if (isset($this->predefined[$preset]))
		{
			$this->base_url = ee('CP/URL')->make($this->index_url . '/create/' . $preset);
			$values = $this->predefined[$preset];
			$this->button->classname = $values['classname'];
		}

		$vars['cp_page_title'] = lang('create_html_button');

		$this->form($vars, $values);
	}

	/**
	 * Edit Button
	 *
	 * @param int $id  The ID of the button to be updated
	 * @access public
	 * @return void
	 */
	public function edit($id)
	{
		$this->base_url = ee('CP/URL')->make($this->index_url . "/edit/$id");

		$vars = array(
			'cp_page_title' => lang('edit_html_button')
		);

		$this->button = ee('Model')->get('HTMLButton', $id)->first();

		$this->form($vars, $this->button->getValues());
	}

	/**
	 * Delete Buttons
	 *
	 * @access public
	 * @return void
	 */
	public function delete()
	{
		$selection = $this->input->post('selection');
		$buttons = ee('Model')->get("HTMLButton", $selection)
					->filter('member_id', 0)
					->all();

		$buttons->delete();

		ee('CP/Alert')->makeInline('html_buttons')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('html_buttons_removed'))
			->defer();

		ee()->functions->redirect(ee('CP/URL')->make($this->index_url));
	}

	public function order()
	{
		parse_str(ee()->input->post('order'), $order);
		$order = $order['order'];
		$position = 0;

		if (is_array($order))
		{
			foreach ($order as $id)
			{
				$button = ee('Model')->get('HTMLButton', $id)->first();
				$button->tag_order = $position;
				$button->save();
				$position++;
			}
		}

		return TRUE;
	}

	/**
	 * Save HTMLButtons
	 *
	 * @access private
	 * @return void
	 */
	private function saveButtons($form)
	{
		foreach ($form['sections'][0] as $sections)
		{
			foreach ($sections['fields'] as $field => $options)
			{
				$this->button->$field = ee()->input->post($field);
			}
		}

		$this->button->save();

		ee()->session->set_flashdata('button_id', $this->button->id);

		return TRUE;
	}

	/**
	 * Display a generic form for creating/editing a HTMLButton
	 *
	 * @param mixed $vars
	 * @param array $values
	 * @access private
	 * @return void
	 */
	private function form($vars, $values = array())
	{
		$name = isset($values['tag_name']) ? $values['tag_name']: '';
		$open = isset($values['tag_open']) ? $values['tag_open']: '';
		$close = isset($values['tag_close']) ? $values['tag_close']: '';
		$shortcut = isset($values['accesskey']) ? $values['accesskey']: '';

		$vars['sections'] = array(
			array(
				array(
					'title' => 'tag_name',
					'fields' => array(
						'tag_name' => array('type' => 'text', 'value' => $name, 'required' => TRUE)
					)
				),
				array(
					'title' => 'tag_open',
					'desc' => 'tag_open_desc',
					'fields' => array(
						'tag_open' => array('type' => 'text', 'value' => $open, 'required' => TRUE)
					)
				),
				array(
					'title' => 'tag_close',
					'desc' => 'tag_close_desc',
					'fields' => array(
						'tag_close' => array('type' => 'text', 'value' => $close, 'required' => TRUE)
					)
				),
				array(
					'title' => 'accesskey',
					'desc' => 'accesskey_desc',
					'fields' => array(
						'accesskey' => array('type' => 'text', 'value' => $shortcut)
					)
				)
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'tag_name',
				 'label'   => 'lang:tag_name',
				 'rules'   => 'required|valid_xss_check'
			),
			array(
				 'field'   => 'tag_open',
				 'label'   => 'lang:tag_open',
				 'rules'   => 'required'
			),
			array(
				 'field'   => 'accesskey',
				 'label'   => 'lang:accesskey',
				 'rules'   => 'valid_xss_check'
			)
		));

		$action = $this->button->isNew() ? 'create' : 'edit';

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			if ($this->saveButtons($vars))
			{
				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang($action . '_html_buttons_success'))
					->addToBody(sprintf(lang($action . '_html_buttons_success_desc'), $this->button->tag_name))
					->defer();

				ee()->functions->redirect(ee('CP/URL')->make($this->index_url));
			}
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang($action . '_html_buttons_error'))
				->addToBody(lang($action . '_html_buttons_error_desc'))
				->now();
		}

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text = sprintf(lang('btn_save'), lang('html_button'));
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}

	private function predefined()
	{
		$buttons = array();
		$result = "<b>" . lang('add_preset_button') . "</b>";

		foreach ($this->predefined as $name => $button)
		{
			$current = array(
				'href' => ee('CP/URL')->make('settings/buttons/create/' . $name),
				'title' => $name,
				'data-accesskey' => $button['accesskey'],
			);
			if (strpos($button['classname'], 'html-') !== 0)
			{
				$current['content'] = $name;
				$buttons[$button['tag_name']] = $current;
			}
			else
			{
				$buttons[$button['classname']] = $current;
			}
		}

		$result .= ee('View')->make('ee:_shared/toolbar')->render(array('toolbar_items' => $buttons));
		return $result;
	}

}
// END CLASS

// EOF
