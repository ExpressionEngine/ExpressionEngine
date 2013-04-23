<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Metaweblog API Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Metaweblog_api_mcp {

	var $field_array = array();
	var $status_array = array();
	var $group_array = array();

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Metaweblog_api_mcp ($switch = TRUE)
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		ee()->load->helper('form');
	}

	// --------------------------------------------------------------------

	/**
	 * Control Panel Index
	 *
	 * @access	public
	 */
	function index()
	{
		$vars['cp_page_title'] = ee()->lang->line('metaweblog_api_module_name');

		ee()->load->library('table');
		ee()->load->library('javascript');
		ee()->load->helper('form');

		ee()->jquery->tablesorter('.mainTable', '{
			headers: {2: {sorter: false}, 3: {sorter: false}, 4: {sorter: false}, 6: {sorter: false}},
			widgets: ["zebra"]
		}');

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

		$api_url = ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.ee()->cp->fetch_action_id('Metaweblog_api', 'incoming');

		ee()->db->select('metaweblog_pref_name, metaweblog_id');
		$metaweblogs = ee()->db->get('metaweblog_api');

		ee()->javascript->compile();
		
		$vars['metaweblogs'] = array();
		
		if ($metaweblogs->num_rows() == 0)
		{
			return ee()->load->view('index', $vars, TRUE);
			exit;
		}

		foreach ($metaweblogs->result() as $metaweblog)
		{
			$vars['metaweblogs'][$metaweblog->metaweblog_id]['id'] = $metaweblog->metaweblog_id;
			$vars['metaweblogs'][$metaweblog->metaweblog_id]['name'] = $metaweblog->metaweblog_pref_name;
			$vars['metaweblogs'][$metaweblog->metaweblog_id]['url'] = $api_url.'&id='.$metaweblog->metaweblog_id;
			$vars['metaweblogs'][$metaweblog->metaweblog_id]['toggle'] = array(
																			'name'		=> 'toggle[]',
																			'id'		=> 'delete_box_'.$metaweblog->metaweblog_id,
																			'value'		=> $metaweblog->metaweblog_id,
																			'class'		=>'toggle'
			    														);
		}

		return ee()->load->view('index', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Create
	 *
	 * @access	public
	 */
	function create()
	{
		return $this->modify('new');
	}

	// --------------------------------------------------------------------

	/**
	 * Modify Configuration
	 *
	 * @param	int
	 * @access	public
	 */
	function modify($id = '')
	{
		$id = ( ! ee()->input->get('id')) ? $id : ee()->input->get_post('id');

		if ($id == '')
		{
			ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=metaweblog_api');
		}
		
		ee()->load->library('form_validation');
		
		ee()->form_validation->set_rules('metaweblog_id',			'lang:metaweblog_id',						'required');
		ee()->form_validation->set_rules('metaweblog_pref_name',	'lang:metaweblog_pref_name',				'required');
		ee()->form_validation->set_rules('metaweblog_parse_type',	'lang:metaweblog_parse_type',				'required');
		ee()->form_validation->set_rules('entry_status',			'lang:metaweblog_entry_status',				'required');
		ee()->form_validation->set_rules('field_group_id',			'lang:metaweblog_field_group_id',			'required');
		ee()->form_validation->set_rules('excerpt_field_id',		'lang:metaweblog_excerpt_field_id',			'required');
		ee()->form_validation->set_rules('content_field_id',		'lang:metaweblog_content_field_id',			'required');
		ee()->form_validation->set_rules('more_field_id',			'lang:metaweblog_more_field_id',			'required');
		ee()->form_validation->set_rules('keywords_field_id',		'lang:metaweblog_keywords_field_id',		'required');
		ee()->form_validation->set_rules('upload_dir',				'lang:metaweblog_upload_dir',				'required');

		//  Form Values
		$vars['pref_name']			= '';
		$vars['parse_type']			= 'n';
		$vars['entry_status']		= 'null';
		$vars['field_group_id']		= '1';
		$vars['excerpt_field_id']	= '0';
		$vars['content_field_id']	= '1';
		$vars['more_field_id']		= '0';
		$vars['keywords_field_id']	= '0';
		$vars['upload_dir']			= '1';
		$vars['submit_text']	= 'submit';

		if ($id != 'new')
		{
			$query = ee()->db->get_where('metaweblog_api', array('metaweblog_id' => $id));
			$vars['submit_text']	= 'update';

			if ($query->num_rows() == 0)
			{
				ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=metaweblog_api');
			}

			foreach($query->row_array() as $name => $pref)
			{
				$name	= str_replace('metaweblog_', '', $name);
				$vars["$name"] = $pref;
			}
		}

		
		if (ee()->form_validation->run() === FALSE)
		{
			ee()->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=metaweblog_api', ee()->lang->line('metaweblog_api_module_name'));

			$vars['cp_page_title'] = ($id == 'new') ? ee()->lang->line('new_config') : ee()->lang->line('modify_config');
			$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=metaweblog_api'.AMP.'method='.(($id == 'new') ? 'create' : 'modify'.AMP.'id='.$id);
			$vars['form_hidden']['metaweblog_id'] = $id;

			// Filtering Javascript

			$this->filtering_menus();
			ee()->javascript->compile();

			$i=0;
			$style = '';

			// PARSE TYPE
			$vars['metaweblog_parse_type_options'] = array(
													'y'=>ee()->lang->line('yes'),
													'n'=>ee()->lang->line('no')
												);
			$vars['metaweblog_parse_type_selected'] = ($vars['parse_type'] == 'y') ? 'y' : 'n';

			// Entry Status
			$vars['entry_status_options'] = array(
													'null'=>ee()->lang->line('do_not_set_status'),
													'open'=>ee()->lang->line('open'),
													'closed'=>ee()->lang->line('closed')
												);

			foreach($this->status_array as $value)
			{
				$vars['entry_status_options'][$value[1]] = $value[1];
			}

			// FIELD GROUP
			$vars['field_group_id_options'] = array();
			
			foreach($this->group_array as $key => $value)
			{
				$vars['field_group_id_options'][$key] = $value['0'];

			}

			// This field array is used for many of the dropdowns below, so we'll
			// generate it once, and just array_merge() it into the fold.
			$fields_array = array();
			
			foreach($this->field_array as $value)
			{
				if ($value['0'] == $vars['field_group_id'])
				{
					$fields_array[$value['1']] = $value['2'];
				}
			}

			// EXCERPT FIELDS
			$vars['excerpt_field_id_options'] = array(0 => ee()->lang->line('none')) + $fields_array;

			// CONTENT FIELDS
			$vars['content_field_id_options'] = array(0 => ee()->lang->line('none')) + $fields_array;

			/// MORE FIELDS
			$vars['more_field_id_options'] = array(0 => ee()->lang->line('none')) + $fields_array;

			/// KEYWORDS FIELDS
			$vars['keywords_field_id_options'] = array(0 => ee()->lang->line('none')) + $fields_array;


			// UPLOAD DIRECTORIES
			$vars['upload_dir_options'] = array(0=>ee()->lang->line('none'));

			// Any group restrictions?
			if (ee()->session->userdata['group_id'] !== 1)
			{
				ee()->db->select('upload_id');
				$no_access = ee()->db->get_where('upload_no_access', array('member_group' => ee()->session->userdata['group_id']));

				if (ee()->config->item('multiple_sites_enabled') !== 'y')
				{
					ee()->db->where('sites.site_id', 1);
				}

				if ($no_access->num_rows() > 0)
				{
					foreach ($no_access->result() as $row)
					{
						ee()->db->where('id', $row->upload_id);
					}
				}
			}

			// Grab them (the above restrictions still apply)
			ee()->db->select('id, name, site_label');
			ee()->db->from('upload_prefs');
			ee()->db->from('sites');
			ee()->db->where(ee()->db->dbprefix.'upload_prefs.site_id = '.ee()->db->dbprefix.'sites.site_id', NULL, FALSE);
			ee()->db->order_by('name');

			$query = ee()->db->get();

			if ($query->num_rows() > 0)
			{
				foreach($query->result() as $row)
				{
					$vars['upload_dir_options'][$row->id] = (ee()->config->item('multiple_sites_enabled') === 'y') ? $row->site_label.NBS.'-'.NBS.$row->name : $row->name;
				}
			}

			return ee()->load->view('create_modify', $vars, TRUE);
		}
		else
		{
			$fields		= array('metaweblog_id', 'metaweblog_pref_name', 'metaweblog_parse_type', 'entry_status',
								'field_group_id','excerpt_field_id','content_field_id',
								'more_field_id','keywords_field_id','upload_dir');

			$data		= array();

			foreach($fields as $var)
			{
				if ( ! isset($_POST[$var]) OR $_POST[$var] == '')
				{
					return ee()->output->show_user_error('submission', ee()->lang->line('metaweblog_mising_fields'));
				}

				$data[$var] = $_POST[$var];
			}

			if ($_POST['metaweblog_id'] == 'new' )
			{
				unset($data['metaweblog_id']);
				ee()->db->query(ee()->db->insert_string('exp_metaweblog_api', $data));
				$message = ee()->lang->line('configuration_created');
			}
			else
			{
				ee()->db->query(ee()->db->update_string('exp_metaweblog_api', $data, "metaweblog_id = '".ee()->db->escape_str($_POST['metaweblog_id'])."'"));
				$message = ee()->lang->line('configuration_updated');
			}

			ee()->session->set_flashdata('message_success', $message);
			ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=metaweblog_api');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Confirm
	 *
	 * @access	public
	 */
	function delete_confirm()
	{
		if ( ! ee()->input->post('toggle'))
		{
			ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=metaweblog_api');
		}

		ee()->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=metaweblog_api', ee()->lang->line('metaweblog_api_module_name'));

		$vars['cp_page_title'] = ee()->lang->line('metaweblog_delete_confirm');

		foreach ($_POST['toggle'] as $key => $val)
		{
			$vars['damned'][] = $val;
		}

		return ee()->load->view('delete_confirm', $vars, TRUE);

	}

	// --------------------------------------------------------------------

	/**
	 * Delete Configuration(s)
	 *
	 * @access	public
	 */
	function delete()
	{
		if ( ! ee()->input->post('delete'))
		{
			ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=metaweblog_api');
		}

		$ids = array();

		foreach ($_POST['delete'] as $key => $val)
		{
			$ids[] = "metaweblog_id = '".ee()->db->escape_str($val)."'";
		}

		$IDS = implode(" OR ", $ids);

		ee()->db->query("DELETE FROM exp_metaweblog_api WHERE ".$IDS);

		$message = (count($ids) == 1) ? ee()->lang->line('metaweblog_deleted') : ee()->lang->line('metaweblogs_deleted');

		ee()->session->set_flashdata('message_success', $message);
		ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=metaweblog_api');
	}

	// ------------------------------------------------------------------------


	/** -----------------------------------------------------------
	/**  JavaScript filtering code
	/** -----------------------------------------------------------*/
	// This function writes some JavaScript functions that
	// are used to switch the various pull-down menus in the
	// CREATE page
	//-----------------------------------------------------------

	function filtering_menus()
	{
		// In order to build our filtering options we need to gather
		// all the field groups and fields

		$allowed_channels = ee()->functions->fetch_assigned_channels();
		$allowed_groups = array();
		$groups_exist = TRUE;
		
		if ( ! ee()->cp->allowed_group('can_edit_other_entries') && count($allowed_channels) == 0)
		{
			$groups_exist = FALSE;
		}

		/*

		// -----------------------------------
		//  Determine Available Groups
		//
		//  We only allow them to specify
		//  groups that to which they have access
		//  or that are used by a channel currently
		// -----------------------------------

		$groups = array();

		$sql = "SELECT field_group FROM exp_channels ";

		$query = ee()->db->query($sql);

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$groups[] = $row['field_group'];
			}
		}

		$xql = "WHERE group_id IN ('".implode("','", $groups)."'";


		/** -----------------------------
		/**  Channel Field Groups
		/** -----------------------------*/

		ee()->db->select('field_group');
		ee()->db->from('exp_channels');
		
		if ( ! ee()->cp->allowed_group('can_edit_other_entries'))
		{
			ee()->db->where_in('channel_id', $allowed_channels);
		}	
		
		$query = ee()->db->get();

		if ($groups_exist && $query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$allowed_groups[] = $row['field_group'];
			}
		
			ee()->db->select('group_id, group_name, site_label');
			ee()->db->from('field_groups');
			ee()->db->where_in('group_id', $allowed_groups);		
			ee()->db->join('sites', 'sites.site_id = field_groups.site_id');
		
			if (ee()->config->item('multiple_sites_enabled') !== 'y')
			{
				ee()->db->where('field_groups.site_id', '1');
			}
		
			$query = ee()->db->get();

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$label = (ee()->config->item('multiple_sites_enabled') === 'y') ? $row['site_label'].NBS.'-'.NBS.$row['group_name'] : $row['group_name'];
					$this->group_array[$row['group_id']] = array(str_replace('"','',$label), $row['group_name']);
				}
			}
		}  // End gather groups

		/** ----------------------------- 
		/**  Entry Statuses
		/** -----------------------------*/
		
		ee()->db->select('group_id, status');
		ee()->db->where_not_in('status', array('open', 'closed'));
		ee()->db->order_by('status_order');
		$query = ee()->db->get('statuses');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$this->status_array[]  = array($row['group_id'], $row['status']);
			}
		}

		/** ----------------------------- 
		/**  Custom Channel Fields
		/** -----------------------------*/
				
		ee()->db->select('group_id, field_label, field_id');
		ee()->db->order_by('field_label');
		
		ee()->db->where_in('channel_fields.field_type', array('textarea', 'text', 'rte'));
		
		$query = ee()->db->get('channel_fields');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$this->field_array[]  = array($row['group_id'], $row['field_id'], str_replace('"','',$row['field_label']));
			}
		}

		ee()->lang->loadfile('content');
		$channel_info = array();

		foreach ($this->group_array as $key => $val)
		{
			$statuses = array(
				array('null', ee()->lang->line('do_not_set_status')),
				array('open', ee()->lang->line('open')),
				array('closed', ee()->lang->line('closed'))
			);

			if (count($this->status_array) > 0)
			{
				foreach ($this->status_array as $k => $v)
				{
					if ($v['0'] == $key)
					{
						$statuses[] = array($v['1'], $v['1']);
					}
				}
			}

			$channel_info[$key]['statuses'] = $statuses;

			$fields = array();

			$fields[] = array('0', ee()->lang->line('none'));

			if (count($this->field_array) > 0)
			{
				foreach ($this->field_array as $k => $v)
				{
					if ($v['0'] == $key)
					{
						$fields[] = array($v['1'], $v['2']);
					}
				}
			}
	
			$channel_info[$key]['fields'] = $fields;
		}

		$channel_info = json_encode($channel_info);
		$none_text = ee()->lang->line('none');
		
		$javascript = <<<MAGIC

