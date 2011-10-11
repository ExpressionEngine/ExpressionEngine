<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team, 
 * 		- Original Development by Barrett Newton -- http://barrettnewton.com
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine SafeCracker Extension
 *
 * @package		ExpressionEngine
 * @subpackage	Extensions
 * @category	Extensions
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Safecracker_ext
{
	public $settings = array();
	public $name = 'SafeCracker';
	public $version = '2.1';
	public $description = 'A replacement and enchancement of the Stand-Alone Entry Form';
	public $settings_exist = 'y';
	public $docs_url = 'http://expressionengine.com/user_guide/modules/safecracker/index.html';
	public $classname = 'Safecracker_ext';
	public $required_by = array('module');
	
	/**
	 * Safecracker_ext
	 * 
	 * @param	mixed $settings = ''
	 * @return	void
	 */
	public function __construct($settings = '')
	{
		$this->EE =& get_instance();
		
		$this->settings = $settings;
	}

	// --------------------------------------------------------------------
	
	/**
	 * activate_extension
	 * 
	 * @return	void
	 */
	public function activate_extension()
	{
		return TRUE;
		
		//show_error('This extension is automatically installed with the Safecracker module');
		
		
		/*
		$hook_defaults = array(
			'class' => $this->classname,
			'settings' => '',
			'version' => $this->version,
			'enabled' => 'y',
			'priority' => 10
		);
		
		$hooks[] = array(
			'method' => 'form_declaration_modify_data',
			'hook' => 'form_declaration_modify_data'
		);
		
		foreach ($hooks as $hook)
		{
			$this->EE->db->insert('extensions', array_merge($hook_defaults, $hook));
		}
		
		*/

	}

	// --------------------------------------------------------------------
	
	/**
	 * update_extension
	 * 
	 * @param	mixed $current = ''
	 * @return	void
	 */
	public function update_extension($current = '')
	{
		return TRUE;
		
		//show_error('This extension is automatically updated with the Safecracker module');

		/*
		if ($current == '' OR version_compare($current, $this->version, '=='))
		{
			return FALSE;
		}
		
		$this->EE->db->update('extensions', array('version' => $this->version), array('class' => $this->classname));
		*/

	}

	// --------------------------------------------------------------------
	
	/**
	 * disable_extension
	 * 
	 * @return	void
	 */
	public function disable_extension()
	{
		return TRUE;
		
		//show_error('This extension is automatically deleted with the Safecracker module');
		
		/*
		$this->EE->db->delete('extensions', array('class' => $this->classname));
		*/
	}

	// --------------------------------------------------------------------
	
	/**
	 * settings
	 * 
	 * @return	void
	 */
	public function settings()
	{
		$settings = array();
		
		return $settings;
	}

	// --------------------------------------------------------------------
	
	/**
	 * settings_form
	 * 
	 * @return	void
	 */
	public function settings_form()
	{
		$this->fetch_channels();
		$this->fetch_statuses();
		$this->fetch_settings();
		$this->fetch_members();
		$this->fetch_member_groups();
		$this->fetch_fieldtypes();
		
		$this->EE->load->library('javascript');
		$this->EE->load->library('table');
		$this->EE->load->helper('form');
		
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('safecracker_module_name'));
		
		$this->EE->cp->add_to_head('
	<script type="text/javascript" charset="utf-8">
// <![CDATA[

			(function($){
				$.SafeCracker = {
					memberListOptions: {},
					getMemberList: function(el, options) {
						$.extend($.SafeCracker.memberListOptions, options);
						$(el).parents("table").eq(0).find(".safecracker_member_list").html(\'<option value="">Loading...</option>\');
						$.getJSON(
							EE.BASE+"&C=addons_modules&M=show_module_cp&module=safecracker&method=member_list",
							$.SafeCracker.memberListOptions,
							function (data) {
							console.log(data);
								var html = "";
								for (i in data) {
									html += \'<option value="\'+i+\'">\'+data[i]+\'</option>\';
								}
								$(el).parents("table").eq(0).find(".safecracker_member_list").html(html);
							}
						);
					}
				};
		       })(jQuery);
// ]]>
</script>
		');
		
		$this->EE->javascript->output("
			$('.safecracker_member_list').change(function(){
				if ($(this).val() == '{NEXT}') {
					$.SafeCracker.memberListOptions.offset += 100;
					$.SafeCracker.getMemberList(this);
				} else {				
					$(this).parents('table').eq(0).find('.safecracker_member_id').val($(this).val());
				}
			});
			$('.safecracker_member_group_list').change(function(){
				if ($(this).val()) {
					$.SafeCracker.getMemberList(this, {offset: 0, 'group_id': $(this).val(), 'search_value': ''})
				}
			});
			$('.safecracker_member_search_keyword').keypress(function(e){
				if (e.keyCode == 13) {
					if ($(this).val()) {
						$.SafeCracker.getMemberList(this, {offset: 0, 'group_id': '', 'search_value': $(this).val()})
					}
					return false;
				}
			});
			$('.safecracker_member_search_submit').click(function(e){
				if ($(this).parents('table').eq(0).find('.safecracker_member_search_keyword').val()) {
					$.SafeCracker.getMemberList(this, {offset: 0, 'group_id': '', 'search_value': $(this).parents('table').eq(0).find('.safecracker_member_search_keyword').val()})
				}
				return false;
			});
		");

		$vars = array(
			'site_id' => $this->EE->config->item('site_id'),
			'action_url' => 'C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file=safecracker',
			'settings' => $this->settings,
			'channels' => $this->channels,
			'statuses' => $this->statuses,
			'members' => $this->members,
			'member_groups' => $this->member_groups,
			'fieldtypes' => $this->fieldtypes
		);
		
		return $this->EE->load->view('index', $vars, TRUE);
	}

	// --------------------------------------------------------------------
	
	/**
	 * save_settings
	 * 
	 * @return	void
	 */
	public function save_settings()
	{
		$this->EE->load->helper('security');
		
		$this->fetch_channels();
		
		foreach ($this->channels as $row)
		{
			if ( ! empty($_POST['allow_guests'][$this->EE->config->item('site_id')][$row['channel_id']]) && ! empty($_POST['logged_out_member_id'][$this->EE->config->item('site_id')][$row['channel_id']]))
			{
				$this->settings['allow_guests'][$this->EE->config->item('site_id')][$row['channel_id']] = xss_clean($_POST['allow_guests'][$this->EE->config->item('site_id')][$row['channel_id']]);
				$this->settings['logged_out_member_id'][$this->EE->config->item('site_id')][$row['channel_id']] = xss_clean($_POST['logged_out_member_id'][$this->EE->config->item('site_id')][$row['channel_id']]);
				if (isset($_POST['require_captcha'][$this->EE->config->item('site_id')][$row['channel_id']]))
				{
					$this->settings['require_captcha'][$this->EE->config->item('site_id')][$row['channel_id']] = xss_clean($_POST['require_captcha'][$this->EE->config->item('site_id')][$row['channel_id']]);
				}
			}
			if (isset($_POST['override_status'][$this->EE->config->item('site_id')][$row['channel_id']]))
			{
				$this->settings['override_status'][$this->EE->config->item('site_id')][$row['channel_id']] = xss_clean($_POST['override_status'][$this->EE->config->item('site_id')][$row['channel_id']]);
			}
		}
		
		$this->settings['license_number'] = $this->EE->input->post('license_number', TRUE);
		
		$this->EE->db->where('class', 'Safecracker_ext');
		
		$this->EE->db->update('extensions', array('settings' => serialize($this->settings)));
		
		$this->EE->functions->redirect(BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=safecracker');
	}

	// --------------------------------------------------------------------
	
	/**
	 * fetch_settings
	 * 
	 * @return	void
	 */
	public function fetch_settings()
	{
		if ( ! empty($this->settings))
		{
			return;
		}
		
		$this->EE->db->select('settings');
		$this->EE->db->where('class', 'Safecracker_ext');
		$this->EE->db->limit(1);
		
		$query = $this->EE->db->get('extensions');
		
		$this->settings = ($query->row('settings')) ? $this->unserialize($query->row('settings')) : array();
	}

	// --------------------------------------------------------------------
	
	/**
	 * fetch_channels
	 * 
	 * @return	void
	 */
	public function fetch_channels()
	{
		if (isset($this->channels))
		{
			return;
		}
		
		$this->EE->load->model('channel_model');
		
		$query = $this->EE->channel_model->get_channels();
			
		$this->channels = $query->result_array();
			
		unset($query);
	}

	// --------------------------------------------------------------------
	
	/**
	 * fetch_fieldtypes
	 * 
	 * @return	void
	 */
	public function fetch_fieldtypes()
	{
		if (isset($this->fieldtypes))
		{
			return;
		}
		
		$this->EE->load->library('api');
		
		$this->EE->api->instantiate('channel_fields');
		
		$this->fieldtypes = $this->EE->api_channel_fields->fetch_installed_fieldtypes();
	}

	// --------------------------------------------------------------------
	
	/**
	 * fetch_members
	 * 
	 * @return	void
	 */
	public function fetch_members()
	{
		if (isset($this->members))
		{
			return;
		}
		
		$this->members = array();
		
		$this->EE->load->model('member_model');
		
		$query = $this->EE->member_model->get_members('', 101);
		
		if ( ! $query)
		{
			return;
		}
		
		$result = $query->result();
		
		$more = FALSE;
		
		if ($query->num_rows() > 100)
		{
			$more = TRUE;
			
			array_pop($result);
		}
		
		$this->members[''] = $this->EE->lang->line('safecracker_select_member');
	
		foreach ($result as $row)
		{
			$this->members[$row->member_id] = $row->username;
		}
		
		if ($more)
		{
			$this->members['{NEXT}'] = $this->EE->lang->line('safecracker_more_members');
		}
		
		unset($query);
	}

	// --------------------------------------------------------------------
	
	/**
	 * fetch_member_groups
	 * 
	 * @return	void
	 */
	public function fetch_member_groups()
	{
		if (isset($this->member_groups))
		{
			return;
		}
		
		$this->EE->load->model('member_model');
		
		$query = $this->EE->member_model->get_member_groups();
		
		$this->member_groups[''] = $this->EE->lang->line('safecracker_select_member_group');
		
		if ($query)
		{
			foreach ($query->result() as $row)
			{
				$this->member_groups[$row->group_id] = $row->group_title;
			}
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * fetch_statuses
	 * 
	 * @return	void
	 */
	public function fetch_statuses()
	{
		if (isset($this->statuses))
		{
			return;
		}
		
		$this->fetch_channels();
		
		$this->statuses = array();
		
		$this->EE->lang->loadfile('content');
		
		foreach ($this->channels as $channel)
		{
			$this->statuses[$channel['channel_id']] = array();
			
			if ( ! empty($channel['status_group']) && empty($this->statuses[$channel['channel_id']]))
			{
				$this->EE->db->select('status');
				$this->EE->db->where('group_id', $channel['status_group']);
				
				$query = $this->EE->db->get('statuses');
				
				foreach ($query->result() as $row)
				{
					$this->statuses[$channel['channel_id']][$row->status] = (in_array($row->status, array('open', 'closed'))) ? $this->EE->lang->line($row->status) : $row->status;
				}
				
				unset($query);
			}
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * unserialize
	 * 
	 * @param	mixed $data
	 * @param	mixed $base64_decode = FALSE
	 * @return	void
	 */
	public function unserialize($data, $base64_decode = FALSE)
	{
		if ($base64_decode)
		{
			$data = base64_decode($data);
		}
		
		$data = @unserialize($data);
		
		return (is_array($data)) ? $data : array();
	}

	// --------------------------------------------------------------------
	
	/**
	 * form_declaration_modify_data
	 * 
	 * @param	mixed $data
	 * @return	void
	 */
	public function form_declaration_modify_data($data)
	{
		if (is_array($this->EE->extensions->last_call))
		{
			$data = $this->EE->extensions->last_call;
		}
		
		if (isset($this->EE->TMPL) && is_object($this->EE->TMPL) && ! empty($this->EE->session->cache['safecracker']['form_declaration']))
		{
			unset($this->EE->session->cache['safecracker']['form_declaration']);
			
			// a hack to retrieve the output_js array from channel standalone
			if (isset($this->EE->safecracker->channel_standalone->output_js))
			{
				$this->EE->session->cache['safecracker']['channel_standalone_output_js'] = $this->EE->safecracker->channel_standalone->output_js;
				
				unset($this->EE->safecracker->channel_standalone->output_js);
			}
			
			if (isset($this->EE->session->cache['safecracker']['enctype']))
			{
				$data['enctype'] = $this->EE->session->cache['safecracker']['enctype'];
			
				unset($this->EE->session->cache['safecracker']['enctype']);
			}
			
			if (isset($this->EE->session->cache['safecracker']['form_declaration_hidden_fields']))
			{
				$data['hidden_fields'] = array_merge($data['hidden_fields'], $this->EE->session->cache['safecracker']['form_declaration_hidden_fields']);
				
				//@TODO
				unset($data['hidden_fields']['PRV']);
				
				unset($this->EE->session->cache['safecracker']['form_declaration_hidden_fields']);
			}
			
			if (isset($this->EE->session->cache['safecracker']['form_declaration_data']))
			{
				$data = array_merge($data, $this->EE->session->cache['safecracker']['form_declaration_data']);
				
				unset($this->EE->session->cache['safecracker']['form_declaration_data']);
			}
		}
		
		return $data;
	}
}

/* End of file ext.safecracker.php */
/* Location: ./system/expressionengine/third_party/safecracker/ext.safecracker.php */