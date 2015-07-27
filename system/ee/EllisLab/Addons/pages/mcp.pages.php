<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\Table;


/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Pages Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Pages_mcp {

	var $page_array		    = array();
	var $pages			    = array();
	var $homepage_display;

	/**
	  *  Constructor
	  */
	function __construct()
	{
		ee()->load->model('pages_model');

		$query = ee()->pages_model->fetch_configuration();

		$default_channel = 0;

		$this->homepage_display = 'not_nested';

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$$row['configuration_name'] = $row['configuration_value'];
			}

			$this->homepage_display = $homepage_display;
		}
	}

	// --------------------------------------------------------------------

	/**
	  *  Pages Main page
	  */
	function index()
	{
		if ( ! empty($_POST))
		{
			$this->delete();
		}

		$base_url = ee('CP/URL', 'addons/settings/pages');
		$site_id = ee()->config->item('site_id');

		$table = ee('CP/Table', array('autosort' => TRUE, 'autosearch' => FALSE, 'limit' => 20));
		$table->setColumns(
			array(
				'page_name',
				'page_url',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_pages');

		$data = array();

		$pages = ee()->config->item('site_pages');
		if ($pages !== FALSE && count($pages[$site_id]['uris']) > 0)
		{
			$entry_ids = array_keys($pages[$site_id]['uris']);
			$entries = ee('Model')->get('ChannelEntry', $entry_ids)
				->fields('entry_id', 'title', 'channel_id')
				->all();

			$titles = $entries->getDictionary('entry_id', 'title');

			foreach($pages[$site_id]['uris'] as $entry_id => $url)
			{
				$checkbox = array(
					'name' => 'selection[]',
					'value' => $entry_id,
					'data'	=> array(
						'confirm' => lang('page') . ': <b>' . htmlentities($titles[$entry_id], ENT_QUOTES) . '</b>'
					)
				);

				$data[] = array(
					'name' => $titles[$entry_id],
					'url' => $url,
					array(
						'toolbar_items' => array(
							'edit' => array(
								'href' => ee('CP/URL', 'publish/edit/' . $entry_id),
								'title' => lang('edit')
							)
						)
					),
					$checkbox
				);
			}
		}

		$table->setData($data);

		$vars['table'] = $table->viewData($base_url);
		$vars['base_url'] = $vars['table']['base_url'];

		$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($base_url);

		ee()->javascript->set_global('lang.remove_confirm', lang('page') . ': <b>### ' . lang('pages') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/confirm_remove'),
		));

		return ee('View')->make('pages:index')->render($vars);
	}

	/**
	  *  Delete Pages
	  */
	private function delete()
	{
	    ee()->load->model('pages_model');

		$pages = ee()->config->item('site_pages');
		$urls = array();
		$ids = array();

		foreach ($_POST['selection'] as $id)
		{
			$ids[$id] = $id;
			$urls[] = $pages[ee()->config->item('site_id')]['uris'][$id];
		}

        // Delete Pages & give us the number deleted.
        $delete_pages = ee()->pages_model->delete_site_pages($ids);

		if ($delete_pages !== FALSE)
		{
			ee('Alert')->makeInline('pages-form')
				->asSuccess()
				->withTitle(lang('success'))
				->addToBody(lang('pages_deleted_desc'))
				->addToBody($urls)
				->now();
		}
	}
}
// END CLASS

/* End of file mcp.pages.php */
/* Location: ./system/expressionengine/modules/pages/mcp.pages.php */
