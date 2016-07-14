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
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class Quicklinks extends Settings {

	private $base_url = 'members/profile/quicklinks';

	public function __construct()
	{
		parent::__construct();
		ee()->load->model('member_model');
		$this->quicklinks = ee()->member_model->get_member_quicklinks($this->member->member_id);
		$this->index_url = $this->base_url;
		$this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);
	}

	/**
	 * Quicklinks index
	 */
	public function index()
	{
		$data = array(
			'table' => $this->makeTable(),
			'new' => ee('CP/URL')->make('members/profile/quicklinks/create', $this->query_string),
			'form_url' => ee('CP/URL')->make('members/profile/quicklinks/delete', $this->query_string)
		);

		ee()->javascript->set_global('lang.remove_confirm', lang('quick_links') . ': <b>### ' . lang('quick_links') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/confirm_remove',
				'cp/members/quick_links_reorder',
			),
			'plugin' => array(
				'ee_table_reorder',
			),
		));

		$reorder_ajax_fail = ee('CP/Alert')->makeBanner('reorder-ajax-fail')
			->asIssue()
			->canClose()
			->withTitle(lang('quick_links_ajax_reorder_fail'))
			->addToBody(lang('quick_links_ajax_reorder_fail_desc'));

		ee()->javascript->set_global('quick_links.reorder_url', ee('CP/URL')->make('members/profile/quicklinks/order/', $this->query_string)->compile());
		ee()->javascript->set_global('alert.reorder_ajax_fail', $reorder_ajax_fail->render());

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
		ee()->cp->set_breadcrumb($this->base_url, lang('quick_links'));
		$this->base_url = ee('CP/URL')->make($this->index_url . '/create', $this->query_string);

		$vars = array(
			'cp_page_title' => lang('create_quick_link'),
		);

		$values = array(
			'name' => ee()->input->get('name'),
			'url'  => ee('CP/URL')->decodeUrl(ee()->input->get('url'))
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

		$this->form($vars, $values);
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
		ee()->cp->set_breadcrumb($this->base_url, lang('quick_links'));
		$this->base_url = ee('CP/URL')->make($this->index_url . "/edit/$id", $this->query_string);

		$vars = array(
			'cp_page_title' => lang('edit_quick_link')
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
	 * @return void
	 */
	public function delete()
	{
		$selection = $this->input->post('selection');

		// re-index from 1 to match the array we get back from the member model
		$selection = array_combine(range(1, count($selection)), array_values($selection));
		$this->quicklinks = array_diff_key($this->quicklinks, array_flip($selection));
		$this->saveQuicklinks();

		ee()->functions->redirect(ee('CP/URL')->make($this->index_url, $this->query_string));
	}

	/**
	 * Reorder quicklinks
	 *
	 * @return Array Success or error array. On success returns the new quicklinks table
	 */
	public function order()
	{
		parse_str(ee()->input->post('order'), $order);
		$order = $order['order'];
		$position = 1;

		if (is_array($order))
		{
			foreach ($order as $id)
			{
				$this->quicklinks[$id]['order'] = $position;
				$position++;
			}
		}

		$this->saveQuicklinks();

		return array('success' => $this->makeTable());
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
		$orders = array();

		foreach ($this->quicklinks as $quicklink)
		{
			$orders[] = $quicklink['order'];
			$compiled[$quicklink['order']] = implode('|', $quicklink);
		}

		array_multisort($orders, $compiled, $this->quicklinks);

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
					'title' => 'name',
					'fields' => array(
						'name' => array('type' => 'text', 'value' => $name, 'required' => TRUE)
					)
				),
				array(
					'title' => 'link_url',
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
				ee()->functions->redirect(ee('CP/URL')->make($this->index_url, $this->query_string));
			}
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('settings_save_error'))
				->addToBody(lang('settings_save_error_desc'))
				->now();
		}

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text = sprintf(lang('btn_save'), lang('quick_link'));
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}

	/**
	 * Create the quicklinks table
	 */
	protected function makeTable()
	{
		$table = ee('CP/Table', array('reorder' => TRUE));
		$links = array();

		foreach ($this->quicklinks as $quicklink)
		{
			$edit_url = ee('CP/URL')->make('members/profile/quicklinks/edit/' . ($quicklink['order'] ?: 1), $this->query_string);

			$toolbar = array('toolbar_items' => array(
				'edit' => array(
					'href' => $edit_url,
					'title' => strtolower(lang('edit'))
				)
			));

			$links[] = array(
				'<a href="' . $edit_url . '">' . htmlentities($quicklink['title'], ENT_QUOTES, 'UTF-8') . '</a>' . form_hidden('order[]', $quicklink['order']),
				$toolbar,
				array(
					'name' => 'selection[]',
					'value' => $quicklink['order'],
					'data'	=> array(
						'confirm' => lang('quick_link') . ': <b>' . htmlentities($quicklink['title'], ENT_QUOTES, 'UTF-8') . '</b>'
					)
				)
			);
		}

		$table->setColumns(
			array(
				'name' => array(
					'encode' => FALSE
				),
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);

		$table->setNoResultsText('no_quick_links_found');
		$table->setData($links);

		return ee('View')->make('_shared/table')->render($table->viewData($this->base_url));
	}
}
// END CLASS

// EOF
