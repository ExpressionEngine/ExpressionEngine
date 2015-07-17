<?php

namespace EllisLab\ExpressionEngine\Controllers\Members\Profile;

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
 * ExpressionEngine CP Member Profile HTML Buttons Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Buttons extends Profile {

	private $base_url = 'members/profile/buttons';

	public function __construct()
	{
		parent::__construct();
		$this->index_url = $this->base_url;
		$this->base_url = ee('CP/URL', $this->base_url, $this->query_string);
	}

	public function index()
	{
		$table = ee('CP/Table');
		$rows = array();
		$data = array();

		foreach ($buttons as $button)
		{
			$toolbar = array('toolbar_items' => array(
				'edit' => array(
					'href' => ee('CP/URL', 'members/profile/buttons/edit/', $this->query_string),
					'title' => strtolower(lang('edit'))
				)
			));

			$rows[] = array(
				'preview' => $button['preview'],
				'tag_name' => $button['tag_name'],
				'short_cut' => $button['short_cut'],
				$toolbar,
				array(
					'name' => 'selection[]',
					'value' => $button['order'],
					'data'	=> array(
						'confirm' => lang('quick_link') . ': <b>' . htmlentities($button['title'], ENT_QUOTES) . '</b>'
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
		$data['new'] = ee('CP/URL', 'members/profile/buttons/create', $this->query_string);
		$data['form_url'] = ee('CP/URL', 'members/profile/buttons/delete', $this->query_string);

		ee()->javascript->set_global('lang.remove_confirm', lang('quick_links') . ': <b>### ' . lang('quick_links') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/confirm_remove'),
		));

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('html_buttons');
		ee()->cp->render('account/buttons', $data);
	}

	}
}
// END CLASS
