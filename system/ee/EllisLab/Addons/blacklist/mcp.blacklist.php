<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Blacklist control panel
 */
class Blacklist_mcp {

	public $LB			= "\r\n";

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	public function __construct( $switch = TRUE )
	{
		ee()->load->dbforge();

		ee()->load->helper('form');

		// Updates
		ee()->db->select('module_version');
		$query = ee()->db->get_where('modules', array('module_name' => 'Blacklist'));

		if ($query->num_rows() > 0)
		{
			if ( ! ee()->db->table_exists('whitelisted'))
			{
				$fields = array(
								'whitelisted_type'  => array(
															'type' 		 => 'varchar',
															'constraint' => '20',
														),
								'whitelisted_value' => array(
															'type' => 'text'
														)
				);

				ee()->dbforge->add_field($fields);
				ee()->dbforge->create_table('whitelisted');
			}
		}
	}

	/**
	 * Blacklist Homepage
	 *
	 * @access	public
	 * @return	string
	 */
	public function index()
	{
		if ( ! ee()->db->table_exists("blacklisted"))
		{
			show_error(lang("ref_no_blacklist_table"));
		}

		if ( ! ee()->db->table_exists("whitelisted"))
		{
			show_error(lang("ref_no_whitelist_table"));
		}

		$allow_write_htaccess = FALSE;
		$htaccess_path = NULL;

		if (ee()->session->userdata('group_id') == '1')
		{
			$allow_write_htaccess = TRUE;
			$htaccess_path = ee()->config->item('htaccess_path', '', TRUE);
		}

		$vars = array(
			'allow_write_htaccess' => $allow_write_htaccess,
			'base_url' => ee('CP/URL')->make('addons/settings/blacklist/save_htaccess_path'),
			'cp_page_title' => lang('blacklist_module_name') . ' ' . lang('settings'),
			'save_btn_text' => 'btn_save_settings',
			'save_btn_text_working' => 'btn_saving',
			'sections' => array(
				array(
					array(
						'title' => 'add_htaccess_file',
						'desc' => 'add_htaccess_file_desc',
						'fields' => array(
							'htaccess_path' => array(
								'type' => 'text',
								'value' => $htaccess_path
							)
						)
					),
				)
			),
			'blacklist_ip' => '',
			'blacklist_agent' => '',
			'blacklist_url' => '',
			'whitelist_ip' => '',
			'whitelist_agent' => '',
			'whitelist_url' => ''
		);

		foreach (array('black', 'white') as $kind)
		{
			$query = ee()->db->get("{$kind}listed");

			if ($query->num_rows() != 0)
			{
				foreach($query->result_array() as $row)
				{
					$vars["{$kind}list_" . $row["{$kind}listed_type"]] = str_replace('|', NL, $row["{$kind}listed_value"]);
				}
			}

		}

		return ee('View')->make('blacklist:index')->render($vars);
	}

