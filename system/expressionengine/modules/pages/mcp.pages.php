<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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

        $new_page_location = '';

		if ($default_channel != 0)
		{
			$new_page_location = AMP.'M=entry_form'.AMP.'channel_id='.$default_channel;
		}

		ee()->cp->set_right_nav(array(
				'create_page'			=> BASE.AMP.'C=content_publish'.$new_page_location,
				'pages_configuration'	=> BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pages'.AMP.'method=configuration'
			));
	}

	// --------------------------------------------------------------------

	/**
	  *  Pages Main page
	  */
	function index()
	{
	    ee()->load->model('pages_model');

		$vars['cp_page_title'] = ee()->lang->line('pages_module_name');
		$vars['new_page_location'] = '';

		ee()->load->library('table');
		ee()->load->library('javascript');
		ee()->load->helper('form');

		ee()->javascript->output(array(
				'$(".toggle_all").toggle(
					function(){
						$("input.toggle").each(function() {
							this.checked = true;
						});
					}, function (){
						var checked_status = this.checked;
						$("input.toggle").each(function() {
							this.checked = false;
						});
					}
				);'
			)
		);

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