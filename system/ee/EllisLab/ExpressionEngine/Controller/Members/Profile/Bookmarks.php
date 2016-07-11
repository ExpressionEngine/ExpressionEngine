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
 * ExpressionEngine CP Member Profile Bookmarks Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Bookmarks extends Settings {

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
		$this->base_url  = ee('CP/URL')->make($this->base_url, $this->query_string);
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
					'href' => ee('CP/URL')->make('members/profile/bookmarks/edit/' . $id, $this->query_string),
					'title' => strtolower(lang('edit'))
				)
			));

			$path = ee()->config->item('cp_url').'?/cp/publish/create/'.$bookmark->channel;
			$path .= '&BK=1&';

			$type = (isset($_POST['safari'])) ? "window.getSelection()" : "document.selection?document.selection.createRange().text:document.getSelection()";
			$link = "bm=$type;void(bmentry=window.open('".$path."title='+encodeURI(document.title)+'&field_id_".$bookmark->field."='+encodeURI(bm),'bmentry',''))";
			$link = 'javascript:'.urlencode($link);

			$links[] = array(
				'name' => "<a href='$link'>".htmlentities($bookmark->name, ENT_QUOTES, 'UTF-8')."</a>",
				$toolbar,
				array(
					'name' => 'selection[]',
					'value' => $id,
					'data'	=> array(
						'confirm' => lang('bookmarklet') . ': <b>' . htmlentities($bookmark->name, ENT_QUOTES, 'UTF-8') . '</b>'
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

		$table->setNoResultsText('no_bookmarklets_found');
		$table->setData($links);

		$data['table'] = $table->viewData($this->base_url);
		$data['new'] = ee('CP/URL')->make('members/profile/bookmarks/create', $this->query_string)->compile();
		$data['form_url'] = ee('CP/URL')->make('members/profile/bookmarks/delete', $this->query_string)->compile();

		ee()->javascript->set_global('lang.remove_confirm', lang('bookmarks') . ': <b>### ' . lang('bookmarks') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove'),
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
		ee()->cp->set_breadcrumb($this->base_url, lang('bookmarklets'));
		$this->base_url = ee('CP/URL')->make($this->index_url . '/create', $this->query_string);

		$vars = array(
			'cp_page_title' => lang('create_bookmarklet')
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
		ee()->cp->set_breadcrumb($this->base_url, lang('bookmarklets'));
		$this->base_url = ee('CP/URL')->make($this->index_url . "/edit/$id", $this->query_string);

		$vars = array(
			'cp_page_title' => lang('edit_bookmarklet')
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

		ee()->functions->redirect(ee('CP/URL')->make($this->index_url, $this->query_string));
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
		$channel_id = isset($values['channel']) ? $values['channel']: '';
		$field = isset($values['field']) ? $values['field']: '';
		$fields = array();

		$channels = ee('Model')->get('Channel')->all()->getDictionary('channel_id', 'channel_title');
		$filter = ee()->input->post('filter');

		if (empty($channel_id))
		{
			$channel = ee('Model')->get('Channel')->first();
		}
		else
		{
			$channel = ee('Model')->get('Channel', $channel_id)->first();
		}

		if ( ! empty($channel))
		{
			$fields = $channel->CustomFields->getDictionary('field_id', 'field_label');
		}

		if ($channels)
		{
			$bookmarklet_field_fields = array(
				'channel' => array(
					'type' => 'select',
					'choices' => $channels,
					'value' => $channel,
					'required' => TRUE
				),
				'field' => array(
					'type' => 'select',
					'choices' => $fields,
					'value' => $field,
					'required' => TRUE
				)
			);
		}
		else
		{
			$bookmarklet_field_fields = array(
				'channel' => array(
					'type' => 'select',
					'choices' => $channels,
					'value' => $channel,
					'required' => TRUE,
					'no_results' => array(
						'text' => 'no_channels',
						'link_text' => 'create_new_channel',
						'link_href' => ee('CP/URL', 'channels/create')
					)
				),
			);
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'name',
					'desc' => 'alphadash_desc',
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
					'fields' => $bookmarklet_field_fields
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
		ee()->view->save_btn_text = sprintf(lang('btn_save'), lang('bookmarklet'));
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

// EOF
