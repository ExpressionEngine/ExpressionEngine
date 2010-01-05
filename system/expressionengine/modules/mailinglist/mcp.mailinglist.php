<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Mailing List Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Mailinglist_mcp {

	var $perpage = 100;
	var $pipe_length = 5;

	/**
	  *  Constructor
	  */
	function Mailinglist_mcp( $switch = TRUE )
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	
		
		$this->EE->cp->set_right_nav(array(
		                'ml_create_new' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=edit_mailing_list', 
		                'mailinglist_preferences' => BASE.AMP.'C=admin_system'.AMP.'M=mailing_list_preferences'
		                ));
	
	}

	// --------------------------------------------------------------------

	/**
	  *  Mailinglist Home Page
	  */
	function index()
	{
		$this->EE->load->library('table');
		$this->EE->load->library('javascript');
		$this->EE->load->helper('form');

		$this->EE->cp->add_js_script(array('plugin' => 'tablesorter'));
		$this->EE->jquery->tablesorter('.mainTable', '{
			headers: {2: {sorter: false}, 3: {sorter: false}, 4: {sorter: false}, 6: {sorter: false}},
			widgets: ["zebra"]
		}');

		$this->EE->javascript->output(array(
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

		$vars['cp_page_title'] = $this->EE->lang->line('ml_mailinglist');

		$this->EE->db->order_by('list_title');
		$mailinglists = $this->EE->db->get('mailing_lists');

		$vars['mailinglists'] = array();
		$vars['list_id_options'] = array();

		foreach ($mailinglists->result() as $list)
		{
			$vars['mailinglists'][$list->list_id]['id'] = $list->list_id;

			$this->EE->db->where('list_id', $list->list_id);
			$vars['mailinglists'][$list->list_id]['count'] = $this->EE->db->count_all_results('mailing_list');

			$vars['mailinglists'][$list->list_id]['shortname'] = $list->list_name;

			$vars['mailinglists'][$list->list_id]['name'] = $list->list_title;
			$vars['mailinglists'][$list->list_id]['toggle'] = array(
																			'name'		=> 'toggle[]',
																			'id'		=> 'delete_box_'.$list->list_id,
																			'value'		=> $list->list_id,
																			'class'		=>'toggle'
			    														);
		
			$vars['list_id_options'][$list->list_id] = $list->list_title;
		}

		$this->EE->javascript->compile();

		return $this->EE->load->view('index', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Mailing List Default Template Data
	  */
	function default_template_data()
	{
return <<<EOF
{message_text}

To remove your email from the "{mailing_list}" mailing list, click here:
{if html_email}<a href="{unsubscribe_url}">{unsubscribe_url}</a>{/if}
{if plain_email}{unsubscribe_url}{/if}
EOF;
	}

	// --------------------------------------------------------------------

	/**
	  *  Create/Edit Mailing List
	  */
	function edit_mailing_list()
	{
		$this->EE->load->library('javascript');
		$this->EE->load->library('form_validation');
		$this->EE->load->helper('form');
		
		$this->EE->form_validation->set_rules('list_name', 'lang:ml_mailinglist_short_name', 'required|alpha_dash|callback__unique_short_name');
		$this->EE->form_validation->set_rules('list_title', 'lang:ml_mailinglist_long_name', 'required');
		$this->EE->form_validation->set_message('alpha_dash', $this->EE->lang->line('ml_invalid_short_name'));
		$this->EE->form_validation->set_error_delimiters('<span class="notice">', '</span>');

		$list_id			= 0;
		$vars['list_name']	= '';
		$vars['list_title']	= '';

		if (is_numeric($this->EE->input->get_post('list_id')))
		{
			$this->EE->db->where('list_id', $this->EE->input->get_post('list_id'));
			$query = $this->EE->db->get('mailing_lists');

			if ($query->num_rows() == 1)
			{
				$list_id = $query->row('list_id') ;
				$vars['list_title'] = $query->row('list_title');
				$vars['list_name'] = $query->row('list_name');
				
				$this->EE->form_validation->set_old_value('list_id', $list_id);
			}
		}
		
		if ($this->EE->form_validation->run() === FALSE)
		{
			$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist', $this->EE->lang->line('ml_mailinglist'));

			$vars['cp_page_title'] = ($list_id == 0) ? $this->EE->lang->line('ml_create_new') : $this->EE->lang->line('ml_edit_list');
			$vars['form_action'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=edit_mailing_list';
			$vars['form_hidden']['list_id'] = $list_id;
			$vars['button_label'] = ($list_id == 0) ? $this->EE->lang->line('ml_create_new') : $this->EE->lang->line('update');

			$this->EE->javascript->compile();

			return $this->EE->load->view('update', $vars, TRUE);
		}
		else
		{
			$data = array(
							'list_name'		=> $this->EE->input->post('list_name'),
							'list_title'	=> $this->EE->input->post('list_title'),
							'list_template'	=> addslashes($this->default_template_data())
						);

			if ($list_id == FALSE)
			{
				$this->EE->db->insert('mailing_lists', $data);
			}
			else
			{
				$this->EE->db->where('list_id', $list_id);
				$this->EE->db->update('mailing_lists', $data);
			}
			
			$message = ($list_id == FALSE) ? $this->EE->lang->line('ml_mailinglist_created') : $this->EE->lang->line('ml_mailinglist_updated');
			
			$this->EE->session->set_flashdata('message_success', $message);
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
		}
	}

	// --------------------------------------------------------------------

	/**
	  *  Mailing List Short Name Callback
	  */
	function _unique_short_name($str)
	{
		if ($list_id = $this->EE->form_validation->old_value('list_id'))
		{
			$this->EE->db->where('list_id !=', $list_id);
		}

		$this->EE->db->where('list_name', $str);

		if ($this->EE->db->count_all_results('mailing_lists') > 0)
		{
			$this->EE->form_validation->set_message('_unique_short_name', $this->EE->lang->line('ml_short_name_taken'));
			return FALSE;
		}
		
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	  *  Mailing List Template
	  */
	function edit_template()
	{
		$this->EE->load->helper('form');

		if ( ! $list_id = $this->EE->input->get_post('list_id'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->EE->db->select('list_title, list_template');
		$this->EE->db->where('list_id', $list_id);
		$list = $this->EE->db->get('mailing_lists');

		if ($list->num_rows() == 0)
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist', $this->EE->lang->line('ml_mailinglist'));

		$vars['cp_page_title'] = $this->EE->lang->line('mailinglist_template');
		$vars['form_hidden']['list_id'] = $list_id;
		$vars['list_title'] = $list->row('list_title');
		$vars['template_data'] = form_prep($list->row('list_template'));

		return $this->EE->load->view('edit_template', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Update Mailing List Template
	  */
	function update_template()
	{
		if ( ! $list_id = $this->EE->input->get_post('list_id'))
		{
			show_error($this->EE->lang->line('ml_no_list_id'));
		}

		if ( ! isset($_POST['template_data']))
		{
			return FALSE;
		}

		$this->EE->db->set('list_template', $this->EE->input->post('template_data'));
		$this->EE->db->where('list_id', $list_id);
		$this->EE->db->update('mailing_lists');
		
		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('template_updated'));
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
	}

	// --------------------------------------------------------------------

	/**
	  *  Delete Mailing List Confirm
	  */
	function delete_mailinglist_confirm()
	{
		if ( ! $this->EE->input->post('toggle'))
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
		}

		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist', $this->EE->lang->line('ml_mailinglist'));

		$vars['cp_page_title'] = $this->EE->lang->line('ml_delete_mailinglist');

		$vars['question_key'] = 'ml_delete_list_question';
		$vars['message'] = $this->EE->lang->line('ml_all_data_nuked'); // an extra warning message

		$vars['form_action'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=delete_mailinglists';

		$this->EE->load->helper('form');

		foreach ($_POST['toggle'] as $key => $val)
		{
			$vars['damned'][] = $val;
		}

		$this->EE->db->select('list_title');
		$this->EE->db->where_in('list_id', $_POST['toggle']);
		$query = $this->EE->db->get('mailing_lists');

		$vars['list_names'] = array();

		foreach ($query->result() as $row)
		{

			$vars['list_names'][] = $row->list_title;
		}

		$this->EE->javascript->compile();

		return $this->EE->load->view('delete_confirm', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Delete Mailing List(s)
	  */
	function delete_mailinglists()
	{
		if ($this->EE->input->post('delete') == '')
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
		}

		$this->EE->db->where_in('list_id', $_POST['delete']);
		$this->EE->db->delete(array('mailing_lists', 'mailing_list'));

		$message = ($this->EE->db->affected_rows() == 1) ? $this->EE->lang->line('ml_list_deleted') : $this->EE->lang->line('ml_lists_deleted');

		$this->EE->session->set_flashdata('message_success', $message);
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
	}

	// --------------------------------------------------------------------

	/**
	  *  Subscribe
	  */
	function subscribe()
	{
		if ($this->EE->input->post('addresses') == '')
		{
			$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('ml_missing_email'));
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
		}

		$this->EE->load->helper(array('email','string'));

		//  Fetch existing addresses
		$subscribe = ($this->EE->input->get_post('sub_action') == 'unsubscribe') ? FALSE : TRUE;

		$list_id = $this->EE->input->get_post('list_id');

		$this->EE->db->select('email');
		$this->EE->db->where('list_id', $list_id);
		$query = $this->EE->db->get('mailing_list');

		$current = array();

		if ($query->num_rows() == 0)
		{
			if ($subscribe == FALSE)
			{
				$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
			}
		}
		else
		{
			foreach ($query->result() as $row)
			{
				$current[] = $row->email;
			}
		}

		//  Clean up submitted addresses
		$email	= trim($_POST['addresses']);
		$email	= preg_replace("/[,|\|]/", "", $email);
		$email	= str_replace(array("\r\n", "\r", "\n"), " ", $email);
		$email	= preg_replace("/\t+/", " ", $email);
		$email	= preg_replace("/\s+/", " ", $email);
		$emails	= array_unique(explode(" ", $email));

		//  Insert new addresses
		$vars['good_email'] = 0;
		$vars['dup_email']	= 0;

		$vars['bad_email']  = array();

		foreach($emails as $addr)
		{
			if (preg_match('/<(.*)>/', $addr, $match))
			{
				$addr = $match['1'];
			}

			if ($subscribe == TRUE)
			{
				if ( ! valid_email($addr))
				{
					$vars['bad_email'][] = $addr;
					continue;
				}

				if (in_array($addr, $current))
				{
					$vars['dup_email']++;
					continue;
				}

				$data = array(
								'list_id'		=> $list_id,
								'authcode'		=> random_string('alnum', 10),
								'email'			=> $addr,
								'ip_address'	=> $this->EE->input->ip_address()
							);
				$this->EE->db->insert('mailing_list', $data);
			}
			else
			{
				$this->EE->db->where('email', $addr);
				$this->EE->db->where('list_id', $list_id);
				$this->EE->db->delete('mailing_list');
			}

			$vars['good_email']++;
		}

		if (count($vars['bad_email']) == 0 AND $vars['dup_email'] == 0)
		{
			if ($subscribe == TRUE)
			{
				$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('ml_emails_imported'));
				$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
			}
			else
			{
				$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('ml_emails_deleted'));
				$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
			}
		}
		else
		{
			$vars['cp_page_title'] = $this->EE->lang->line('ml_batch_subscribe');
			$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist', $this->EE->lang->line('ml_mailinglist'));

			$vars['notice'] = '';

			$vars['notice_import_del'] = ($subscribe == TRUE) ? 'ml_total_emails_imported' : 'ml_total_emails_deleted';

			if (count($vars['bad_email']) > 0)
			{
				sort($vars['bad_email']);
				
				$vars['notice_bad_email'] = ($subscribe == TRUE) ? 'ml_bad_email_heading' : 'ml_bad_email_del_heading';
			}

			return $this->EE->load->view('subscribe', $vars, TRUE);
		}
	}

	// --------------------------------------------------------------------

	/**
	  *  View Mailinglist
	  */
	function view()
	{
		$this->EE->load->library('pagination');
		$this->EE->load->library('javascript');
		$this->EE->load->library('table');
		$this->EE->load->helper('form');

		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist', $this->EE->lang->line('ml_mailinglist'));

		$vars['cp_page_title'] = $this->EE->lang->line('ml_view_mailinglist');
		$this->EE->cp->add_js_script(array('plugin' => 'dataTables'));


	$this->EE->javascript->output('
var oCache = {
	iCacheLower: -1
};

function fnSetKey( aoData, sKey, mValue )
{
	for ( var i=0, iLen=aoData.length ; i<iLen ; i++ )
	{
		if ( aoData[i].name == sKey )
		{
			aoData[i].value = mValue;
		}
	}
}

function fnGetKey( aoData, sKey )
{
	for ( var i=0, iLen=aoData.length ; i<iLen ; i++ )
	{
		if ( aoData[i].name == sKey )
		{
			return aoData[i].value;
		}
	}
	return null;
}

function fnDataTablesPipeline ( sSource, aoData, fnCallback ) {
	var iPipe = '.$this->pipe_length.';  /* Ajust the pipe size */
	
	var bNeedServer = false;
	var sEcho = fnGetKey(aoData, "sEcho");
	var iRequestStart = fnGetKey(aoData, "iDisplayStart");
	var iRequestLength = fnGetKey(aoData, "iDisplayLength");
	var iRequestEnd = iRequestStart + iRequestLength;
	var email = document.getElementById("email");
    var list_id = document.getElementById("list_id");

		aoData.push( 
		 { "name": "email", "value": email.value },
         { "name": "list_id", "value": list_id.value }
		 );
	
	oCache.iDisplayStart = iRequestStart;
	
	/* outside pipeline? */
	if ( oCache.iCacheLower < 0 || iRequestStart < oCache.iCacheLower || iRequestEnd > oCache.iCacheUpper )
	{
		bNeedServer = true;
	}
	
	/* sorting etc changed? */
	if ( oCache.lastRequest && !bNeedServer )
	{
		for( var i=0, iLen=aoData.length ; i<iLen ; i++ )
		{
			if ( aoData[i].name != "iDisplayStart" && aoData[i].name != "iDisplayLength" && aoData[i].name != "sEcho" )
			{
				if ( aoData[i].value != oCache.lastRequest[i].value )
				{
					bNeedServer = true;
					break;
				}
			}
		}
	}
	
	/* Store the request for checking next time around */
	oCache.lastRequest = aoData.slice();
	
	if ( bNeedServer )
	{
		if ( iRequestStart < oCache.iCacheLower )
		{
			iRequestStart = iRequestStart - (iRequestLength*(iPipe-1));
			if ( iRequestStart < 0 )
			{
				iRequestStart = 0;
			}
		}
		
		oCache.iCacheLower = iRequestStart;
		oCache.iCacheUpper = iRequestStart + (iRequestLength * iPipe);
		oCache.iDisplayLength = fnGetKey( aoData, "iDisplayLength" );
		fnSetKey( aoData, "iDisplayStart", iRequestStart );
		fnSetKey( aoData, "iDisplayLength", iRequestLength*iPipe );
		
				aoData.push( 
				 { "name": "email", "value": email.value },
        	     { "name": "list_id", "value": list_id.value }
				 );
		
		
		$.getJSON( sSource, aoData, function (json) { 
			/* Callback processing */
			oCache.lastJson = jQuery.extend(true, {}, json);
 			
			if ( oCache.iCacheLower != oCache.iDisplayStart )
			{
				json.aaData.splice( 0, oCache.iDisplayStart-oCache.iCacheLower );
			}
			json.aaData.splice( oCache.iDisplayLength, json.aaData.length );
			
			fnCallback(json)
		} );
	}
	else
	{
		json = jQuery.extend(true, {}, oCache.lastJson);
		json.sEcho = sEcho; /* Update the echo for each response */
		json.aaData.splice( 0, iRequestStart-oCache.iCacheLower );
		json.aaData.splice( iRequestLength, json.aaData.length );
		fnCallback(json);
		return;
	}
}

	
	oTable = $(".mainTable").dataTable( {	
			"sPaginationType": "full_numbers",
			"bLengthChange": false,
			"bFilter": false,
			"sWrapper": false,
			"sInfo": false,
			"bAutoWidth": false,
			"iDisplayLength": '.$this->perpage.',  

		"aoColumns": [null, null, null, { "bSortable" : false } ],
			
			
		"oLanguage": {
			"sZeroRecords": "'.$this->EE->lang->line('ml_no_results').'",
			
			"oPaginate": {
				"sFirst": "<img src=\"'.$this->EE->cp->cp_theme_url.'images/pagination_first_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />",
				"sPrevious": "<img src=\"'.$this->EE->cp->cp_theme_url.'images/pagination_prev_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />",
				"sNext": "<img src=\"'.$this->EE->cp->cp_theme_url.'images/pagination_next_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />", 
				"sLast": "<img src=\"'.$this->EE->cp->cp_theme_url.'images/pagination_last_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />"
			}
		},
		
			
			"bProcessing": true,
			"bServerSide": true,
			"sAjaxSource": EE.BASE+"&C=addons_modules&M=show_module_cp&module=mailinglist&method=view_ajax_filter",
			"fnServerData": fnDataTablesPipeline

	} );
	
		$("select#list_id").change(function () {
				oTable.fnDraw();
			});		



var delayed;

$("#email").keyup(function() {
     clearTimeout(delayed);
     var value = this.value;
     if (value) {
         delayed = setTimeout(function() {
	oTable.fnDraw();
         }, 300);
     }
})		
		
		');		


// this worked when you cleared the field- but no delay
		//	$("#email").keyup( function () {
		/* Filter on the column (the index) of this element */
		//oTable.fnDraw();
		//} );	


		$this->EE->javascript->output(array(
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

		$list_id = $this->EE->input->get_post('list_id');
		$email = $this->EE->input->get_post('email');
		$rownum = ($this->EE->input->get_post('rownum') != '') ? $this->EE->input->get_post('rownum') : 0;

		// some page defaults
		$vars['form_hidden'] = '';


		if ($list_id != '')
		{
			$vars['form_hidden']['list_id'] = $list_id;
		}

		$this->EE->db->select('list_id, list_title');
		$res = $this->EE->db->get('mailing_lists');

		$vars['mailinglists'][''] = $this->EE->lang->line('all');

		foreach ($res->result_array() as $row)
		{
			$lists[$row['list_id']] = $row['list_title'];
			$vars['mailinglists'][$row['list_id']] = $row['list_title'];
		}
		
		$vars['selected_list'] = $list_id;
		$vars['email'] = $email;

		$vars['subscribers'] = array();

		$row_count = 1;
		$query = $this->mailinglist_search($list_id, $email, '', $rownum);


		foreach ($query->result() as $row)
		{
			$vars['subscribers'][$row->user_id]['row_count'] = $row_count;
			$vars['subscribers'][$row->user_id]['ip_address'] = $row->ip_address;
			$vars['subscribers'][$row->user_id]['toggle'] = array(
																			'name'		=> 'toggle[]',
																			'id'		=> 'delete_box_'.$row->user_id,
																			'value'		=> $row->user_id,
																			'class'		=>'toggle'
																	);
			$vars['subscribers'][$row->user_id]['email'] = $row->email;
			$vars['subscribers'][$row->user_id]['list'] = isset($lists[$row->list_id]) ?  $lists[$row->list_id] : '';

			$row_count++;
		}

		// Pass the relevant data to the paginate class
		$config['base_url'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=view';
		$config['total_rows'] = $this->EE->db->count_all('mailing_list');
		$config['per_page'] = $this->perpage;
		$config['page_query_string'] = TRUE;
		$config['query_string_segment'] = 'rownum';
		$config['full_tag_open'] = '<p id="paginationLinks">';
		$config['full_tag_close'] = '</p>';
		$config['prev_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="&lt;" />';
		$config['next_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt="&gt;" />';
		$config['first_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="&lt; &lt;" />';
		$config['last_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="&gt; &gt;" />';

		$this->EE->pagination->initialize($config);

		$vars['pagination'] = $this->EE->pagination->create_links();

		$this->EE->javascript->compile();

		return $this->EE->load->view('view', $vars, TRUE);
	}


	function view_ajax_filter()
	{
		$this->EE->output->enable_profiler(FALSE);
		$col_map = array('email', 'ip_address', 'list_title');
		
		$email = ($this->EE->input->get_post('email')) ? $this->EE->input->get_post('email') : '';
		$list_id = ($this->EE->input->get_post('list_id')) ? $this->EE->input->get_post('list_id') : '';		

		
		// Note- we pipeline the js, so pull more data than are displayed on the page		
		$perpage = $this->EE->input->get_post('iDisplayLength');
		$offset = ($this->EE->input->get_post('iDisplayStart')) ? $this->EE->input->get_post('iDisplayStart') : 0; // Display start point
		$sEcho = $this->EE->input->get_post('sEcho');	
		
		/* Ordering */
		$order = array();

		if ( isset($_GET['iSortCol_0']))
		{
			for ( $i=0; $i < $_GET['iSortingCols']; $i++ )
			{
				$order[$col_map[$_GET['iSortCol_'.$i]]] = $_GET['iSortDir_'.$i];
			}
		}

		$this->EE->db->select('list_id, list_title');
		$res = $this->EE->db->get('mailing_lists');

		foreach ($res->result_array() as $row)
		{
			$lists[$row['list_id']] = $row['list_title'];
		}


		$query = $this->mailinglist_search($list_id, $email, $order, $offset, $perpage);

		
		// Note- we can just use $f_total for both if we choose not to show total records
		// $f_total must be accurate for proper pagination
		$total = $this->EE->db->count_all('mailing_list');
		
		if ($list_id != '')
		{
			$this->EE->db->where('list_id', $list_id);
			$f_total = $this->EE->db->count_all_results('mailing_list');
		}
		else
		{
			$f_total = $total;
		}


		$j_response['sEcho'] = $sEcho;
		$j_response['iTotalRecords'] = $total;
		$j_response['iTotalDisplayRecords'] = $f_total;

		$tdata = array();
		$i = 0;

		foreach ($query->result_array() as $subscriber)
		{
			$m[] = '<a href="mailto:'.$subscriber['email'].'">'.$subscriber['email'].'</a>';
			$m[] =  $subscriber['ip_address'];
			$m[] =	isset($lists[$subscriber['list_id']]) ?  $lists[$subscriber['list_id']] : '';
			$m[] = '<input class="toggle" type="checkbox" name="toggle[]" value="'.$subscriber['user_id'].'" />';

			$tdata[$i] = $m;
			$i++;
			unset($m);
		}

		$j_response['aaData'] = $tdata;	
		$sOutput = $this->EE->javascript->generate_json($j_response, TRUE);

		die($sOutput);
	}

	function mailinglist_search($list_id = '', $email = '', $order = array(), $rownum = 0, $perpage = '')
	{
		$perpage = ($perpage == '') ? $this->perpage: $perpage;
		$do_join = FALSE;
			
		$this->EE->db->select('user_id, mailing_list.list_id, email, ip_address');
		$this->EE->db->from('mailing_list');

		if (is_array($order) && count($order) > 0)
		{
			foreach ($order as $key => $val)
			{
				if ($key != 'list_title')
				{
					$this->EE->db->order_by($key, $val);
				}
				elseif ($key == 'list_title' && $list_id == '')
				{
					$do_join = TRUE;
					$this->EE->db->order_by($key, $val);
				}
			}
		}

		if ($do_join == TRUE)
		{
			$this->EE->db->join('mailing_lists', 'mailing_lists.list_id = mailing_list.list_id', 'left');
		}

		if ($list_id != '')
		{
			$this->EE->db->where('list_id', $list_id);
		}

		if ($email)
		{
			$this->EE->db->like('email', urldecode($email));
		}

		$this->EE->db->limit($perpage, $rownum);
		$query = $this->EE->db->get();
		
		return $query;
	}

	// --------------------------------------------------------------------

	/**
	  *  Delete Emails - Confirm
	  */
	function delete_confirm()
	{
		if ( ! $this->EE->input->post('toggle'))
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=view');
		}

		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist', $this->EE->lang->line('ml_mailinglist'));

		$vars['cp_page_title'] = $this->EE->lang->line('ml_delete_confirm');
		$vars['question_key'] = 'ml_delete_question';
		$vars['form_action'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=delete_email_addresses';

		$this->EE->load->helper('form');

		foreach ($_POST['toggle'] as $key => $val)
		{
			$vars['damned'][] = $val;
		}

		$this->EE->javascript->compile();

		return $this->EE->load->view('delete_confirm', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Delete Email Addresses
	  */
	function delete_email_addresses()
	{
		if ($this->EE->input->post('delete') == '')
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=view');
		}

		$this->EE->db->where_in('user_id', $_POST['delete']);
		$this->EE->db->delete('mailing_list');

		$message = ($this->EE->db->affected_rows() == 1) ? $this->EE->lang->line('ml_email_deleted') : $this->EE->lang->line('ml_emails_deleted');

		$this->EE->session->set_flashdata('message_success', $message);
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
	}
}
// END CLASS

/* End of file mcp.mailinglist.php */
/* Location: ./system/expressionengine/modules/mailinglist/mcp.mailinglist.php */