// Whee - json

var channel_map = $channel_info;

var empty_select = new Option("{$none_text}", 'none');

// We prep our magic arrays as soons as we can, basically
// converting everything into option elements
(function() {
	jQuery.each(channel_map, function(key, details) {
		
		// Go through each of the individual settings and build a proper dom element
		jQuery.each(details, function(group, values) {
			var html = new String();
			
			// Add the new option fields
			jQuery.each(values, function(a, b) {
				html += '<option value="' + b[0] + '">' + b[1] + "</option>";
			});

			// Set the new values
			channel_map[key][group] = html;
		});
	});
	
})();


// Change the submenus
// Gets passed the channel id
function changemenu(index)
{
	var channels = 'null';
	
	if (channel_map[index] === undefined) {
		$('select[name=excerpt_field_id], select[name=content_field_id], select[name=more_field_id], select[name=keywords_field_id]').empty().append(empty_select);
	}
	else {
		jQuery.each(channel_map[index], function(key, val) {
			if (key == 'fields')
			{
				$('select[name=excerpt_field_id]').empty().append(val);
				$('select[name=content_field_id]').empty().append(val);
				$('select[name=more_field_id]').empty().append(val);
				$('select[name=keywords_field_id]').empty().append(val);				
			}
		});
	}
}

$('select[name=field_group_id]').change(function() {
	changemenu(this.value);
});
MAGIC;
		ee()->javascript->output($javascript);
	}
}


/* End of file mcp.metaweblog_api.php */
/* Location: ./system/expressionengine/modules/metaweblog_api/mcp.metaweblog_api.php */