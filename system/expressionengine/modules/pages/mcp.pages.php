<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
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
	function Pages_mcp($switch=TRUE)
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

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
		$vars['base_url'] = clone $vars['table']['base_url'];

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

		return ee()->load->view('index', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Delete Confirmation
	  */
	function delete_confirm()
	{
	    ee()->load->model('pages_model');

		if ( ! ee()->input->post('toggle'))
		{
			return $this->index();
		}

		ee()->load->helper('form');

		ee()->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pages', ee()->lang->line('pages_module_name'));

		$vars['cp_page_title'] = ee()->lang->line('pages_delete_confirm');

		foreach ($_POST['toggle'] as $key => $val)
		{
			$vars['damned'][] = $val;
		}

		$vars['form_hidden']['groups'] = 'n';

		return ee()->load->view('delete_confirm', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Delete Pages
	  */
	function delete()
	{
	    ee()->load->model('pages_model');

		if ( ! ee()->input->post('delete'))
		{
			return $this->index();
		}

		$ids = array();

		foreach ($_POST['delete'] as $key => $val)
		{
			$ids[$val] = $val;
		}

        // Delete Pages & give us the number deleted.
        $delete_pages = ee()->pages_model->delete_site_pages($ids);

		if ($delete_pages === FALSE)
		{
			return $this->index();
		}
		else
		{
    		$message = ($delete_pages > 1) ?
    		                ee()->lang->line('pages_deleted') : ee()->lang->line('page_deleted');

    		ee()->session->set_flashdata('message_success', $message);
    	    ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pages');
		}
	}
}
// END CLASS

/* End of file mcp.pages.php */
/* Location: ./system/expressionengine/modules/pages/mcp.pages.php */