	/**
	 * Write .htaccess File
	 *
	 * @access	public
	 * @return	void
	 */
	public function save_htaccess_path()
	{
		if (ee()->session->userdata('group_id') != '1' OR ee()->input->get_post('htaccess_path') === FALSE OR (ee()->input->get_post('htaccess_path') == '' && ee()->config->item('htaccess_path') === FALSE))
		{
			ee()->functions->redirect(ee('CP/URL')->make('addons/settings/blacklist'));
		}

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules('htaccess_path', 'lang:htaccess_path', 'callback__check_path');

		ee()->form_validation->set_error_delimiters('<br /><span class="notice">', '<br />');

		if (ee()->form_validation->run() === FALSE)
		{

			return $this->index();
		}

		ee()->config->_update_config(array('htaccess_path' => ee()->input->get_post('htaccess_path')));

		if (ee()->input->get_post('htaccess_path') == '' && ! ee()->config->item('htaccess_path'))
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('htaccess_path_removed'))
				->addToBody(lang('htaccess_path_removed_desc'))
				->defer();
			ee()->functions->redirect(ee('CP/URL')->make('addons/settings/blacklist'));
		}

		$this->write_htaccess(parse_config_variables(ee()->input->get_post('htaccess_path')));

		ee('CP/Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang('htaccess_written_successfully'))
			->addToBody(lang('htaccess_written_successfully_desc'))
			->defer();
		ee()->functions->redirect(ee('CP/URL')->make('addons/settings/blacklist'));
	}

	private function _check_path($str)
	{
		if ($str == '')
		{
			return TRUE;
		}

		$str = parse_config_variables($str);

		if ( ! file_exists($str) OR ! is_file($str))
		{
			ee()->form_validation->set_message('_check_path', lang('invalid_htaccess_path'));
			return FALSE;
		}
		elseif (! is_writeable(ee()->input->get_post('htaccess_path')))
		{
				ee()->form_validation->set_message('_check_path', lang('invalid_htaccess_path'));
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Write .htaccess File
	 *
	 * @access	public
	 * @return	void
	 */
	public function write_htaccess($htaccess_path = '', $return = 'redirect')
	{
		$htaccess_path = ($htaccess_path == '') ? ee()->config->item('htaccess_path') : $htaccess_path;

		if (ee()->session->userdata('group_id') != '1' OR $htaccess_path == '')
		{
			ee()->functions->redirect(ee('CP/URL')->make('addons/settings/blacklist'));
		}

		if ( ! $fp = @fopen($htaccess_path, FOPEN_READ))
		{
			if ($return == 'bool')
			{
				return FALSE;
			}

			show_error(lang('invalid_htaccess_path'));
		}

		flock($fp, LOCK_SH);
		$data = @fread($fp, filesize($htaccess_path));
		flock($fp, LOCK_UN);
		fclose($fp);

		if (preg_match("/##EE Spam Block(.*?)##End EE Spam Block/s", $data, $match))
		{
			$data = str_replace($match['0'], '', $data);
		}

		$data = trim($data);

		//  Current Blacklisted
		$query 			= ee()->db->get('blacklisted');
		$old['url']		= array();
		$old['agent']	= array();
		$old['ip']		= array();

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$old_values = explode('|',trim($row['blacklisted_value']));
				for ($i=0, $s = count($old_values); $i < $s; $i++)
				{
					if (trim($old_values[$i]) != '')
					{
						$old[$row['blacklisted_type']][] = preg_quote($old_values[$i]);
					}
				}
			}
		}

		//  EE currently uses URLs and IPs
		$urls = '';

		while(count($old['url']) > 0)
		{
			$urls .= 'SetEnvIfNoCase Referer ".*('.trim(implode('|', array_slice($old['url'], 0, 50))).').*" BadRef'.$this->LB;
			$old['url'] = array_slice($old['url'], 50);
		}

		$ips = '';

		while(count($old['ip']) > 0)
		{
			$ips .= 'SetEnvIfNoCase REMOTE_ADDR "^('.trim(implode('|', array_slice($old['ip'], 0, 50))).').*" BadIP'.$this->LB;
			$old['ip'] = array_slice($old['ip'], 50);
		}

		$site 	= parse_url(ee()->config->item('site_url'));

		$domain  = ( ! ee()->config->item('cookie_domain')) ? '' : 'SetEnvIfNoCase Referer ".*('.preg_quote(ee()->config->item('cookie_domain')).').*" GoodHost'.$this->LB;

		$domain .= 'SetEnvIfNoCase Referer "^$" GoodHost'.$this->LB;  // If no referrer, they be safe!

		$host  = 'SetEnvIfNoCase Referer ".*('.preg_quote($site['host']).').*" GoodHost'.$this->LB;

		if ($urls != '' OR $ips != '')
		{
			$data .= $this->LB.$this->LB."##EE Spam Block".$this->LB
					.	$urls
					.	$ips
					.	$domain
					.	$host
					.	"order deny,allow".$this->LB
					.	"deny from env=BadRef".$this->LB
					.	"deny from env=BadIP".$this->LB
					.	"allow from env=GoodHost".$this->LB
					."##End EE Spam Block".$this->LB.$this->LB;
		}

		if ( ! $fp = @fopen($htaccess_path, FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			show_error(lang('invalid_htaccess_path'));
		}

		flock($fp, LOCK_EX);
		fwrite($fp, $data);
		flock($fp, LOCK_UN);
		fclose($fp);

		return TRUE;
	}

	/**
	 * Update Blacklist
	 *
	 * @access	public
	 * @return	string
	 */
	public function ee_blacklist()
	{
		$this->_download_update_list('black');
		ee('CP/Alert')->makeInline('lists-form')
			->asSuccess()
			->withTitle(lang('lists_updated'))
			->addToBody(lang('blacklist_downloaded'))
			->defer();
		ee()->functions->redirect(ee('CP/URL')->make('addons/settings/blacklist'));
	}

	/**
	 * Update Whitelist
	 *
	 * @access	public
	 * @return	string
	 */
	public function ee_whitelist()
	{
		$this->_download_update_list('white');
		ee('CP/Alert')->makeInline('lists-form')
			->asSuccess()
			->withTitle(lang('lists_updated'))
			->addToBody(lang('whitelist_downloaded'))
			->defer();
		ee()->functions->redirect(ee('CP/URL')->make('addons/settings/blacklist'));
	}

	public function save_lists()
	{
		$this->update_whitelist();
		$this->update_blacklist();
		ee('CP/Alert')->makeInline('lists-form')
			->asSuccess()
			->withTitle(lang('lists_updated'))
			->addToBody(lang('lists_updated_desc'))
			->defer();
		ee()->functions->redirect(ee('CP/URL')->make('addons/settings/blacklist'));
	}

	/**
	 * Update Blacklisted Items
	 *
	 * @access	public
	 * @return	void
	 */
	private function update_blacklist()
	{
		if ( ! ee()->db->table_exists('blacklisted'))
		{
			show_error(lang('ref_no_blacklist_table'));
		}

		// Current Blacklisted
		$query 			= ee()->db->get('blacklisted');
		$old['url']		= array();
		$old['agent']	= array();
		$old['ip']		= array();

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$old_values = explode('|',$row['blacklisted_value']);
				for ($i=0; $i < count($old_values); $i++)
				{
					$old[$row['blacklisted_type']][] = $old_values[$i];
				}
			}
		}

		// Current Whitelisted

		$query 				= ee()->db->get('whitelisted');
		$white['url']		= array();
		$white['agent']		= array();
		$white['ip']		= array();

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$white_values = explode('|',$row['whitelisted_value']);
				for ($i=0; $i < count($white_values); $i++)
				{
					if (trim($white_values[$i]) != '')
					{
						$white[$row['whitelisted_type']][] = ee()->db->escape_str($white_values[$i]);
					}
				}
			}
		}

		// Update Blacklist with New Values sans Whitelist Matches

		$default = array('blacklist_ip', 'blacklist_agent', 'blacklist_url');
		$modified_channels = array();

		foreach ($default as $val)
		{
			$type = str_replace('blacklist_', '', $val);

			if (isset($_POST[$val]))
			{
				$_POST[$val] = str_replace('[-]', '', $_POST[$val]);
				$_POST[$val] = str_replace('[+]', '', $_POST[$val]);
				$_POST[$val] = trim(stripslashes($_POST[$val]));

				$new_values = explode(NL,strip_tags($_POST[$val]));
			}
			else
			{
				continue;
			}

			 // Clean out user mistakes; and
			 // Clean out Referrers with new additions
			 foreach ($new_values as $key => $value)
			 {
				if (trim($value) == "" OR trim($value) == NL)
				{
					unset($new_values[$key]);
				}

				if ($type == 'ip')
				{
					// Collapse IPv6 addresses
					if (ee()->input->valid_ip($value, 'ipv6'))
					{
						$new_values[$key] = inet_ntop(inet_pton($value));
					}
				}
			 }

			 sort($new_values);

			 $_POST[$val] = implode("|", array_unique($new_values));

			 ee()->db->where('blacklisted_type', $val);
			 ee()->db->delete('blacklisted');

			 $data = array(
			 	'blacklisted_type' => $type,
			 	'blacklisted_value' => $_POST[$val]
			 );

			 ee()->db->insert('blacklisted', $data);
		}
	}

	/**
	 * Update Whitelisted Items
	 *
	 * @access	public
	 * @return	void
	 */
	private function update_whitelist()
	{
		if ( ! ee()->db->table_exists('whitelisted'))
		{
			show_error(lang('ref_no_whitelist_table'));
		}
		// Current Whitelisted

		$query 			= ee()->db->get('whitelisted');
		$old['url']		= array();
		$old['agent']	= array();
		$old['ip']		= array();

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$old_values = explode('|',$row['whitelisted_value']);
				for ($i=0; $i < count($old_values); $i++)
				{
					$old[$row['whitelisted_type']][] = $old_values[$i];
				}
			}
		}

		// Update Whitelist with New Values

		$default = array('whitelist_ip', 'whitelist_agent', 'whitelist_url');

		foreach ($default as $val)
		{
			if (isset($_POST[$val]))
			{
				$type = str_replace('whitelist_', '', $val);

				$_POST[$val] = str_replace('[-]', '', $_POST[$val]);
				$_POST[$val] = str_replace('[+]', '', $_POST[$val]);
				$_POST[$val] = trim(stripslashes($_POST[$val]));

				$new_values = explode(NL,strip_tags($_POST[$val]));

				// Clean out user mistakes; and
				// Clean out Whitelists with new additions
				foreach ($new_values as $key => $value)
				{
					if (trim($value) == "" OR trim($value) == NL)
					{
						unset($new_values[$key]);
					}

					if ($type == 'ip')
					{
						// Collapse IPv6 addresses
						if (ee()->input->valid_ip($value, 'ipv6'))
						{
							$new_values[$key] = inet_ntop(inet_pton($value));
						}
					}
				}

				$_POST[$val] = implode("|",$new_values);

				ee()->db->where('whitelisted_type', $val);
				ee()->db->delete('whitelisted');

				$data = array(
					'whitelisted_type' 	=> $type,
					'whitelisted_value'	=> $_POST[$val]
				);

				ee()->db->insert('whitelisted', $data);
			}
		}
	}

	/**
	 * Download and update ExpressionEngine.com Black- or Whitelist
	 *
	 * @access	private
	 * @return	string
	 */
	private function _download_update_list($listtype = "black")
	{
		if (ee()->input->get('token') != CSRF_TOKEN)
		{
			show_error(lang('unauthorized_access'));
		}

		$vars['cp_page_title'] = lang('blacklist_module_name'); // both black and white lists share this title

		if ( ! ee()->db->table_exists("{$listtype}listed"))
		{
			show_error(lang("ref_no_{$listtype}list_table"));
		}

		//  Get Current List from ExpressionEngine.com
		ee()->load->library('xmlrpc');
		ee()->xmlrpc->server('http://ping.expressionengine.com/index.php', 80);
		ee()->xmlrpc->method("ExpressionEngine.{$listtype}list");

		if (ee()->xmlrpc->send_request() === FALSE)
		{
			ee('CP/Alert')->makeInline('lists-form')
				->asIssue()
				->withTitle(lang("ref_{$listtype}list_irretrievable"))
				->addToBody(ee()->xmlrpc->display_error())
				->defer();
			ee()->functions->redirect(ee('CP/URL')->make('addons/settings/blacklist'));
		}

		// Array of our returned info
		$remote_info = ee()->xmlrpc->display_response();

		$new['url'] 	= ( ! isset($remote_info['urls']) OR count($remote_info['urls']) == 0) 	? array() : explode('|',$remote_info['urls']);
		$new['agent'] 	= ( ! isset($remote_info['agents']) OR count($remote_info['agents']) == 0) ? array() : explode('|',$remote_info['agents']);
		$new['ip'] 		= ( ! isset($remote_info['ips']) OR count($remote_info['ips']) == 0) 		? array() : explode('|',$remote_info['ips']);

		//  Add current list
		$query 			= ee()->db->get("{$listtype}listed");
		$old['url']		= array();
		$old['agent']	= array();
		$old['ip']		= array();

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$old_values = explode('|',$row["{$listtype}listed_value"]);
				for ($i=0; $i < count($old_values); $i++)
				{
					$old[$row["{$listtype}listed_type"]][] = $old_values[$i];
				}
			}
		}

		//  Current listed
		$query 				= ee()->db->get('whitelisted');
		$white['url']		= array();
		$white['agent']		= array();
		$white['ip']		= array();

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$white_values = explode('|',$row['whitelisted_value']);
				for ($i=0; $i < count($white_values); $i++)
				{
					if (trim($white_values[$i]) != '')
					{
						$white[$row['whitelisted_type']][] = $white_values[$i];
					}
				}
			}
		}

		//  Check for uniqueness and sort
		$new['url'] 	= array_unique(array_merge($old['url'],$new['url']));
		$new['agent']	= array_unique(array_merge($old['agent'],$new['agent']));
		$new['ip']		= array_unique(array_merge($old['ip'],$new['ip']));
		sort($new['url']);
		sort($new['agent']);
		sort($new['ip']);

		//  Put blacklist info back into database
		ee()->db->truncate("{$listtype}listed");

		foreach($new as $key => $value)
		{
			$listed_value = implode('|',$value);

			$data = array(
				"{$listtype}listed_type" 	=> $key,
				"{$listtype}listed_value"	=> $listed_value
			);

			ee()->db->insert("{$listtype}listed", $data);
		}

		if ($listtype == 'white')
		{
			// If this is a whitelist update, we're done, send data to view and get out
			return;
		}
	}

}
// END CLASS

// EOF
