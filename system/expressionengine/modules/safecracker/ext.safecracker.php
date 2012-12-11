<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team, 
 * 		- Original Development by Barrett Newton -- http://barrettnewton.com
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Safecracker_ext
{
	public $settings = array();
	public $name = 'SafeCracker';
	public $version = '2.1';
	public $description = 'A replacement and enchancement of the Stand-Alone Entry Form';
	public $settings_exist = 'y';
	public $docs_url = 'http://ellislab.com/expressionengine/user-guide/modules/safecracker/index.html';
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
		
		$this->EE->cp->set_variable('cp_page_title', lang('safecracker_module_name'));
		
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
		$this->fetch_channels();
		$this->fetch_settings();

		$site_id = $this->EE->config->item('site_id');

		// We should be able to just check override_status in order
		// to determine whether we've initialized the settings for
		// the entire site.
		if( ! isset($this->settings['override_status'][$site_id]))
		{
			$this->settings['override_status'][$site_id] = array();
			$this->settings['allow_guests'][$site_id] = array();
			$this->settings['logged_out_member_id'][$site_id] = array();
			$this->settings['require_captcha'][$site_id] = array();
		}

		$post = $this->EE->security->xss_clean($_POST);
		foreach ($this->channels as $row)
		{
			// Just make things a little clearer.
			$channel_id = $row['channel_id'];

			if(isset($post['override_status'][$site_id][$channel_id]))
			{
				$this->settings['override_status'][$site_id][$channel_id] = $post['override_status'][$site_id][$channel_id];
			}

			$allow_guests = isset($post['allow_guests'][$site_id][$channel_id]) ? $post['allow_guests'][$site_id][$channel_id] : false;
			if ( ! $allow_guests) 
			{
				unset($this->settings['allow_guests'][$site_id][$channel_id]);
				unset($this->settings['logged_out_member_id'][$site_id][$channel_id]);
				unset($this->settings['require_captcha'][$site_id][$channel_id]);
			} 
			else 
			{
				$this->settings['allow_guests'][$site_id][$channel_id] = $allow_guests;
				if ( ! empty($post['logged_out_member_id'][$site_id][$channel_id]))
				{
					$this->settings['logged_out_member_id'][$site_id][$channel_id] = $post['logged_out_member_id'][$site_id][$channel_id];
				}
				elseif (isset($this->settings['logged_out_member_id'][$site_id][$channel_id])) 
				{
					unset($this->settings['logged_out_member_id'][$site_id][$channel_id]);
				}
			
				if ( ! empty($post['require_captcha'][$site_id][$channel_id])) 
				{
					$this->settings['require_captcha'][$site_id][$channel_id] = $post['require_captcha'][$site_id][$channel_id];
				}
				elseif (isset($this->settings['require_captcha'][$site_id][$channel_id]))
				{
					unset($this->settings['require_captcha'][$site_id][$channel_id]);
				}
			}
		}
		

		$this->EE->db->update(
			'extensions',
			array('settings' => serialize($this->settings)),
			array('class' => 'Safecracker_ext')
		);
		
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
		
		$this->settings = ($query->row('settings')) ? $this->unserialize($query->row('settings')) : NULL;

		// If we don't have settings to load, then initialize the
		// settings array.
		if ($this->settings === NULL)
		{
			$this->settings = array(
				'override_status'	=>	array(),
				'allow_guests'		=>	array(),
				'logged_out_member_id'=>array(),
				'require_captcha'	=>	array()
			);
		}
		
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
		
		$this->members[''] = lang('safecracker_select_member');
	
		foreach ($result as $row)
		{
			$this->members[$row->member_id] = $row->username;
		}
		
		if ($more)
		{
			$this->members['{NEXT}'] = lang('safecracker_more_members');
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
		
		$this->member_groups[''] = lang('safecracker_select_member_group');
		
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
					$this->statuses[$channel['channel_id']][$row->status] = (in_array($row->status, array('open', 'closed'))) ? lang($row->status) : $row->status;
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
		
		if (isset($this->EE->TMPL)
			AND is_object($this->EE->TMPL)
			AND ! empty($this->EE->session->cache['safecracker']['form_declaration'])
			AND $this->EE->safecracker->form_loaded)
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
