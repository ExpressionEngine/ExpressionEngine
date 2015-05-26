<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
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
		ee()->load->model('pages_model');

		ee()->view->cp_page_title = ee()->lang->line('pages_module_name');
		$vars['new_page_location'] = '';

		ee()->load->library('table');
		ee()->load->library('javascript');
		ee()->load->helper('form');

		ee()->javascript->compile();

		$pages = ee()->config->item('site_pages');

		if ($pages === FALSE OR count($pages[ee()->config->item('site_id')]['uris']) == 0)
		{
			return ee()->load->view('index', $vars, TRUE);
		}

		natcasesort($pages[ee()->config->item('site_id')]['uris']);
		$vars['pages'] = array();

		//  Our Pages

		$i = 0;
		$previous = array();
		$spcr = '<img src="'.PATH_CP_GBL_IMG.'clear.gif" border="0"  width="24" height="14" alt="" title="" />';
		$indent = $spcr.'<img src="'.PATH_CP_GBL_IMG.'cat_marker.gif" border="0"  width="18" height="14" alt="" title="" />';

		foreach($pages[ee()->config->item('site_id')]['uris'] as $entry_id => $url)
		{
			$url = ($url == '/') ? '/' : '/'.trim($url, '/');

			$vars['pages'][$entry_id]['entry_id'] = $entry_id;
			$vars['pages'][$entry_id]['entry_id'] = $entry_id;
			$vars['pages'][$entry_id]['view_url'] = ee()->functions->fetch_site_index().QUERY_MARKER.'URL='.urlencode(ee()->functions->create_url($url));
			$vars['pages'][$entry_id]['page'] = $url;
			$vars['pages'][$entry_id]['indent'] = '';

			if ($this->homepage_display == 'nested' && $url != '/')
            {
            	$x = explode('/', trim($url, '/'));

            	for($i=0, $s=count($x); $i < $s; ++$i)
            	{
            		if (isset($previous[$i]) && $previous[$i] == $x[$i])
            		{
            			continue;
            		}

					$this_indent = ($i == 0) ? '' : str_repeat($spcr, $i-1).$indent;
					$vars['pages'][$entry_id]['indent'] = $this_indent;
            	}

            	$previous = $x;
            }

			$vars['pages'][$entry_id]['toggle'] = array(
														'name'		=> 'toggle[]',
														'id'		=> 'delete_box_'.$entry_id,
														'value'		=> $entry_id,
														'class'		=>'toggle'
														);

		}

		return ee()->load->view('index', $vars, TRUE);
	}


	/*
		Hunting for Bugs in the Code...

	           /      \
	        \  \  ,,  /  /
	         '-.`\()/`.-'
	        .--_'(  )'_--.
	       / /` /`""`\ `\ \
	        |  |  ><  \  \
	        \  \      /  /
	            '.__.'
	*/

	// --------------------------------------------------------------------

	/**
	  *  Pages Configuration Screen
	  */
	function configuration()
	{
	    ee()->load->model('pages_model');

		if ( ! empty($_POST))
		{
			$this->delete();
		}

        ee()->load->library('table');

		ee()->view->cp_page_title = ee()->lang->line('pages_configuration');

		ee()->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pages',
		                              ee()->lang->line('pages_module_name'));

		ee()->load->helper('form');

		//  Get Channels
        ee()->load->model('channel_model');
		$wquery = ee()->channel_model->get_channels(ee()->config->item('site_id'));

		$base_url = new URL('addons/settings/pages', ee()->session->session_id());

		$table = Table::create(array('autosort' => TRUE, 'autosearch' => FALSE, 'limit' => 20));
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
		if ($pages !== FALSE && count($pages[ee()->config->item('site_id')]['uris']) > 0)
		{
			$entry_ids = array_keys($pages[ee()->config->item('site_id')]['uris']);
			$entries = ee('Model')->get('ChannelEntry', $entry_ids)->fields('entry_id', 'title')->all();

			$titles = array();
			$entries->each(function($entry) use (&$titles) {
				$titles[$entry->entry_id] = $entry->title;
			});

			foreach($pages[ee()->config->item('site_id')]['uris'] as $entry_id => $url)
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
								'href' => cp_url('publish/edit/' . $entry_id),
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

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
			$pagination = new Pagination(
				$vars['table']['limit'],
				$vars['table']['total_rows'],
				$vars['table']['page']
			);
			$vars['pagination'] = $pagination->cp_links($base_url);
		}

		ee()->javascript->set_global('lang.remove_confirm', lang('page') . ': <b>### ' . lang('pages') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/confirm_remove'),
		));

		return ee()->load->view('index', $vars, TRUE);
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
