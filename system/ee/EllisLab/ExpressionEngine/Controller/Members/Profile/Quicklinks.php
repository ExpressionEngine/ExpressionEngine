<?php

namespace EllisLab\ExpressionEngine\Controller\Members\Profile;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;

use EllisLab\ExpressionEngine\Library\CP\Table;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Member Profile Quicklinks Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Quicklinks extends Profile {

	private $base_url = 'members/profile/quicklinks';

	public function __construct()
	{
		parent::__construct();
		ee()->load->model('member_model');
		$this->quicklinks = ee()->member_model->get_member_quicklinks($this->member->member_id);
		$this->index_url = $this->base_url;
		$this->base_url = ee('CP/URL', $this->base_url, $this->query_string);
	}

	/**
	 * Quicklinks index
	 */
	public function index()
	{
		$table = ee('CP/Table');
		$links = array();
		$data = array();

		foreach ($this->quicklinks as $quicklink)
		{
			$toolbar = array('toolbar_items' => array(
				'edit' => array(
					'href' => ee('CP/URL', 'members/profile/quicklinks/edit/' . ($quicklink['order'] ?: 1), $this->query_string),
					'title' => strtolower(lang('edit'))
				)
			));

			$links[] = array(
				'name' => $quicklink['title'],
				$toolbar,
				array(
					'name' => 'selection[]',
					'value' => $quicklink['order'],
					'data'	=> array(
						'confirm' => lang('quick_link') . ': <b>' . htmlentities($quicklink['title'], ENT_QUOTES) . '</b>'
					)
				)
			);
		}

		$table->setColumns(
			array(
				'name',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);

		$table->setNoResultsText('no_search_results');
		$table->setData($links);

		$data['table'] = $table->viewData($this->base_url);
		$data['new'] = ee('CP/URL', 'members/profile/quicklinks/create', $this->query_string);
		$data['form_url'] = ee('CP/URL', 'members/profile/quicklinks/delete', $this->query_string);

		ee()->javascript->set_global('lang.remove_confirm', lang('quick_links') . ': <b>### ' . lang('quick_links') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/confirm_remove'),
		));

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('quick_links');
		ee()->cp->render('account/quicklinks', $data);
	}

	/**
	 * Create new quicklink
	 *
	 * @access public
	 * @return void
	 */
	public function create()
	{
		$this->base_url = ee('CP/URL', $this->index_url . '/create', $this->query_string);

		$vars = array(
			'cp_page_title' => lang('create_quick_link'),
			'save_btn_text' => lang('create_quick_link')
		);

		if ( ! empty($_POST))
		{
			$order = count($this->quicklinks) + 1;
			$id = $order;
			$this->quicklinks[$order] = array(
				'title' => ee()->input->post('name'),
				'link' => ee()->input->post('url'),
				'order' => $order
			);
		}

		$this->form($vars);
	}

	/**
	 * Edit quicklink
	 *
	 * @param int $id  The ID of the quicklink to be updated
	 * @access public
	 * @return void
	 */
	public function edit($id)
	{
		$this->base_url = ee('CP/URL', $this->index_url . "/edit/$id", $this->query_string);

		$vars = array(
			'cp_page_title' => lang('edit_quick_link'),
			'save_btn_text' => lang('save_quick_link')
		);

		$values = array(
			'name' => $this->quicklinks[$id]['title'],
			'url' => $this->quicklinks[$id]['link'],
			'order' => $this->quicklinks[$id]['order']
		);

		if ( ! empty($_POST))
		{
			$this->quicklinks[$id] = array(
				'title' => ee()->input->post('name'),
				'link' => ee()->input->post('url'),
				'order' => $id
			);
		}

		$this->form($vars, $values, $id);
	}

	/**
	 * Delete Quicklinks
	 *
	 * @access public
	 * @return void
	 */
	public function delete()
	{
		$selection = $this->input->post('selection');

		// re-index from 1 to match the array we get back from the member model
		$selection = array_combine(range(1, count($selection)), array_values($selection));
		$this->quicklinks = array_diff_key($this->quicklinks, array_flip($selection));
		$this->saveQuicklinks();

		ee()->functions->redirect(ee('CP/URL', $this->index_url, $this->query_string));
	}

	/**
	 * saveQuicklinks compiles the links and saves them for the current member
	 *
	 * @access private
	 * @return void
	 */
	private function saveQuicklinks()
	{
		$compiled = array();

		foreach ($this->quicklinks as $quicklink)
		{
			$compiled[] = implode('|', $quicklink);
		}

		$compiled = implode("\n", $compiled);
		$this->member->quick_links = $compiled;
		$this->member->save();

		return TRUE;
	}

	/**
	 * Display a generic form for creating/editing a Quicklink
	 *
	 * @param mixed $vars
	 * @param array $values
	 * @access private
	 * @return void
	 */
	private function form($vars, $values = array())
	{
		$name = isset($values['name']) ? $values['name']: '';
		$url = isset($values['url']) ? $values['url']: '';

		$vars['sections'] = array(
			array(
				array(
					'title' => 'link_title',
					'desc' => 'link_title_desc',
					'fields' => array(
						'name' => array('type' => 'text', 'value' => $name, 'required' => TRUE)
					)
				),
				array(
					'title' => 'link_url',
					'desc' => 'link_url_desc',
					'fields' => array(
						'url' => array('type' => 'text', 'value' => $url, 'required' => TRUE)
					)
				)
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
				ee()->functions->redirect(ee('CP/URL', $this->index_url, $this->query_string));
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

/* End of file Quicklinks.php */
/* Location: ./system/expressionengine/controllers/cp/Members/Profile/Quicklinks.php */
