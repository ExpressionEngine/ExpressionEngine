<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Comment Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Comment_mcp {

	var $pipe_length		= '2';
	var $comment_chars			= "20";
	var $comment_leave_breaks = 'n';
	var $perpage = 50;
	var $base_url = '';
	var $search_url;

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Comment_mcp()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
				
		if (REQ == 'CP')
		{
			$this->base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment';

			if ($this->EE->cp->allowed_group('can_moderate_comments') &&  $this->EE->cp->allowed_group('can_edit_all_comments') && $this->EE->cp->allowed_group('can_delete_all_comments'))
			{
				$this->EE->cp->set_right_nav(array(
									'settings'				=> $this->base_url.AMP.'method=settings',
									'comments' 				=> $this->base_url)
								);	
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Main Comment Listing
	 *
	 * @access	public
	 * @return	string
	 */
	function index($channel_id = '', $entry_id = '', $message = '', $id_array = '', $total_rows = '', $pag_base_url = '')
	{
		if ( ! $this->EE->cp->allowed_group('can_moderate_comments') && ! $this->EE->cp->allowed_group('can_edit_all_comments') && ! $this->EE->cp->allowed_group('can_edit_own_comments'))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		$this->EE->load->helper('text');
		$this->EE->load->model('search_model');
		$this->EE->load->model('comment_model');

	
		$this->EE->load->library('javascript');
		$this->EE->load->library('table');
		$this->EE->load->helper('form');
		
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('comments'));
		// Add javascript

		$this->EE->cp->add_js_script(array('plugin' => 'dataTables'));
		$this->EE->cp->add_js_script(array('plugin' => 'crypt'));
		$this->EE->cp->add_js_script('ui', 'datepicker');
			
		$this->EE->javascript->output($this->ajax_filters('comments_ajax_filter', 9));
		
		$this->EE->cp->get_installed_modules();
		
		$this->EE->javascript->output('
		$(".toggle_comments").toggle(
			function(){
				$("input[class=comment_toggle]").each(function() {
					this.checked = true;
				});
			}, function (){
				$("input[class=comment_toggle]").each(function() {
					this.checked = false;
				});
			}
		);');
		

		// Require at least one comment checked to submit
		$this->EE->javascript->output('
		$("#target").submit(function() {
			if ( ! $("input[class=comment_toggle]", this).is(":checked")) {
			$.ee_notice("'.$this->EE->lang->line('selection_required').'", {"type" : "error"});
			return false;
			}
		});');
				
		$this->EE->javascript->output('
			$("#custom_date_start_span").datepicker({
				dateFormat: "yy-mm-dd",
				prevText: "<<",
				nextText: ">>",
				onSelect: function(date) {
					$("#custom_date_start").val(date);
					dates_picked();
				}
			});
			$("#custom_date_end_span").datepicker({
				dateFormat: "yy-mm-dd",
				prevText: "<<",
				nextText: ">>",
				onSelect: function(date) {
					$("#custom_date_end").val(date);
					dates_picked();
				}
			});

			$("#custom_date_start, #custom_date_end").focus(function(){
				if ($(this).val() == "yyyy-mm-dd")
				{
					$(this).val("");
				}
			});

			$("#custom_date_start, #custom_date_end").keypress(function(){
				if ($(this).val().length >= 9)
				{
					dates_picked();
				}
			});

			function dates_picked()
			{
				if ($("#custom_date_start").val() != "yyyy-mm-dd" && $("#custom_date_end").val() != "yyyy-mm-dd")
				{
					// populate dropdown box
					focus_number = $("#date_range").children().length;
					$("#date_range").append("<option id=\"custom_date_option\">" + $("#custom_date_start").val() + " to " + $("#custom_date_end").val() + "</option>");
					document.getElementById("date_range").options[focus_number].selected=true;

					// hide custom date picker again
					$("#custom_date_picker").slideUp("fast");
				}
			}
		');

		$this->EE->javascript->change("#date_range", "
			if ($('#date_range').val() == 'custom_date')
			{
				// clear any current dates, remove any custom options
				$('#custom_date_start').val('yyyy-mm-dd');
				$('#custom_date_end').val('yyyy-mm-dd');
				$('#custom_date_option').remove();

				// drop it down
				$('#custom_date_picker').slideDown('fast');
			}
			else
			{
				$('#custom_date_picker').hide();
			}
		");
		

		$this->EE->javascript->compile();

		$filter = $this->filter_settings();

		$vars = $this->create_filter($filter);
		$vars['hidden'] = array();
		$vars['pagination'] = FALSE;
		
		$vars['form_options'] = array(
									'close' => $this->EE->lang->line('close_selected'),
									'open' => $this->EE->lang->line('open_selected'),
									'pending' => $this->EE->lang->line('pending_selected'),
									);

		if ($this->EE->cp->allowed_group('can_delete_all_comments') OR $this->EE->cp->allowed_group('can_delete_own_comments'))
		{
			$vars['form_options']['null'] = '------';
			$vars['form_options']['delete'] = $this->EE->lang->line('delete_selected');
		}		
			
		if ( ! $rownum = $this->EE->input->get_post('rownum'))
		{		
			$rownum = 0;
		}

		//  Get comment ids
		$comment_id_query = $this->EE->comment_model->get_comment_ids($filter);	
		

		//  Check for pagination
		$total = $comment_id_query->num_rows();
		
		

		// No results?  No reason to continue...
		if ($total == 0)
		{
			$vars['message'] = $this->EE->lang->line('no_comments');
			$vars['comments'] = array();
			return $this->EE->load->view('index', $vars, TRUE);
		}

		$comment_ids = array_slice($comment_id_query->result_array(), $rownum, $this->perpage);
		
		$ids = array();
		
		foreach ($comment_ids as $id)
		{
			$ids[] = $id['comment_id'];
		}
		
		$comment_results = $this->EE->comment_model->fetch_comment_data($ids);

		/* -------------------------------------------
		/*	Hidden Configuration Variables
		/*	- view_comment_chars => Number of characters to display (#)
		/*	- view_comment_leave_breaks => Create <br />'s based on line breaks? (y/n)
		/* -------------------------------------------*/

		$this->comment_chars		= ($this->EE->config->item('view_comment_chars') !== FALSE) ? $this->EE->config->item('view_comment_chars') : $this->comment_chars;
		$this->comment_leave_breaks = ($this->EE->config->item('view_comment_leave_breaks') !== FALSE) ? $this->EE->config->item('view_comment_leave_breaks') : $this->comment_leave_breaks;
	
		// Do we need pagination?

		$this->EE->load->library('pagination');

		$p_config = $this->pagination_config('index', $total);

		$this->EE->pagination->initialize($p_config);
		$pagination_links = $this->EE->pagination->create_links();
			

		// Prep for output
		$config = ($this->EE->config->item('comment_word_censoring') == 'y') ? array('word_censor' => TRUE) : array();
		
		$this->EE->load->library('typography');
		$this->EE->typography->initialize($config);
		$this->EE->load->helper('form');


		// Show comments
		
		$vars['comments'] = array();
		
		if ($comment_results != FALSE)
		{
			$config = ($this->EE->config->item('comment_word_censoring') == 'y') ? array('word_censor' => TRUE) : array();
		
			$this->EE->load->library('typography');
			$this->EE->typography->initialize($config);
			
			$this->EE->typography->parse_images = FALSE;
			$this->EE->typography->allow_headings = FALSE;
			
			foreach ($comment_results->result_array() as $row)
			{
				$data = array();

				$row['full_comment'] = $this->EE->typography->parse_type($row['comment'],
					array(
						'text_format'	=> $row['comment_text_formatting'],
						'html_format'	=> $row['comment_html_formatting'],
						'auto_links'	=> $row['comment_auto_link_urls'],
						'allow_img_url' => $row['comment_allow_img_urls']
						)
					);

				if ($this->comment_leave_breaks == 'y')
				{
					$row['comment'] = str_replace(array("\n","\r"),
												  '<br />',
												  strip_tags($row['comment'])
												  );
				}
				else
				{
					$row['comment'] = strip_tags(str_replace(array("\t","\n","\r"), ' ', $row['comment']));
				}

				if ($this->comment_chars != 0)
				{
					$row['comment'] = $this->EE->functions->char_limiter(trim($row['comment']), $this->comment_chars);
				}

				$row['can_edit_comment'] = TRUE;

				if (($row['entry_author_id'] != $this->EE->session->userdata('member_id')) && ! $this->EE->cp->allowed_group('can_edit_all_comments'))
				{
					$row['can_edit_comment'] = FALSE;
				}
	
				if ($row['status'] == 'o')
				{
					$status_label = $this->EE->lang->line('open');
				}
				elseif ($row['status'] == 'c')
				{
					$status_label = $this->EE->lang->line('closed');
				}
				else
				{
					$status_label = $this->EE->lang->line('pending');
				}

				$data = $row;
 			
				$data['edit_url'] = $this->base_url.AMP.'method=edit_comment_form'.AMP.'comment_id='.$row['comment_id'];
			
				$data['status_label'] = $status_label;
				$data['status_search_url'] = $this->base_url.AMP.'status='.$row['status'];
				$data['can_edit_comment'] = $row['can_edit_comment'];
				$data['ip_search_url'] = $this->base_url.AMP.'ip_address='.base64_encode($row['ip_address']);
				$data['channel_search_url'] = $this->base_url.AMP.'channel_id='.$row['channel_id'];
				$data['email_search_url'] = $this->base_url.AMP.'email='.base64_encode($row['email']);
				$data['mail_to'] = ($row['email'] != '') ? mailto($row['email']) : FALSE;
				$data['name_search_url'] = $this->base_url.AMP.'name='.base64_encode($row['name']);
				$data['date'] = $this->EE->localize->set_human_time($row['comment_date']);
				$data['entry_search_url'] = $this->base_url.AMP.'entry_id='.$row['entry_id'];
				$data['entry_title'] = $this->EE->functions->char_limiter(trim(strip_tags($row['title'])), 26);

				$vars['comments'][] = $data;
			} // END FOREACH
		}

		$vars['pagination'] = $pagination_links;
		$vars['message'] = $message;

		return $this->EE->load->view('index', $vars, TRUE);
	}



	function pagination_config($method, $total_rows)
	{
		// Pass the relevant data to the paginate class

		
		$config['base_url'] = ($this->search_url == '') ? $this->base_url.AMP.'method='.$method : $this->base_url.AMP.'method='.$method.AMP.$this->search_url;
		$config['total_rows'] = $total_rows;
		$config['per_page'] = $this->perpage;
		$config['page_query_string'] = TRUE;
		$config['query_string_segment'] = 'rownum';
		$config['full_tag_open'] = '<p id="paginationLinks">';
		$config['full_tag_close'] = '</p>';
		$config['prev_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="&lt;" />';
		$config['next_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt="&gt;" />';
		$config['first_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="&lt; &lt;" />';
		$config['last_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="&gt; &gt;" />';

		return $config;
	}



	function ajax_filters($ajax_method = '', $cols = '')
	{
		if ($ajax_method == '')
		{
			return;
		}
		
		$js = '
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
		var iPipe 			= '.$this->pipe_length.', /* Ajust the pipe size */
			bNeedServer 	= false,
			sEcho 			= fnGetKey(aoData, "sEcho"),
			iRequestStart 	= fnGetKey(aoData, "iDisplayStart"),
			iRequestLength 	= fnGetKey(aoData, "iDisplayLength"),
			iRequestEnd 	= iRequestStart + iRequestLength,
			keywords		= document.getElementById("keywords"),
			status			= document.getElementById("f_status"),
			channel_id		= document.getElementById("f_channel_id"),
   			search_in		= document.getElementById("f_search_in"),
   			date_range		= document.getElementById("date_range");

			//keywordFix = $.base64Encode(keywords.value);
			if (keywords.value.length)
			{
				keywordFix = $().crypt({method:"b64enc",source: keywords.value}); 
			}
			else
			{
				keywordFix = keywords.value;
			}
			
		aoData.push( 
			 { "name": "keywords", "value": keywordFix },
	         { "name": "status", "value": status.value },
			 { "name": "channel_id", "value": channel_id.value },
	         { "name": "search_in", "value": search_in.value },
	         { "name": "date_range", "value": date_range.value }
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
			 			{ "name": "keywords", "value": keywordFix },
	         			{ "name": "status", "value": status.value },
			 			{ "name": "channel_id", "value": channel_id.value },
	         			{ "name": "search_in", "value": search_in.value },
	         			{ "name": "date_range", "value": date_range.value }

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

	var time = new Date().getTime();

	oTable = $(".mainTable").dataTable( {	
			"sPaginationType": "full_numbers",
			"bLengthChange": false,
			"aaSorting": [[ 6, "desc" ]],
			"bFilter": false,
			"sWrapper": false,
			"sInfo": false,
			"bAutoWidth": false,
			"fnDrawCallback": fnOpenClose,
			"iDisplayLength": '.$this->perpage.', 
			"aoColumns": [{ "bSortable" : false }, null, null, { "bVisible" : false }, null, null, null, null, null, { "bVisible" : false }, { "bSortable" : false } ],
					
		"oLanguage": {
			"sZeroRecords": "'.$this->EE->lang->line('no_valid_comments').'",
			
			"oPaginate": {
				"sFirst": "<img src=\"'.$this->EE->cp->cp_theme_url.'images/pagination_first_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />",
				"sPrevious": "<img src=\"'.$this->EE->cp->cp_theme_url.'images/pagination_prev_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />",
				"sNext": "<img src=\"'.$this->EE->cp->cp_theme_url.'images/pagination_next_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />", 
				"sLast": "<img src=\"'.$this->EE->cp->cp_theme_url.'images/pagination_last_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />"
			}
		},
		
			"bProcessing": true,
			"bServerSide": true,
			"sAjaxSource": EE.BASE+"&C=addons_modules&M=show_module_cp&module=comment&method='.$ajax_method.'&time=" + time,
			"fnServerData": fnDataTablesPipeline

	} );
	
	/* Formating function for row details */
	function fnFormatDetails ( nTr )
	{
		var aData = oTable.fnGetData( nTr );
		var sOut = "<table class=\"detailTable\">";
		sOut += "<tr><td>"+aData[9]+"</td></tr>";
		sOut += "</table>";
	
		return sOut;
	}
	
	/* Event handler function */
function fnOpenClose ( oSettings )
{
	$("td img", oTable.fnGetNodes() ).each( function () {
		$(this).click( function () {
			var nTr = this.parentNode.parentNode;
			if ( this.src.match("field_expand") )
			{
				/* This row is already open - close it */
				this.src = "'.$this->EE->cp->cp_theme_url.'images/field_collapse.png";

				var nRemove = $(nTr).next()[0];
				nRemove.parentNode.removeChild( nRemove );
			}
			else
			{
				/* Open this row */
				this.src = "'.$this->EE->cp->cp_theme_url.'images/field_expand.png";
				oTable.fnOpen( nTr, fnFormatDetails(nTr), "details");
			}
		} );
	} );
}
	
			$("#expand_contract").toggle(function () {
					$("[src$=\'field_collapse.png\']").click();
				}, function() {
					$("[src$=\'field_expand.png\']").click();
			});
			
			$("#keywords").keyup( function () {
			/* Filter on the column (the index) of this element */
				oTable.fnDraw();
			});

			$("select#f_channel_id").change(function () {
				oTable.fnDraw();
			});	

			$("select#f_status").change(function () {
				oTable.fnDraw();
			});

			$("select#f_search_in").change(function () {
				oTable.fnDraw();
			});

			$("select#date_range").change(function () {
				oTable.fnDraw();
			});

';

		return $js;
		
	}
	
	function comments_ajax_filter()
	{
		$this->EE->output->enable_profiler(FALSE);
		$this->EE->load->helper('text');
		//$this->EE->load->model('search_model');
		$this->EE->load->model('comment_model');
		$ids = array();
		
				
		$col_map = array('comment', 'comment', 'title', 'channel_title', 'name', 'email', 'comment_date', 'ip_address', 'status');

		// Note- we pipeline the js, so pull more data than are displayed on the page		
		$perpage = $this->EE->input->get_post('iDisplayLength');
		$offset = ($this->EE->input->get_post('iDisplayStart')) ? $this->EE->input->get_post('iDisplayStart') : 0; // Display start point
		$sEcho = $this->EE->input->get_post('sEcho');

		
		/* Ordering */
		$order = array();
		
		if ($this->EE->input->get('iSortCol_0') !== FALSE)
		{
			for ( $i=0; $i < $this->EE->input->get('iSortingCols'); $i++ )
			{
				if (isset($col_map[$this->EE->input->get('iSortCol_'.$i)]))
				{
					$order[$col_map[$this->EE->input->get('iSortCol_'.$i)]] = ($this->EE->input->get('sSortDir_'.$i) == 'asc') ? 'asc' : 'desc';
				}
			}
		}

		$filter = $this->filter_settings($ajax = TRUE);
		
		//  Get comment ids
		
		$comment_id_query = $this->EE->comment_model->get_comment_ids($filter, '', $order);	

		$comment_ids = array_slice($comment_id_query->result_array(), $offset, $perpage);
		
		foreach ($comment_ids as $id)
		{
			$ids[] = $id['comment_id'];
		}
		
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$total = $this->EE->db->count_all_results('comments');
		
		

		$j_response['sEcho'] = $sEcho;
		$j_response['iTotalRecords'] = $total;
		$j_response['iTotalDisplayRecords'] = $comment_id_query->num_rows();
					
		$tdata = array();
		$i = 0;			
		
		$comment_results = $this->EE->comment_model->fetch_comment_data($ids, $order);
		
		// Note- empty string added because otherwise it will throw a js error
		if ($comment_results != FALSE)
		{

			$config = ($this->EE->config->item('comment_word_censoring') == 'y') ? array('word_censor' => TRUE) : array();
		
			$this->EE->load->library('typography');
			$this->EE->typography->initialize($config);
		
			$this->EE->typography->parse_images = FALSE;
			$this->EE->typography->allow_headings = FALSE;
		
			foreach ($comment_results->result_array() as $comment)
			{
				$can_edit_comment = TRUE;

				if (($comment['entry_author_id'] != $this->EE->session->userdata('member_id')) && ! $this->EE->cp->allowed_group('can_edit_all_comments'))
				{
					$can_edit_comment = FALSE;
				}
	
				if ($comment['status'] == 'o')
				{
					$status_label = $this->EE->lang->line('open');
				}
				elseif ($comment['status'] == 'c')
				{
					$status_label = $this->EE->lang->line('closed');
				}
				else
				{
					$status_label = $this->EE->lang->line('pending');
				}
			

				if ($this->comment_leave_breaks == 'y')
				{
					$display_comment = str_replace(array("\n","\r"),
												  '<br />',
												  strip_tags($comment['comment'])
												  );
				}
				else
				{
					$display_comment = strip_tags(str_replace(array("\t","\n","\r"), ' ', $comment['comment']));
				}

				if ($this->comment_chars != 0)
				{
					$display_comment = $this->EE->functions->char_limiter(trim($display_comment), $this->comment_chars);
				}
			

				$full_comment = $this->EE->typography->parse_type($comment['comment'],
					array(
						'text_format'	=> $comment['comment_text_formatting'],
						'html_format'	=> $comment['comment_html_formatting'],
						'auto_links'	=> $comment['comment_auto_link_urls'],
						'allow_img_url' => $comment['comment_allow_img_urls']
						)
					);
			
				$edit_url = $this->base_url.AMP.'method=edit_comment_form'.AMP.'comment_id='.$comment['comment_id'];
				$status_search_url = $this->base_url.AMP.'status='.$comment['status'];
				$ip_search_url = $this->base_url.AMP.'ip_address='.base64_encode($comment['ip_address']);
				$channel_search_url = $this->base_url.AMP.'channel_id='.$comment['channel_id'];
				$email_search_url = $this->base_url.AMP.'email='.base64_encode($comment['email']);

				$mail_to = ($comment['email'] != '') ? mailto($comment['email']) : FALSE;
				$name_search_url = $this->base_url.AMP.'name='.base64_encode($comment['name']);
				$date = $this->EE->localize->set_human_time($comment['comment_date']);
				$entry_search_url = $this->base_url.AMP.'entry_id='.$comment['entry_id'];
				$entry_title = $this->EE->functions->char_limiter(trim(strip_tags($comment['title'])), 26);
			
				$expand_img = '<img src="'.$this->EE->cp->cp_theme_url.'images/field_collapse.png" alt="expand" />';
			
				$m[] = $expand_img;
				$m[] = "<a class='less_important_link' href='{$edit_url}'>{$display_comment}</a>";
				$m[] = "<a class='less_important_link' href='{$entry_search_url}'>{$entry_title}</a>";
				$m[] = "<a class='less_important_link' href='{$channel_search_url}'>{$comment['channel_title']}</a>";
				$m[] = "<a class='less_important_link'  href='{$name_search_url}'>{$comment['name']}</a>";
				$m[] = "<a class='less_important_link'  href='{$email_search_url}'>{$comment['email']}</a>";
				$m[] = ( ! is_null($date)) ? $date : '';
				$m[] = "<a class='less_important_link' href='{$ip_search_url}'>{$comment['ip_address']}</a>";
				$m[] = "<a class='less_important_link' href='{$status_search_url}'>{$status_label}</a>";
				$m[] = ( ! is_null($full_comment)) ? $full_comment : '';
				$m[] = '<input class="comment_toggle" type="checkbox" name="toggle[]" value="'.$comment['comment_id'].'" />';

				$tdata[$i] = $m;
				$i++;
				unset($m);
			}		
		} // end false check
	
	
		$j_response['aaData'] = $tdata;	
		$sOutput = $this->EE->javascript->generate_json($j_response, TRUE);
	
		die($sOutput);
	}



	function filter_settings($ajax = FALSE)
	{
		// Load the search helper so we can filter the keywords
		$this->EE->load->helper('search');	
		$keywords = '';

		if ($this->EE->input->post('keywords')) 
		{
			$keywords = $this->EE->input->get_post('keywords');
		}
		elseif ($this->EE->input->get('keywords'))
		{
			$keywords = base64_decode($this->EE->input->get('keywords'));
		}
		
		$channel_id = ($this->EE->input->get_post('channel_id') && $this->EE->input->get_post('channel_id') != 'null') ? $this->EE->input->get_post('channel_id') : '';

		$filter_on['status']= $this->EE->input->get_post('status');
		$filter_on['order']	= $this->EE->input->get_post('order');
		$filter_on['date_range'] = $this->EE->input->get_post('date_range');	
		$filter_on['name'] = ($this->EE->input->get('name')) ? sanitize_search_terms(base64_decode($this->EE->input->get('name'))) : 	$this->EE->input->post('name');		
		$filter_on['keywords'] = $keywords;
		$filter_on['search_in'] = $this->EE->input->get_post('search_in');
		$filter_on['channel_id'] = $this->EE->input->get_post('channel_id');
		$filter_on['date_range'] = $this->EE->input->get_post('date_range');
		$filter_on['ip_address'] = ($this->EE->input->get('ip_address')) ? sanitize_search_terms(base64_decode($this->EE->input->post('ip_address'))) : $this->EE->input->post('ip_address');	
		$filter_on['email'] = ($this->EE->input->get('email')) ? base64_decode($this->EE->input->post('email')) : $this->EE->input->post('email');	
		
			
		$filter_on['entry_id'] = $this->EE->input->get_post('entry_id');
		$filter_on['comment_id'] = $this->EE->input->get_post('comment_id');
		$filter_on['limit'] = $this->perpage;
		
		//  Because you can specify some extra gets- let's translate that back to search_in/keywords
		
		if ($this->EE->input->get('entry_id'))
		{
			$filter_on['search_in'] = 'entry_title';
			
			$this->EE->db->select('title');
			$this->EE->db->where('entry_id', $this->EE->input->get('entry_id'));
			$query = $this->EE->db->get('channel_titles');
			
			$row = $query->row();

			$filter_on['keywords'] = $row->title;
		}
		elseif($this->EE->input->get('name'))
		{
			$filter_on['search_in'] = 'name';
			$filter_on['keywords'] = base64_decode($this->EE->input->get('name'));
		}
		elseif($this->EE->input->get('email'))
		{
			$filter_on['search_in'] = 'email';
			$filter_on['keywords'] = base64_decode($this->EE->input->get('email'));
		}
		elseif($this->EE->input->get('ip_address'))
		{
			$filter_on['search_in'] = 'ip_address';
			$filter_on['keywords'] = base64_decode($this->EE->input->get('ip_address'));
		}

		//  Create the get variables for non-js pagination

		// Post variables: search_in, keywords*, channel_id, status, date_range
		// Get variables: entry_id, channel_id, name, email*, ip_address* and status 

		$url = array('search_in' => $filter_on['search_in']);
		$filter_on['search_form_hidden'] = array();
		
		foreach ($filter_on as $name => $value)
		{
			if($this->EE->input->post($name) && $this->EE->input->post($name) != '')
			{
				$v = ($name == 'keywords') ? base64_encode($this->EE->input->post($name)) : $this->EE->input->post($name);
				
				$url[$name] = $name.'='.$v;
			}
			elseif ($this->EE->input->get($name))
			{
				$url[$name] = $name.'='.$this->EE->input->get($name);
			}
		}
		
		if ( ! isset($url['keywords']))
		{
			unset($url['search_in']);
		}
		
		$this->search_url = implode(AMP, $url);

		return $filter_on;
	}


	function create_filter($filter)
	{
		if ( ! $this->EE->cp->allowed_group('can_moderate_comments') && ! $this->EE->cp->allowed_group('can_edit_all_comments') && ! $this->EE->cp->allowed_group('can_edit_own_comments'))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		// Channel selection pull-down menu
		// Fetch the names of all channels and write each one in an <option> field

		$fields = array('channel_title', 'channel_id', 'cat_group');
		$where = array();
		
		//  We only limit to channels they are assigned to if they can't moderate and can't edit all
		if ( ! $this->EE->cp->allowed_group('can_moderate_comments') && ! $this->EE->cp->allowed_group('can_edit_all_comments'))
		{
			$query = $this->EE->channel_model->get_channels($this->EE->config->item('site_id'), $fields, $where);
		}
		else
		{
			$this->EE->db->select('channel_title, channel_id, cat_group');
			$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
			$this->EE->db->order_by('channel_title');
		
			$query = $this->EE->db->get('channels'); 
		}		
		
		$vars = array(
			'channel_selected'			=> $filter['channel_id'],
			'channel_select_options'	=> array('' => $this->EE->lang->line('filter_by_channel'))
		);
		
		if ($query->num_rows() > 1)
		{
			$vars['channel_select_options']['all'] = $this->EE->lang->line('all');
		}

		foreach ($query->result_array() as $row)
		{
			$vars['channel_select_options'][$row['channel_id']] = $row['channel_title'];
		}

		// Status pull-down menu
		$vars['status_selected'] = $filter['status'];
		
		$vars['status_select_options'][''] = $this->EE->lang->line('filter_by_status');
		$vars['status_select_options']['all'] = $this->EE->lang->line('all');

	 	$vars['status_select_options']['p'] = $this->EE->lang->line('pending');
		$vars['status_select_options']['o'] = $this->EE->lang->line('open');
		$vars['status_select_options']['c'] = $this->EE->lang->line('closed');

		// Date range pull-down menu
		$vars['date_selected'] = $filter['date_range'];

		$vars['date_select_options'][''] = $this->EE->lang->line('date_range');
		$vars['date_select_options']['1'] = $this->EE->lang->line('past_day');
		$vars['date_select_options']['7'] = $this->EE->lang->line('past_week');
		$vars['date_select_options']['31'] = $this->EE->lang->line('past_month');
		$vars['date_select_options']['182'] = $this->EE->lang->line('past_six_months');
		$vars['date_select_options']['365'] = $this->EE->lang->line('past_year');
		$vars['date_select_options']['custom_date'] = $this->EE->lang->line('any_date');

		$vars['search_form'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment';

		
		$vars['keywords'] = $filter['keywords'];
		
		$vars['search_in_options']['comment'] =  $this->EE->lang->line('search_in_comments');
		$vars['search_in_options']['ip_address'] =  $this->EE->lang->line('search_in_ips');
		$vars['search_in_options']['email'] =  $this->EE->lang->line('search_in_emails');
		$vars['search_in_options']['name'] =  $this->EE->lang->line('search_in_names');	
		$vars['search_in_options']['entry_title'] =  $this->EE->lang->line('search_in_entry_titles');			
		
		$vars['keywords'] = $filter['keywords'];
		$vars['search_in_selected'] = $filter['search_in'];

		$vars['search_form_hidden'] = array();
		
		return $vars;
		
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Comment Notification
	 *
	 * @access	public
	 * @return	string
	 */
	function delete_comment_notification()
	{
		if ( ! $id = $this->EE->input->get_post('id') OR ! $hash = $this->EE->input->get_post('hash'))
		{
			return FALSE;
		}

		if ( ! is_numeric($id))
		{
			return FALSE;
		}

		$this->EE->lang->loadfile('comment');

		$this->EE->load->library('subscription');
		$this->EE->subscription->init('comment', array('subscription_id' => $id), TRUE);
		$this->EE->subscription->unsubscribe('', $hash);

		$data = array(
				'title' 	=> $this->EE->lang->line('cmt_notification_removal'),
				'heading'	=> $this->EE->lang->line('thank_you'),
				'content'	=> $this->EE->lang->line('cmt_you_have_been_removed'),
				'redirect'	=> '',
				'link'		=> array($this->EE->config->item('site_url'), stripslashes($this->EE->config->item('site_name')))
		);

		$this->EE->output->show_message($data);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Edit Comment Form
	 *
	 * @access	public
	 * @return	void
	 */
	function edit_comment_form($comment_id = FALSE)
	{
		if ( ! $this->EE->cp->allowed_group('can_moderate_comments') 
		  && ! $this->EE->cp->allowed_group('can_edit_all_comments') 
		  && ! $this->EE->cp->allowed_group('can_edit_own_comments'))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		$can_edit = FALSE;

		$this->EE->load->library('table');
		$this->EE->load->library('javascript');	

		$this->EE->javascript->output('		

		// If validation fails- want to be sure to show the move field if populated
		if ($("#move_to").val() != "")
		{
			$("#move_link").hide();
			$("#move_field").show();
		}
		
		$("#move_link").click(function() {
			$("#move_link").hide();
			$("#move_field").show();
			return false;
		});
		
		$("#cancel_link").click(function() {
			$("input#move_to").val("");
			$("#move_link").show();
			$("#move_field").hide();
			return false;
		});		
		');


		$this->EE->javascript->compile();
		$comment_id	= ( ! $comment_id) ? $this->EE->input->get_post('comment_id') : $comment_id;


		if ($comment_id == FALSE OR ! is_numeric($comment_id))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		$this->EE->load->helper(array('form', 'snippets'));


		$this->EE->db->select('channel_titles.author_id as entry_author, title, channel_title, comment_require_email, comment, comment_id, comments.author_id, comments.status, name, email, url, location, comments.ip_address, comment_date, channels.comment_text_formatting, channels.comment_html_formatting, channels.comment_allow_img_urls, channels.comment_auto_link_urls');
		$this->EE->db->from(array('channel_titles', 'comments'));
		$this->EE->db->join('channels', 'exp_comments.channel_id = exp_channels.channel_id ', 'left');
		$this->EE->db->where('channel_titles.entry_id = '.$this->EE->db->dbprefix('comments.entry_id'));
		$this->EE->db->where('comments.comment_id', $comment_id);

		$query = $this->EE->db->get();
			
		if ($query->num_rows() === 0)
		{
			return FALSE;
		}

			
		if ( ! $this->EE->cp->allowed_group('can_edit_all_comments'))
		{
			if ($query->row('entry_author') != $this->EE->session->userdata('member_id'))
			{
				if ( ! $this->EE->cp->allowed_group('can_moderate_comments'))
				{
					show_error($this->EE->lang->line('unauthorized_access'));
				}
			}
			else
			{
				$can_edit = TRUE;			
			}
		}
		else
		{
			$can_edit = TRUE;
		}

		$vars = $query->row_array();

		$vars['move_link'] = '';
		$vars['move_to'] = '';
		$vars['can_edit'] = $can_edit;
		
	 	$vars['status_select_options']['p'] = $this->EE->lang->line('pending');
		$vars['status_select_options']['o'] = $this->EE->lang->line('open');
		$vars['status_select_options']['c'] = $this->EE->lang->line('closed');	
		
		$vars['status'] = ($this->EE->input->post('status')) ? $this->EE->input->post('status') : $vars['status'];

		// Instantiate Typography class
		$config = ($this->EE->config->item('comment_word_censoring') == 'y') ? array('word_censor' => TRUE) : array();
		
		$this->EE->load->library('typography');
		$this->EE->typography->initialize($config);
		$this->EE->typography->parse_images = FALSE;

		$vars['display_comment'] = $this->EE->typography->parse_type($vars['comment'],
										array(
												'text_format'	=> $vars['comment_text_formatting'],
												'html_format'	=> $vars['comment_html_formatting'],
												'auto_links'	=> $vars['comment_auto_link_urls'],
												'allow_img_url' => $vars['comment_allow_img_urls']
											)
									);
		
		$hidden = array(
						'comment_id'	=> $comment_id,
						'email'			=> $query->row('email')
						);

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('edit_comment'));

		// a bit of a breadcrumb override is needed
		$this->EE->cp->set_variable('cp_breadcrumbs', array(
			$this->base_url => $this->EE->lang->line('comments')));

		$vars['hidden'] = $hidden;

		$this->EE->javascript->compile();
		
		return $this->EE->load->view('edit', $vars, TRUE);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Update Comment
	 *
	 * @access	public
	 * @return	void
	 */
	function update_comment()
	{
		if ( ! $this->EE->cp->allowed_group('can_moderate_comments') 
		  && ! $this->EE->cp->allowed_group('can_edit_all_comments') 
		  && ! $this->EE->cp->allowed_group('can_edit_own_comments'))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		$comment_id = $this->EE->input->get_post('comment_id');

		if ($comment_id == FALSE OR ! is_numeric($comment_id))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		$this->EE->load->library('form_validation');
		$can_edit = FALSE;
		
		if ($this->EE->cp->allowed_group('can_edit_all_comments'))
		{
			$query = $this->EE->db->get_where('comments', array('comment_id' => $comment_id));
			$can_edit = TRUE;
		}
		else
		{
			$this->EE->db->select('channel_titles.author_id, comments.channel_id, comments.entry_id');
			$this->EE->db->from(array('channel_titles', 'comments'));
			$this->EE->db->where('channel_titles.entry_id = '.$this->EE->db->dbprefix('comments.entry_id'));
			$this->EE->db->where('comments.comment_id', $comment_id);

			$query = $this->EE->db->get();

			if ($query->row('author_id') != $this->EE->session->userdata('member_id'))
			{
				if ( ! $this->EE->cp->allowed_group('can_moderate_comments'))
				{
					show_error($this->EE->lang->line('unauthorized_access'));
				}
				else
				{
					$can_edit = TRUE;			
				}
			}
		}

		if ($query->num_rows() == 0)
		{
			return false;
		}
		
		$row = $query->row_array();

   		$author_id = $row['author_id'];
		$channel_id = $row['channel_id'];
		$entry_id = $row['entry_id'];
		$current_status = $row['status'];
		
		$new_channel_id = $row['channel_id'];
		$new_entry_id = $row['entry_id'];

		//	 Are emails required?
		$this->EE->db->select('channels.comment_require_email');
		$this->EE->db->from(array('channels', 'comments'));
		$this->EE->db->where('comments.channel_id = '.$this->EE->db->dbprefix('channels.channel_id'));
		$this->EE->db->where('comments.comment_id', $comment_id);
		$query = $this->EE->db->get();

		if ($query->num_rows() == 0)
		{
			return show_error($this->EE->lang->line('no_channel_exists'));
		}

		foreach ($query->row_array() as $key => $val)
		{
			$$key = $val;
		}

		$status = $this->EE->input->post('status');

		//  If they can not edit- only the status may change
		if ( ! $can_edit)
		{
			if ( ! in_array($status, array('o', 'c', 'p')))
			{
				show_error($this->EE->lang->line('unauthorized_access'));
			}
			
			$data = array('status' => $status);
			$this->EE->db->query($this->EE->db->update_string('exp_comments', $data, "comment_id = '$comment_id'"));
			
			$this->update_stats(array($entry_id), array($channel_id), array($author_id));

			//  Did status change to open?  Notify
			if ($status == 'o' && $current_status != 'o')
			{
				$this->send_notification_emails(array($comment_id));
			}
			
			$this->EE->functions->clear_caching('all');

			$url = $this->base_url.AMP.'comment_id='.$comment_id;

			$this->EE->session->set_flashdata('message_success',  $this->EE->lang->line('comment_updated'));
			$this->EE->functions->redirect($url);			
		}
		
		// Error checks
		if ($author_id == 0)
		{
			// Fetch language file
			$this->EE->lang->loadfile('myaccount');

			if ($comment_require_email == 'y')
			{
				$this->EE->form_validation->set_rules('email', 'lang:email', 'callback__email_check');
			}
			else
			{
				$this->EE->form_validation->set_rules('email', 'lang:email', '');
			}

			$this->EE->form_validation->set_rules('name', 'lang:name', 'required');
	
		
			$this->EE->form_validation->set_rules('url', '', '');			
			$this->EE->form_validation->set_rules('location', '', '');
		}


		// Are thy moving the comment?  Check for valid entry_id
		$move_to = $this->EE->input->get_post('move_to');
		$recount_ids = array();
		$recount_channels = array();

		if ($move_to != '')
		{
			$tcount = 0;
			
			if (ctype_digit($move_to))
			{
				$this->EE->db->select('title, entry_id, channel_id');
				$this->EE->db->where('entry_id', $move_to);
				$query = $this->EE->db->get('channel_titles');
			
				$tcount = $query->num_rows();
			}


			if ($tcount == 0)
			{
				$this->EE->form_validation->set_rules('move_to', 'lang:move_to', 'callback__move_check');
			}
			else
			{
				$row = $query->row();
			
				$new_entry_id = $row->entry_id;
				$new_channel_id = $row->channel_id;

				$recount_ids[] = $entry_id;
				$recount_channels[] = $channel_id;

				$recount_ids[] = $row->entry_id;
				$recount_channels[] = $row->channel_id;
			}
		}

		
		$this->EE->form_validation->set_rules('comment', 'lang:comment', 'required');

		$this->EE->form_validation->set_error_delimiters('<br /><span class="notice">', '<br />');
		
		if ($this->EE->form_validation->run() === FALSE)
		{
			return $this->edit_comment_form($comment_id);
		}		

		// Build query

		if ($author_id == 0)
		{
			$data = array(
							'entry_id' => $new_entry_id,
							'channel_id' => $new_channel_id,
							'name'		=> $this->EE->input->post('name'),
							'email'		=> $this->EE->input->post('email'),
							'url'		=> $this->EE->input->post('url'),
							'location'	=> $this->EE->input->post('location'),
							'comment'	=> $this->EE->input->post('comment'),
							'status'	=> $status
						 );
		}
		else
		{
			$data = array(
							'entry_id' => $new_entry_id,
							'channel_id' => $new_channel_id,
							'comment'	=> $this->EE->input->post('comment'),
							'status'	=> $status
						 );
		}
		
		$this->EE->db->query($this->EE->db->update_string('exp_comments', $data, "comment_id = '$comment_id'"));

		if ($status != $current_status)
		{
			$this->update_stats(array($entry_id), array($channel_id), array($author_id));

			//  Did status change to open?  Notify
			if ($status == 'o' && $current_status != 'o')
			{
				$this->send_notification_emails(array($comment_id));
			}			
		}


		if (count($recount_ids) > 0)
		{
			$this->EE->load->model('comment_model');

			$this->EE->comment_model->recount_entry_comments($recount_ids);
			
			// Quicker and updates just the channels
			foreach(array_unique($recount_channels) as $channel_id) 
			{ 
				$this->EE->stats->update_comment_stats($channel_id, '', FALSE); 
			}

			// Updates the total stats
			$this->EE->stats->update_comment_stats();
		}
		

		/* -------------------------------------------
		/* 'update_comment_additional' hook.
		/*  - Add additional processing on comment update.
		*/
			$edata = $this->EE->extensions->call('update_comment_additional', $comment_id, $data);
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		$this->EE->functions->clear_caching('all');

		$url = $this->base_url.AMP.'comment_id='.$comment_id;

		$this->EE->session->set_flashdata('message_success',  $this->EE->lang->line('comment_updated'));
		$this->EE->functions->redirect($url);
	}


	function _email_check($str)
	{
		// Is email missing?
		if ($str == '')
		{
			$this->EE->form_validation->set_message('_email_check', $this->EE->lang->line('missing_email'));
			return FALSE;

		}

		// Is email valid?
		$this->EE->load->helper('email');
		
		if ( ! valid_email($str))
		{
			$this->EE->form_validation->set_message('_email_check', $this->EE->lang->line('invalid_email_address'));
			return FALSE;
		}

		// Is email banned?
		if ($this->EE->session->ban_check('email', $str))
		{
			$this->EE->form_validation->set_message('_email_check', $this->EE->lang->line('banned_email'));
			return FALSE;
		}
		
		return TRUE;
	}
	
	function _move_check($str)
	{
		// failed by definition
		$this->EE->form_validation->set_message('_move_check', $this->EE->lang->line('invalid_entry_id'));
		return FALSE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Modify Comments
	 *
	 * @access	public
	 * @return	void
	 */
	function modify_comments()
	{
		// This only happens if they submit with no comments checked, so we send
		// them home.
		if ( ! $this->EE->input->post('toggle') && ! $this->EE->input->get_post('comment_id'))
		{
			$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('no_valid_selections'));
			$this->EE->functions->redirect($this->base_url);
		}

		switch($this->EE->input->post('action'))
		{
			case 'open':
				$this->change_comment_status('o');
			break;
			case 'close':
				$this->change_comment_status('c');
			break;
			case 'pending':
				$this->change_comment_status('p');
			break;			
			default:
				return $this->delete_comment_confirm();
			break;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Comments Confirmation
	 *
	 * @access	public
	 * @return	void
	 */
	function delete_comment_confirm()
	{
		if ( ! $this->EE->cp->allowed_group('can_delete_all_comments') 
		  && ! $this->EE->cp->allowed_group('can_delete_own_comments'))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		$this->EE->cp->get_installed_modules();
		
		$blacklist_installed =  (isset($this->EE->cp->installed_modules['blacklist'])) ? TRUE : FALSE;

		if ( ! $this->EE->input->post('toggle') && ! $this->EE->input->get_post('comment_id'))
		{
			$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('no_valid_selections'));
			$this->EE->functions->redirect($this->base_url);
		}

		$this->EE->load->library('table');
		$comments = array();

		if ($this->EE->input->post('toggle'))
		{
			foreach ($_POST['toggle'] as $key => $val)
			{
				$comments[] = $val;
			}
		}
		
		if ($this->EE->input->get_post('comment_id') !== FALSE && is_numeric($this->EE->input->get_post('comment_id')))
		{
			$comments[] = $this->EE->input->get_post('comment_id');
		}

		if (count($comments) == 0)
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		$this->EE->db->select('channel_titles.author_id, title, comments.comment_id, comment, comments.ip_address');
		$this->EE->db->from(array('channel_titles', 'comments'));
		$this->EE->db->where('channel_titles.entry_id = '.$this->EE->db->dbprefix('comments.entry_id'));
		$this->EE->db->where_in('comments.comment_id', $comments);

		$comments	= array();

		$query = $this->EE->db->get();

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				if ( ! $this->EE->cp->allowed_group('can_delete_all_comments')  && ($row['author_id'] != $this->EE->session->userdata('member_id')))
				{					
					continue;
				}
				
				$row['comment'] = strip_tags(str_replace(array("\t","\n","\r"), ' ', $row['comment']));
				$row['comment'] = $this->EE->functions->char_limiter(trim($row['comment']), 100);


				$comments[$row['comment_id']]['entry_title'] = $row['title'];
				$comments[$row['comment_id']]['comment'] = $row['comment'];
				$comments[$row['comment_id']]['ip_address'] = $row['ip_address'];
			}
		}


		if (count($comments) == 0)
		{
			$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('no_valid_selections'));
			$this->EE->functions->redirect($this->base_url);
		}

		$this->EE->load->helper('form');
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('delete_confirm'));

		$this->EE->cp->set_variable('cp_breadcrumbs', array(
			$this->base_url => $this->EE->lang->line('comments'),

		));
		
		$vars = array();

		$vars['hidden'] = array(
								'comment_ids'	=> implode('|', array_keys($comments))
								);
								
		$vars['blacklist_installed'] = (isset($this->EE->cp->installed_modules['blacklist'])) ? TRUE : FALSE;
								
		$message = (count($comments) > 1) ? 'delete_comments_confirm' : 'delete_comment_confirm';

		$vars['comments'] = $comments;
		$vars['message'] = $message;
		return $this->EE->load->view('delete_comments', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Change Comment Status
	 *
	 * @access	public
	 * @param	string	new status
	 * @return	void
	 */
	function change_comment_status($status = '')
	{
		if ( ! $this->EE->cp->allowed_group('can_moderate_comments') 
		  && ! $this->EE->cp->allowed_group('can_edit_all_comments') 
		  && ! $this->EE->cp->allowed_group('can_edit_own_comments'))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		$comments	= array();
		
		if (isset($_POST['toggle']) && is_array($_POST['toggle']))
		{
			foreach ($_POST['toggle'] as $key => $val)
			{
				$comments[$val] = $val;
			}
		}

		if($this->EE->input->get_post('comment_id') !== FALSE && is_numeric($this->EE->input->get_post('comment_id')))
		{
			$comments[$this->EE->input->get_post('comment_id')] = $this->EE->input->get_post('comment_id');
		}

		if (count($comments) == 0)
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		if ($status == '')
		{
			$status = $this->EE->input->get('status');
		}
		
		if ( ! in_array($status, array('o', 'c', 'p')))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		$this->EE->db->select('exp_comments.entry_id, exp_comments.channel_id, exp_comments.author_id, comment_id, exp_channel_titles.author_id AS entry_author');
		$this->EE->db->join('channel_titles', 'exp_comments.entry_id = exp_channel_titles.entry_id', 'left');
		$this->EE->db->where_in('comment_id', $comments);
		$query = $this->EE->db->get('comments');

		// Retrieve Our Results

		if ($query->num_rows() == 0)
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		$entry_ids	= array();
		$author_ids = array();
		$channel_ids = array();


		foreach($query->result_array() as $row)
		{
			if (( ! $this->EE->cp->allowed_group('can_moderate_comments') 
			   && ! $this->EE->cp->allowed_group('can_edit_all_comments')) 
			   && ($row['entry_author'] != $this->EE->session->userdata('member_id')))
			{					
				unset($comments[$row['comment_id']]);
				continue;
			}

			$entry_ids[]  = $row['entry_id'];
			$author_ids[] = $row['author_id'];
			$channel_ids[] = $row['channel_id'];
		}
		
		if (count($comments) == 0)
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}		
		

		$entry_ids	= array_unique($entry_ids);
		$author_ids = array_unique($author_ids);
		$channel_ids = array_unique($channel_ids);

		/** -------------------------------
		/**	 Change Status
		/** -------------------------------*/

		$this->EE->db->set('status', $status);
		$this->EE->db->where_in('comment_id', $comments);
		$this->EE->db->update('comments');
		
		$this->update_stats($entry_ids, $channel_ids, $author_ids);
		
		//	 Send email notification or remove notifications

		if ($status == 'o')
		{
			$this->send_notification_emails($comments);
		}

		$this->EE->functions->clear_caching('all');

		$url = $this->base_url;

		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('status_changed'));
		$this->EE->functions->redirect($url);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Comment
	 *
	 * @access	public
	 * @return	void
	 */
	function delete_comment()
	{
		if ( ! $this->EE->cp->allowed_group('can_delete_all_comments') && ! $this->EE->cp->allowed_group('can_delete_own_comments'))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		$comment_id = $this->EE->input->post('comment_ids');

		if ($comment_id == FALSE)
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}


		if ( ! preg_match("/^[0-9]+$/", str_replace('|', '', $comment_id)))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}
		
		$this->EE->db->where_in('comment_id', explode('|', $comment_id));
		$count = $this->EE->db->count_all_results('comments');

		if ($count == 0)
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}
	
		$this->EE->cp->get_installed_modules();
		
		$blacklist_installed =  (isset($this->EE->cp->installed_modules['blacklist'])) ? TRUE : FALSE;

		$this->EE->db->select('channel_titles.author_id, channel_titles.entry_id, channel_titles.channel_id, channel_titles.comment_total, comments.ip_address');
		$this->EE->db->from(array('channel_titles', 'comments'));
		$this->EE->db->where('channel_titles.entry_id = '.$this->EE->db->dbprefix('comments.entry_id'));
		$this->EE->db->where_in('comments.comment_id', explode('|', $comment_id));

		$query = $this->EE->db->get();

		if ($query->num_rows() == 0)
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		$entry_ids	= array();
		$author_ids = array();
		$channel_ids = array();
		$bad_ips = array();

		foreach($query->result_array() as $row)
		{
			$entry_ids[]  = $row['entry_id'];
			$author_ids[] = $row['author_id'];
			$channel_ids[] = $row['channel_id'];
			$bad_ips[] = $row['ip_address'];
		}

		$entry_ids	= array_unique($entry_ids);
		$author_ids = array_unique($author_ids);
		$channel_ids = array_unique($channel_ids);
		$ips['ip'] = array_unique($bad_ips);
		unset($bad_ips);


		if ( ! $this->EE->cp->allowed_group('can_delete_all_comments'))
		{
			foreach($query->result_array() as $row)
			{
				if ($row['author_id'] != $this->EE->session->userdata('member_id'))
				{
					show_error($this->EE->lang->line('unauthorized_access'));
				}
			}
		}

		// If blacklist was checked- blacklist!
		if ($blacklist_installed && $this->EE->input->post('add_to_blacklist') == 'y')
		{
			include_once PATH_MOD.'blacklist/mcp.blacklist'.EXT;

			$bl = new Blacklist_mcp();
			
			// Write to htaccess?
			$write_htacces = ($this->EE->session->userdata('group_id') == '1' && $this->EE->config->item('htaccess_path') != '')	? TRUE : FALSE;		
			
			$blacklisted = $bl->update_blacklist($ips, $write_htacces, 'bool');
		}


		$comment_ids = explode('|', $comment_id);

		/* -------------------------------------------
		/* 'delete_comment_additional' hook.
		/*  - Add additional processing on comment delete
		*/
			$edata = $this->EE->extensions->call('delete_comment_additional', $comment_ids);
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

		$this->EE->db->where_in('comment_id', $comment_ids);
		$this->EE->db->delete('comments');
		
		$this->update_stats($entry_ids, $channel_ids, $author_ids);


		$msg = $this->EE->lang->line('comment_deleted');

		$this->EE->functions->clear_caching('all');

		$this->EE->session->set_flashdata('message_success', $msg);

		$this->EE->functions->redirect($this->base_url);

	}
	
	// --------------------------------------------------------------------

	/**
	 * Send Notification Emails
	 *
	 * @access	public
	 * @return	void
	 */
	function send_notification_emails($comments)
	{
		// Load subscription class
		$this->EE->load->library('subscription');
			
		// Instantiate Typography class
		$config = ($this->EE->config->item('comment_word_censoring') == 'y') ? array('word_censor' => TRUE) : array();
	
		$this->EE->load->library('typography');
		$this->EE->typography->initialize($config);
		$this->EE->typography->parse_images = FALSE;


		// Grab the required comments
		$this->EE->db->select('comment, comment_id, author_id, name, email, comment_date, entry_id');
		$this->EE->db->where_in('comment_id', $comments);
		$query = $this->EE->db->get('comments');


		// Sort based on entry
		$entries = array();
		
		foreach ($query->result() as $row)
		{
			if ( ! isset($entries[$row->entry_id]))
			{
				$entries[$row->entry_id] = array();
			}
			
			$entries[$row->entry_id][] = $row;
		}
		

		// Go through the entries and send subscriptions
		
		foreach ($entries as $entry_id => $comments)
		{
			$this->EE->subscription->init('comment', array('entry_id' => $entry_id), TRUE);
			
			// Grab them all
			$subscriptions = $this->EE->subscription->get_subscriptions();
			
			$this->EE->load->model('comment_model');
			$recipients = $this->EE->comment_model->fetch_email_recipients($entry_id, $subscriptions);
			
			if (count($recipients))
			{
				// Grab generic entry info
				
				$action_id	= $this->EE->functions->fetch_action_id('Comment_mcp', 'delete_comment_notification');

				$this->EE->db->select('channel_titles.title, channel_titles.entry_id, channel_titles.url_title, channels.channel_title, channels.comment_url, channels.channel_url, channels.channel_id');
				$this->EE->db->join('channels', 'exp_channel_titles.channel_id = exp_channels.channel_id', 'left');
				$this->EE->db->where('channel_titles.entry_id', $entry_id);
				$results = $this->EE->db->get('channel_titles');		

				$com_url = ($results->row('comment_url')  == '') ? $results->row('channel_url')	 : $results->row('comment_url');				
				
				
				// Create an array of comments to add to the email
				
				$comment_swap = array();
				
				foreach ($comments as $c)
				{
					$comment_text = $this->EE->typography->parse_type(
						$c->comment,
						array(
							'text_format'	=> 'none',
							'html_format'	=> 'none',
							'auto_links'	=> 'n',
							'allow_img_url' => 'n'
						)
					);
					
					$comments_swap[] = array(
						'name_of_commenter'	=> $c->name,
						'name'				=> $c->name,
						'comment'			=> $comment_text,
						'comment_id'		=> $c->comment_id,
					);
				}
				
				
				$swap = array(
					'channel_name'					=> $results->row('channel_title'),
					'entry_title'					=> $results->row('title'),
					'site_name'						=> stripslashes($this->EE->config->item('site_name')),
					'site_url'						=> $this->EE->config->item('site_url'),
					'comment_url'					=> $this->EE->functions->remove_double_slashes($com_url.'/'.$results->row('url_title') .'/'),
					'channel_id'					=> $results->row('channel_id'),
					'entry_id'						=> $results->row('entry_id'),
					'url_title'						=> $results->row('url_title'),
					'comment_url_title_auto_path'	=> reduce_double_slashes($com_url.'/'.$results->row('url_title')),
					
					'comments'						=> $comments_swap
				);
				
				$template = $this->EE->functions->fetch_email_template('comments_opened_notification');
				
				$this->EE->load->library('template');
				
				
				$email_tit = $this->EE->template->parse_variables_row($template['title'], $swap);
				$email_msg = $this->EE->template->parse_variables_row($template['data'], $swap);

				//	Send email
				$this->EE->load->library('email');
				$this->EE->email->wordwrap = true;

				// Load the text helper
				$this->EE->load->helper('text');

				$sent = array();

				foreach ($recipients as $val)
				{
					if ( ! in_array($val['0'], $sent))
					{
						$title	 = $email_tit;
						$message = $email_msg;

						$sub	= $subscriptions[$val['1']];
						$sub_qs	= 'id='.$sub['subscription_id'].'&hash='.$sub['hash'];

						// Deprecate the {name} variable at some point
						$title	 = str_replace('{name}', $val['2'], $title);
						$message = str_replace('{name}', $val['2'], $message);

						$title	 = str_replace('{name_of_recipient}', $val['2'], $title);
						$message = str_replace('{name_of_recipient}', $val['2'], $message);

						$title	 = str_replace('{notification_removal_url}', $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&'.$sub_qs, $title);
						$message = str_replace('{notification_removal_url}', $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&'.$sub_qs, $message);

						$this->EE->email->EE_initialize();
						$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
						$this->EE->email->to($val['0']);
						$this->EE->email->subject($title);
						$this->EE->email->message(entities_to_ascii($message));
						$this->EE->email->send();

						$sent[] = $val['0'];
					}
				}
			}
		}

		return;
	}
		
	// --------------------------------------------------------------------

	/**
	 * Update Entry and Channel Stats
	 *
	 * @access	public
	 * @return	void
	 */
	function update_stats($entry_ids, $channel_ids, $author_ids)
	{
		foreach($entry_ids as $entry_id)
		{
			$query = $this->EE->db->query("SELECT MAX(comment_date) AS max_date FROM exp_comments WHERE status = 'o' AND entry_id = '".$this->EE->db->escape_str($entry_id)."'");

			$comment_date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;

			$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '".$this->EE->db->escape_str($entry_id)."' AND status = 'o'");

			$this->EE->db->set('comment_total', $query->row('count'));
			$this->EE->db->set('recent_comment_date', $comment_date);
			$this->EE->db->where('entry_id', $entry_id);
			$this->EE->db->update('channel_titles');
		}

		// Quicker and updates just the channels
		foreach($channel_ids as $channel_id)
		{
			$this->EE->stats->update_comment_stats($channel_id, '', FALSE);
		}

		// Updates the total stats
		$this->EE->stats->update_comment_stats();

		foreach($author_ids as $author_id)
		{
			$res = $this->EE->db->query("SELECT COUNT(comment_id) AS comment_total, MAX(comment_date) AS comment_date FROM exp_comments WHERE author_id = '$author_id'");
			$resrow = $res->row_array();

			$comment_total = $resrow['comment_total'] ;
			$comment_date  = ( ! empty($resrow['comment_date'])) ? $resrow['comment_date'] : 0;

			$this->EE->db->query($this->EE->db->update_string('exp_members', array('total_comments' => $comment_total, 'last_comment_date' => $comment_date), "member_id = '$author_id'"));
		}
		
		return;
	}
	

	// --------------------------------------------------------------------

	/**
	 * Settings page
	 *
	 * @access	public
	 * @return	void
	 */
	function settings()
	{
		if ( ! $this->EE->cp->allowed_group('can_moderate_comments') && ! $this->EE->cp->allowed_group('can_edit_all_comments') && ! $this->EE->cp->allowed_group('can_delete_all_comments'))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		$this->EE->load->library('table');
		$this->EE->load->library('javascript');
		$this->EE->load->helper('form');


		$vars = array('action_url' => 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=save_settings'
		);

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('comment_settings'));

		$this->EE->cp->set_variable('cp_breadcrumbs', array(
			$this->base_url => $this->EE->lang->line('comments')));		
		
		$vars['comment_word_censoring']			= ($this->EE->config->item('comment_word_censoring') == 'y') ? TRUE : FALSE;
		$vars['comment_moderation_override']	= ($this->EE->config->item('comment_moderation_override') == 'y') ? TRUE : FALSE;
		$vars['comment_edit_time_limit']	= ($this->EE->config->item('comment_edit_time_limit') && ctype_digit($this->EE->config->item('comment_edit_time_limit'))) ? $this->EE->config->item('comment_edit_time_limit') : 0;		

		return $this->EE->load->view('settings', $vars, TRUE);		
	}
	
	// --------------------------------------------------------------------

	/**
	 * Update Comment Settings
	 *
	 * @access	public
	 * @return	void
	 */	
	function save_settings()
	{
		if ( ! $this->EE->cp->allowed_group('can_moderate_comments') && ! $this->EE->cp->allowed_group('can_edit_all_comments') && ! $this->EE->cp->allowed_group('can_delete_all_comments'))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}
		
		$timelimit = $this->EE->input->post('comment_edit_time_limit');
		
		$insert['comment_word_censoring'] = ($this->EE->input->post('comment_word_censoring')) ? 'y' : 'n';
		$insert['comment_moderation_override'] = ($this->EE->input->post('comment_moderation_override')) ? 'y' : 'n';
		$insert['comment_edit_time_limit'] = ($timelimit && ctype_digit($timelimit)) ? $timelimit : '';
		
		$this->EE->config->_update_config($insert);


		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('settings_updated'));

		$this->EE->functions->redirect($this->base_url.AMP.'method=settings');

	}
}
// END CLASS

/* End of file mcp.comment.php */
/* Location: ./system/expressionengine/modules/comment/mcp.comment.php */