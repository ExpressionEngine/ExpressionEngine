<?php

namespace EllisLab\ExpressionEngine\Controllers\Members\Profile;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Library\CP\Table;

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
 * ExpressionEngine CP Member Profile Bookmarks Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Bookmarks extends Profile {

	private $base_url = 'members/profile/bookmarks';

	public function __construct()
	{
		parent::__construct();
		$this->bookmarks = array();
		$bookmarks = is_string($this->member->bookmarklets) ? (array)json_decode($this->member->bookmarklets) : array();

		foreach ($bookmarks as $bookmark)
		{
			$this->bookmarks[] = $bookmark;
		}

		$this->index_url = $this->base_url;
		$this->base_url = new URL($this->base_url, ee()->session->session_id(), $this->query_string);
	}

	/**
	 * Bookmarks index
	 */
	public function index()
	{
		$table = ee('CP/Table');
		$links = array();
		$data = array();

		foreach ($this->bookmarks as $id => $bookmark)
		{
			$toolbar = array('toolbar_items' => array(
				'edit' => array(
					'href' => cp_url('members/profile/bookmarks/edit/' . $id, $this->query_string),
					'title' => strtolower(lang('edit'))
				)
			));

			$path = cp_url(
				'content_publish/entry_form',
				array(
					'Z'          => 1,
					'BK'         => 1,
					'channel_id' => $bookmark->channel
				)
			);

			$type = (isset($_POST['safari'])) ? "window.getSelection()" : "document.selection?document.selection.createRange().text:document.getSelection()";
			$link = "javascript:bm=$type;void(bmentry=window.open('".$path."title='+encodeURI(document.title)+'&tb_url='+encodeURI(window.location.href)+'&".$bookmark->field."='+encodeURI(bm),'bmentry',''))";
			$link = urlencode($link);

			$links[] = array(
				'name' => "<a href='$link'>{$bookmark->name}</a>",
				$toolbar,
				array(
					'name' => 'selection[]',
					'value' => $id,
					'data'	=> array(
						'confirm' => lang('bookmarklet') . ': <b>' . htmlentities($bookmark->name, ENT_QUOTES) . '</b>'
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
		$data['new'] = cp_url('members/profile/bookmarks/create', $this->query_string);
		$data['form_url'] = cp_url('members/profile/bookmarks/delete', $this->query_string);

		ee()->javascript->set_global('lang.remove_confirm', lang('bookmarks') . ': <b>### ' . lang('bookmarks') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/confirm_remove'),
		));

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('bookmarklets');
		ee()->cp->render('account/bookmarks', $data);
	}

	/**
	 * Create new bookmark
	 *
	 * @access public
	 * @return void
	 */
	public function create()
	{
		$this->base_url = new URL($this->index_url . '/create', ee()->session->session_id(), $this->query_string);

		$vars = array(
			'cp_page_title' => lang('create_bookmarklet'),
			'save_btn_text' => lang('create_bookmarklet')
		);

		if ( ! empty($_POST))
		{
			$order = count($this->bookmarks) + 1;
			$id = $order;
			$this->bookmarks[$order] = array(
				'name' => ee()->input->post('name'),
				'channel' => ee()->input->post('channel'),
				'field' => ee()->input->post('field')
			);
		}

		$this->form($vars);
	}

	/**
	 * Edit bookmark
	 *
	 * @param int $id  The ID of the bookmark to be updated
	 * @access public
	 * @return void
	 */
	public function edit($id)
	{
		$this->base_url = new URL($this->index_url . "/edit/$id", ee()->session->session_id(), $this->query_string);

		$vars = array(
			'cp_page_title' => lang('edit_bookmarklet'),
			'save_btn_text' => lang('save_bookmarklet')
		);

		$values = array(
			'name' => $this->bookmarks[$id]->name,
			'channel' => $this->bookmarks[$id]->channel,
			'field' => $this->bookmarks[$id]->field
		);

		if ( ! empty($_POST))
		{
			$this->bookmarks[$id] = array(
				'name' => ee()->input->post('name'),
				'channel' => ee()->input->post('channel'),
				'field' => ee()->input->post('field')
			);
		}

		$this->form($vars, $values, $id);
	}

	/**
	 * Delete Bookmarks
	 *
	 * @access public
	 * @return void
	 */
	public function delete()
	{
		$selection = array_map(function($x) {return (string)$x;}, $this->input->post('selection'));
		$this->bookmarks = array_diff_key($this->bookmarks, array_flip($selection));
		$this->saveBookmarks();

		ee()->functions->redirect(cp_url($this->index_url, $this->query_string));
	}

	/**
	 * saveBookmarks compiles the links and saves them for the current member
	 *
	 * @access private
	 * @return void
	 */
	private function saveBookmarks()
	{
		$this->member->bookmarklets = json_encode($this->bookmarks);
		$this->member->save();

		return TRUE;
	}

	/**
	 * Display a generic form for creating/editing a bookmark
	 *
	 * @param mixed $vars
	 * @param array $values
	 * @access private
	 * @return void
	 */
	private function form($vars, $values = array())
	{
		$name = isset($values['name']) ? $values['name']: '';
		$channel = isset($values['channel']) ? $values['channel']: '';
		$field = isset($values['field']) ? $values['field']: '';

		$channels = ee('Model')->get('Channel')->all()->getDictionary('channel_id', 'channel_title');
		$filter = ee()->input->post('filter');

		if ( ! empty($channel))
		{
			$fields = ee('Model')->get('Channel', array($channel))->first()->getCustomFields()->getDictionary('field_id', 'field_label');
		}
		else
		{
			$fields = ee('Model')->get('Channel')->first()->getCustomFields()->getDictionary('field_id', 'field_label');
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'bookmarklet_name',
					'desc' => 'bookmarklet_name_desc',
					'fields' => array(
						'name' => array(
							'type' => 'text',
							'value' => $name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'bookmarklet_field',
					'desc' => 'bookmarklet_field_desc',
					'fields' => array(
						'channel' => array(
							'type' => 'dropdown',
							'choices' => $channels,
							'value' => $channel,
							'required' => TRUE
						),
						'field' => array(
							'type' => 'dropdown',
							'choices' => $fields,
							'value' => $field,
							'required' => TRUE
						)
					)
				)
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'name',
				 'label'   => 'lang:bookmark_name',
				 'rules'   => 'required|valid_xss_check'
			)
		));

		if ( empty($filter))
		{
			if (AJAX_REQUEST)
			{
				ee()->form_validation->run_ajax();
				exit;
			}
			elseif (ee()->form_validation->run() !== FALSE)
			{
				if ($this->saveBookmarks())
				{
					ee()->functions->redirect(cp_url($this->index_url, $this->query_string));
				}
			}
			elseif (ee()->form_validation->errors_exist())
			{
				ee()->view->set_message('issue', lang('settings_save_error'), lang('settings_save_error_desc'));
			}
		}

		ee()->javascript->output('
			$("select[name=channel]").change(function(e) {
				$("<input>").attr({
				    type: "hidden",
				    value: "true",
				    name: "filter"
				}).appendTo($(this).parents("form"));
				$(this).parents("form").submit();
			});
		');

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text_working = 'btn_save_working';
		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

/* End of file Bookmarks.php */
/* Location: ./system/expressionengine/controllers/cp/Members/Profile/Bookmarks.php */
