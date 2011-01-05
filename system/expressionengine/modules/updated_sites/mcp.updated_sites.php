<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Updated Sites Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Updated_sites_mcp {

	var $field_array = array();
	var $group_array = array();
	var $perpage = 100;
	var $pipe_length = 5;
	
	/**
	  *  Constructor
	  */
	function Updated_sites_mcp ($switch = TRUE)
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		
		$this->EE->cp->set_right_nav(array(
		        'updated_sites_create_new' => 
		        BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=updated_sites'.AMP.'method=create'));
		
	}

	// --------------------------------------------------------------------

	/**
	  *  Control Panel homepage
	  */
	function index()
	{
		$this->EE->load->library('table');
		$this->EE->load->library('javascript');
		$this->EE->load->helper('form');

		$this->EE->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}, 3: {sorter: false}},
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

		$vars['cp_page_title'] = $this->EE->lang->line('updated_sites_module_name');

		$api_url = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->cp->fetch_action_id('Updated_sites', 'incoming');

		$this->EE->db->select('updated_sites_pref_name, updated_sites_id');
		$query = $this->EE->db->get('updated_sites');

		$vars['pings'] = array();
		
		$this->EE->javascript->compile();

		if ($query->num_rows() == 0)
		{
			$vars['message'] = $this->EE->lang->line('no_ping_configs');
			return $this->EE->load->view('index', $vars, TRUE);
			exit;
		}

		foreach ($query->result() as $row)
		{
			$vars['pings'][$row->updated_sites_id]['id'] = $row->updated_sites_id;
			$vars['pings'][$row->updated_sites_id]['name'] = $row->updated_sites_pref_name;
			$vars['pings'][$row->updated_sites_id]['url'] = $api_url.'&id='.$row->updated_sites_id;
			$vars['pings'][$row->updated_sites_id]['toggle'] = array(
																			'name'		=> 'toggle[]',
																			'id'		=> 'module_'.$row->updated_sites_id,
																			'value'		=> $row->updated_sites_id,
																			'class'		=>'toggle'
			    														);
		}

		return $this->EE->load->view('index', $vars, TRUE);
	}

	// ------------------------------------------------------------------------

	/**
	  *  Create
	  */
	function create()
	{
		return $this->modify('new');
	}

	// ------------------------------------------------------------------------


	/**
	  *  Modify Configuration
	  */
	function modify($id = '')
	{
		$this->EE->load->library('form_validation');
		$this->EE->load->helper('form');
		$this->EE->load->library('table');

		$id = ( ! $this->EE->input->get('id')) ? $id : $this->EE->input->get_post('id');

		//  Default Form Values
		$vars['updated_sites_pref_name']		= 'Updated Sites';
		$vars['updated_sites_short_name']		= 'updated_sites';
		$vars['updated_sites_allowed']			= '';
		$vars['updated_sites_prune']			= 500;


		$this->EE->jquery->tablesorter('.mainTable', '{
			headers: {6: {sorter: false}},
			widgets: ["zebra"]
		}');
		
		$this->EE->form_validation->set_rules('updated_sites_id',			'lang:updated_sites_id',			'required');
		$this->EE->form_validation->set_rules('updated_sites_pref_name',	'lang:updated_sites_pref_name',		'required');
		$this->EE->form_validation->set_rules('updated_sites_short_name',	'lang:updated_sites_short_name',	'required|callback__check_duplicate');
		$this->EE->form_validation->set_rules('updated_sites_allowed',		'lang:updated_sites_allowed',		'required');
		$this->EE->form_validation->set_rules('updated_sites_prune',		'lang:updated_sites_prune',			'required|is_natural');

		$this->EE->form_validation->set_error_delimiters('<p class="notice">', '</p>');

		$vars['submit_text']	= 'submit'; // button label lang key

		if ($id != 'new')
		{
			$vars['submit_text']	= 'update'; // button label lang key

			$query = $this->EE->db->get_where('updated_sites', array('updated_sites_id' => $id));

			if ($query->num_rows() == 0)
			{
				$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=updated_sites');
			}

			foreach($query->row_array() as $name => $pref)
			{
				$name	= str_replace('blogger_', '', $name);
				$vars["$name"] = $pref;
			}
			
			$this->EE->form_validation->set_old_value('id', $id);
		}

		if ($this->EE->form_validation->run() === FALSE)
		{
			$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=updated_sites', $this->EE->lang->line('updated_sites_module_name'));

			$vars['cp_page_title'] = ($id == 'new') ? $this->EE->lang->line('new_config') : $this->EE->lang->line('modify_config');
			$vars['form_action'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=updated_sites'.AMP.'method='. (($id == 'new') ? 'create' : 'modify'.AMP.'id='.$id);

			$vars['form_hidden']['updated_sites_id'] =$id;

			$vars['updated_sites_allowed'] = str_replace("|", "\n", trim($vars['updated_sites_allowed']));
			
			return $this->EE->load->view('create_modify', $vars, TRUE);
		}
		else
		{
			$data	= array();
			$keys	= array('updated_sites_id', 'updated_sites_pref_name', 'updated_sites_short_name',
							'updated_sites_allowed', 'updated_sites_prune');

			foreach($keys as $var)
			{
				$data[$var] = $_POST[$var];
			}

			if ($_POST['updated_sites_id'] == 'new' )
			{
				unset($data['updated_sites_id']);

				$this->EE->db->insert('updated_sites', $data);

				$message = $this->EE->lang->line('configuration_created');
			}
			else
			{
				$this->EE->db->where('updated_sites_id', $_POST['updated_sites_id']);
				$this->EE->db->update('updated_sites', $data);

				$message = $this->EE->lang->line('configuration_updated');
			}

			$this->EE->session->set_flashdata('message_success', $message);
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=updated_sites');
		}
	}

	// --------------------------------------------------------------------

	/**
	  *  Check for duplicate short names (callback)
	  */
	function _check_duplicate($str)
	{
		$id = $this->EE->form_validation->old_value('id');
		if ($id)
		{
			$this->EE->db->where('updated_sites_id !=', $id);
		}
		
		$this->EE->db->where('updated_sites_short_name', $str);
		
		if ($this->EE->db->count_all_results('updated_sites') > 0)
		{
			$this->EE->form_validation->set_message('_check_duplicate', $this->EE->lang->line('updated_sites_short_name_taken'));
			return FALSE;
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	  *  View Pings
	  */
	function pings($id = '1')
	{
		$this->EE->load->library('pagination');
		$this->EE->load->library('javascript');
		$this->EE->load->library('table');
		$this->EE->load->helper('form');
		$this->EE->load->helper('text');

		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=updated_sites', $this->EE->lang->line('updated_sites_module_name'));

		$vars['cp_page_title'] = $this->EE->lang->line('view_pings');

		$this->EE->cp->add_js_script(array('plugin' => 'dataTables'));

		$id = ( ! $this->EE->input->get('id')) ? $id : $this->EE->input->get_post('id');
		if ($id != '')
		{
			$vars['form_hidden']['config_id'] = $id;
			$id_get = '&id='.$id;
		}

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
			"bFilter": false,
			"sWrapper": false,
			"sInfo": false,
			"bAutoWidth": false,
			"iDisplayLength": '.$this->perpage.',  
			
		"aoColumns": [null, null, null, null, { "bSortable" : false } ],
					
		"oLanguage": {
			"sZeroRecords": "'.$this->EE->lang->line('no_pings').'",
			
			"oPaginate": {
				"sFirst": "<img src=\"'.$this->EE->cp->cp_theme_url.'images/pagination_first_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />",
				"sPrevious": "<img src=\"'.$this->EE->cp->cp_theme_url.'images/pagination_prev_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />",
				"sNext": "<img src=\"'.$this->EE->cp->cp_theme_url.'images/pagination_next_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />", 
				"sLast": "<img src=\"'.$this->EE->cp->cp_theme_url.'images/pagination_last_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />"
			}
		},

			"bProcessing": true,
			"bServerSide": true,
			"sAjaxSource": EE.BASE+"&C=addons_modules&M=show_module_cp&module=updated_sites&method=pings_ajax_filter'.$id_get.'&time=" + time,
			"fnServerData": fnDataTablesPipeline

	} );

		

	');	

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

		$this->EE->cp->add_to_foot('<script type="text/javascript">
			function showHide(entryID, htmlObj, linkType) {

				extTextDivID = ("extText" + (entryID));
				extLinkDivID = ("extLink" + (entryID));

				if (linkType == "close")
				{
					document.getElementById(extTextDivID).style.display = "none";
					document.getElementById(extLinkDivID).style.display = "block";
					htmlObj.blur();
				}
				else
				{
					document.getElementById(extTextDivID).style.display = "block";
					document.getElementById(extLinkDivID).style.display = "none";
					htmlObj.blur();
				}

			}
			</script>');


		$rownum = ($this->EE->input->get_post('rownum') != '') ? $this->EE->input->get_post('rownum') : 0;

		// some page defaults
		$vars['form_hidden'] = '';

		$this->EE->db->where('ping_config_id', $id);
		$vars['ping_count'] = $this->EE->db->count_all_results('updated_site_pings');

		$query = $this->pings_search($id, '', $rownum);
		
		$vars['pings'] = array();

		$site_url = $this->EE->config->item('site_url');

		foreach ($query->result() as $row)
		{
			// Name
			$vars['pings'][$row->ping_id]['name'] = $row->ping_site_name;

			// URL
			$vars['pings'][$row->ping_id]['full_url'] = $this->EE->functions->fetch_site_index().QUERY_MARKER.'URL='.$row->ping_site_url;
			$vars['pings'][$row->ping_id]['display_url'] = character_limiter(str_replace('http://', '', $row->ping_site_url), 40);

			// RSS
			$vars['pings'][$row->ping_id]['rss'] = $row->ping_site_rss;

			// Date
			$vars['pings'][$row->ping_id]['date'] = ($row->ping_date != '' AND $row->ping_date != 0) ? $this->EE->localize->set_human_time($row->ping_date) : '-';

			// delete checkbox
			$vars['pings'][$row->ping_id]['toggle'] = array(
																			'name'		=> 'toggle[]',
																			'id'		=> 'delete_box_'.$row->ping_id,
																			'value'		=> $row->ping_id,
																			'class'		=>'toggle'
																	);

		}

		// Pass the relevant data to the paginate class
		$config['base_url'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=updated_sites'.AMP.'method=pings';
		$config['total_rows'] = $vars['ping_count'];
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

		return $this->EE->load->view('pings', $vars, TRUE);
	}


	function pings_ajax_filter()
	{
		$this->EE->output->enable_profiler(FALSE);
		$this->EE->load->helper('text');
				
		$col_map = array('ping_site_name', 'ping_site_url', 'ping_site_url', 'ping_date');

		$id = ($this->EE->input->get_post('id')) ? $this->EE->input->get_post('id') : '';		


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
		
		$query = $this->pings_search($id, $order, $offset, $perpage);

		$this->EE->db->where('ping_config_id', $id);
		$total = $this->EE->db->count_all_results('updated_site_pings');

		$j_response['sEcho'] = $sEcho;
		$j_response['iTotalRecords'] = $total;
		$j_response['iTotalDisplayRecords'] = $total;
					
		$tdata = array();
		$i = 0;
		
		foreach ($query->result_array() as $ping)
		{
		
			$m[] = $ping['ping_site_name'];
			$m[] =  '<a href="'.$this->EE->functions->fetch_site_index().
			QUERY_MARKER.'URL='.$ping['ping_site_url'].'">'.character_limiter(str_replace('http://', '', $ping['ping_site_url']), 40).'</a>';
			$m[] =	$ping['ping_site_rss'];
			$m[] =	($ping['ping_date'] != '' AND $ping['ping_date'] != 0) ? $this->EE->localize->set_human_time($ping['ping_date']) : '-';
			$m[] = '<input class="toggle" type="checkbox" name="email[]" value="'.$ping['ping_id'].'" />';		

			$tdata[$i] = $m;
			$i++;
			unset($m);
		}		

		$j_response['aaData'] = $tdata;	
		$sOutput = $this->EE->javascript->generate_json($j_response, TRUE);
	
		die($sOutput);
	}


	function pings_search($id = '', $order = array(), $rownum = 0, $perpage = '')
	{
		$perpage = ($perpage == '') ? $this->perpage: $perpage;
		
		$this->EE->db->from('updated_site_pings');
		
		if (is_array($order) && count($order) > 0)
		{
			foreach ($order as $key => $val)
			{
				$this->EE->db->order_by($key, $val);
			}
		}
		else
		{
			$this->EE->db->order_by('ping_date', 'desc');
		}

		$this->EE->db->where('ping_config_id', $id);

		$this->EE->db->limit($perpage, $rownum);
		$query = $this->EE->db->get();
		
		return $query;
	}


	// --------------------------------------------------------------------

	/**
	  *  Delete Pings Confirmation Page
	  */
	function delete_confirm()
	{
		if ( ! $this->EE->input->post('toggle'))
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=updated_sites');
		}

		$id = ( ! $this->EE->input->get('config_id')) ? '1' : $this->EE->input->get_post('config_id');

		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=updated_sites', $this->EE->lang->line('updated_sites_module_name'));

		$vars['cp_page_title'] = $this->EE->lang->line('updated_sites_delete_confirm');
		$vars['question_key'] = 'ml_delete_question';
		$vars['form_hidden']['config_id'] = ( ! $this->EE->input->get('config_id')) ? '1' : $this->EE->input->get_post('config_id');

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
	  *  Delete Ping Configurations
	  */
	function delete_configs()
	{
		$id = ( ! $this->EE->input->get('config_id')) ? '1' : $this->EE->input->get_post('config_id');

		if ( ! $this->EE->input->post('delete'))
		{
			return $this->pings($id);
		}

		//  Delete Configurations
		$this->EE->db->where_in('updated_sites_id', $_POST['delete']);
		$this->EE->db->delete('updated_sites');
		
		$message = (count($_POST['delete']) == 1) ? $this->EE->lang->line('updated_site_deleted') : $this->EE->lang->line('updated_sites_deleted');

		$this->EE->session->set_flashdata('message_success', $message);
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=updated_sites');
	}

	// --------------------------------------------------------------------


	/**
	  *  Delete Pings Confirmation Page
	  */
	function delete_pings_confirm()
	{
		if ( ! $this->EE->input->post('toggle'))
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=updated_sites');
		}

		$id = ( ! $this->EE->input->get('config_id')) ? '1' : $this->EE->input->get_post('config_id');

		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=updated_sites', $this->EE->lang->line('updated_sites_module_name'));

		$vars['cp_page_title'] = $this->EE->lang->line('delete_pings_confirm');
		$vars['question_key'] = 'ml_delete_question';
		$vars['form_hidden']['config_id'] = ( ! $this->EE->input->get('config_id')) ? '1' : $this->EE->input->get_post('config_id');

		$this->EE->load->helper('form');

		foreach ($_POST['toggle'] as $key => $val)
		{
			$vars['damned'][] = $val;
		}

		$this->EE->javascript->compile();

		return $this->EE->load->view('delete_pings_confirm', $vars, TRUE);
	}


	// --------------------------------------------------------------------

	/**
	  *  Delete Pings
	  */
	function delete_pings()
	{
		$id = ( ! $this->EE->input->get('config_id')) ? '1' : $this->EE->input->get_post('config_id');
		
		if ( ! $this->EE->input->post('delete'))
		{
			return $this->pings($id);
		}

		//  Delete Referrers
		$this->EE->db->where_in('ping_id', $_POST['delete']);
		$this->EE->db->delete('updated_site_pings');

		return $this->pings($id, $this->EE->lang->line('pings_deleted'));
	}
}


/* End of file mcp.updated_sites.php */
/* Location: ./system/expressionengine/modules/updated_sites/mcp.updated_sites.php */