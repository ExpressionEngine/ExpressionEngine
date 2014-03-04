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
 * ExpressionEngine Blacklist Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Blacklist_mcp {

	var $value		= '';
	var $LB			= "\r\n";

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Blacklist_mcp( $switch = TRUE )
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
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

	// --------------------------------------------------------------------

	/**
	 * Blacklist Homepage
	 *
	 * @access	public
	 * @return	string
	 */
	function index()
	{
		$vars['license_number'] = ee()->config->item('license_number');
		$vars['cp_page_title'] = lang('blacklist_module_name');

		$vars['allow_write_htaccess'] = FALSE; // overwritten below if admin

		if (ee()->session->userdata('group_id') == '1')
		{
			$vars['allow_write_htaccess'] = TRUE;

			$htaccess_path = ee()->config->item('htaccess_path');

			$vars['htaccess_path'] = $htaccess_path;
		}

		return ee()->load->view('index', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Write .htaccess File
	 *
	 * @access	public
	 * @return	void
	 */
	function save_htaccess_path()
	{
		if (ee()->session->userdata('group_id') != '1' OR ee()->input->get_post('htaccess_path') === FALSE OR (ee()->input->get_post('htaccess_path') == '' && ee()->config->item('htaccess_path') === FALSE))
		{
			ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blacklist');
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
			ee()->session->set_flashdata('message_success', lang('htaccess_path_removed'));
			ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blacklist');
		}

		$this->write_htaccess(ee()->input->get_post('htaccess_path'));

		ee()->session->set_flashdata('message_success', lang('htaccess_written_successfully'));
		ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blacklist');
	}


	function _check_path($str)
	{
		if ($str == '')
		{
			return TRUE;
		}

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

	// --------------------------------------------------------------------

	/**
	 * Write .htaccess File
	 *
	 * @access	public
	 * @return	void
	 */
	function write_htaccess($htaccess_path = '', $return = 'redirect')
	{
		$htaccess_path = ($htaccess_path == '') ? ee()->config->item('htaccess_path') : $htaccess_path;

		if (ee()->session->userdata('group_id') != '1' OR $htaccess_path == '')
		{
			ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blacklist');
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

	// --------------------------------------------------------------------

	/**
	 * Update Blacklist
	 *
	 * @access	public
	 * @return	string
	 */
	function ee_blacklist()
	{
		return $this->_download_update_list('black');
	}

	// --------------------------------------------------------------------

	/**
	 * Update Whitelist
	 *
	 * @access	public
	 * @return	string
	 */
	function ee_whitelist()
	{
		return $this->_download_update_list('white');
	}

	// --------------------------------------------------------------------

	/**
	 * View Blacklisted
	 *
	 * @access	public
	 * @return	string
	 */
	function view_blacklist()
	{
		$vars = $this->_view_list('black');

		return ee()->load->view('view', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * View Whitelisted
	 *
	 * @access	public
	 * @return	string
	 */
	function view_whitelist()
	{
		$vars = $this->_view_list('white');

		return ee()->load->view('view', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Update Blacklisted Items
	 *
	 * @access	public
	 * @return	void
	 */
	function update_blacklist($additions = array(), $write = FALSE, $return = 'redirect')
	{
		if ( ! ee()->db->table_exists('blacklisted'))
		{
			show_error(lang('ref_no_blacklist_table'));
		}

		$write_htaccess = ($write) ? $write : ee()->input->get_post('write_htaccess');

		// Current Blacklisted

		$query 			= ee()->db->get('blacklisted');
		$old['url']		= array();
		$old['agent']	= array();
		$old['ip']		= array();
		$use_post		= TRUE;

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

		$default = array('ip', 'agent', 'url');
		$modified_channels = array();

		if (count($additions) > 0)
		{
			$use_post = FALSE;

			$new_data['agent']		= (isset($additions['agent'])) ? array_merge($old['agent'], $additions['agent']) : $old['agent'];
			$new_data['url']		= (isset($additions['url'])) ? array_merge($old['url'], $additions['url']) : $old['url'];
			$new_data['ip']		= (isset($additions['ip'])) ? array_merge($old['ip'], $additions['ip']) : array();

		}

		foreach ($default as $val)
		{
			if (isset($_POST[$val]))
			{
				 $_POST[$val] = str_replace('[-]', '', $_POST[$val]);
				 $_POST[$val] = str_replace('[+]', '', $_POST[$val]);
				 $_POST[$val] = trim(stripslashes($_POST[$val]));

				 $new_values = explode(NL,strip_tags($_POST[$val]));
			}
			elseif (isset($new_data[$val]))
			{
				$new_values = $new_data[$val];
			}
			else
			{
				continue;
			}

			 // Clean out user mistakes; and
			 // Clean out Referrers with new additions
			 foreach ($new_values as $key => $this->value)
			 {
				if (trim($this->value) == "" OR trim($this->value) == NL)
				{
					unset($new_values[$key]);
				}
				elseif ( ! in_array($this->value, $old[$val]))
				{
					$name = ($val == 'url') ? 'from' : $val;

					if (ee()->db->table_exists('referrers'))
					{
						ee()->db->like('ref_'.$name, $this->value);

						foreach ($white[$val] as $w_value)
						{
							ee()->db->not_like('ref_'.$name, $w_value);
						}

						ee()->db->delete('referrers');
					}
				}

				if ($val == 'ip')
				{
					// Collapse IPv6 addresses
					if (ee()->input->valid_ip($this->value, 'ipv6'))
					{
						$new_values[$key] = inet_ntop(inet_pton($this->value));
					}
				}
			 }

			 sort($new_values);

			 $_POST[$val] = implode("|", array_unique($new_values));

			 ee()->db->where('blacklisted_type', $val);
			 ee()->db->delete('blacklisted');

			 $data = array(
			 	'blacklisted_type' => $val,
			 	'blacklisted_value' => $_POST[$val]
			 );

			 ee()->db->insert('blacklisted', $data);
		}


		if ($write_htaccess == 'y')
		{
			$this->write_htaccess();
		}

		if ($return == 'bool')
		{
			return TRUE;
		}

		ee()->session->set_flashdata('message_success', lang('blacklist_updated'));
		ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'
		.AMP.'module=blacklist'.AMP.'method=view_blacklist');

	}

	// --------------------------------------------------------------------

	/**
	 * Update Whitelisted Items
	 *
	 * @access	public
	 * @return	void
	 */
	function update_whitelist()
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

		$default = array('ip', 'agent', 'url');

		foreach ($default as $val)
		{
			if (isset($_POST[$val]))
			{
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

					if ($val == 'ip')
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
				 	'whitelisted_type' 	=> $val,
				 	'whitelisted_value'	=> $_POST[$val]
				 );

				 ee()->db->insert('whitelisted', $data);
			}
		}

		ee()->session->set_flashdata('message_success', lang('whitelist_updated'));
		ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP
		.'module=blacklist'.AMP.'method=view_whitelist');
	}

	// --------------------------------------------------------------------

	/**
	 * Download and update ExpressionEngine.com Black- or Whitelist
	 *
	 * @access	private
	 * @return	string
	 */
	function _download_update_list($listtype = "black")
	{
		$vars['cp_page_title'] = lang('blacklist_module_name'); // both black and white lists share this title

		if ( ! ee()->db->table_exists("{$listtype}listed"))
		{
			show_error(lang("ref_no_{$listtype}list_table"));
		}

		if ( ! $license = ee()->config->item('license_number'))
		{
			show_error(lang('ref_no_license'));
		}

		//  Get Current List from ExpressionEngine.com
		ee()->load->library('xmlrpc');
		ee()->xmlrpc->server('http://ping.expressionengine.com/index.php', 80);
		ee()->xmlrpc->method("ExpressionEngine.{$listtype}list");
		ee()->xmlrpc->request(array($license));

		if (ee()->xmlrpc->send_request() === FALSE)
		{
			// show the error and stop
			$vars['message'] = lang("ref_{$listtype}list_irretrievable").BR.BR.ee()->xmlrpc->display_error();
			return ee()->load->view('update', $vars, TRUE);
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
			ee()->session->set_flashdata('message_success', lang('whitelist_updated'));
			ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blacklist');
		}

		//  Using new blacklist members, clean out spam
		$new['url']		= array_diff($new['url'], $old['url']);
		$new['agent']	= array_diff($new['agent'], $old['agent']);
		$new['ip']		= array_diff($new['ip'], $old['ip']);

		$modified_channels = array();

		foreach($new as $key => $value)
		{
			sort($value);
			$name = ($key == 'url') ? 'from' : $key;

			if (count($value) > 0)
			{
				for($i=0; $i < count($value); $i++)
				{
					if ($value[$i] != '')
					{
						if (ee()->db->table_exists('referrers'))
						{
							ee()->db->like('ref_'.$name, $value[$i]);

							foreach ($white[$key] as $w_value)
							{
								ee()->db->not_like('ref_'.$name, $w_value);
							}

							ee()->db->delete('referrers');
						}
					}
				}
			}
		}

		//  Blacklist updated message

		$vars['message'] = lang('blacklist_updated');
		$vars['form_hidden']['write_htaccess'] = 'n'; // over-ridden below if needed

		if (ee()->session->userdata('group_id') == '1' && ee()->config->item('htaccess_path') === FALSE)
		{
			$vars['use_htaccess'] = TRUE;
			$vars['form_action'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blacklist'.AMP.'method=write_htaccess';
			$vars['form_hidden']['htaccess_path'] = ee()->config->item('htaccess_path');
			$vars['form_hidden']['write_htaccess'] = 'y';

		}
		else
		{
			ee()->session->set_flashdata('message_success', lang('blacklist_updated'));
			ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blacklist');
		}

		return ee()->load->view('update', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * View List
	 *
	 * @access	private
	 * @return	mixed
	 */
	function _view_list($black_or_white = 'black')
	{
		if ( ! ee()->db->table_exists("{$black_or_white}listed"))
		{
			show_error(lang("ref_no_{$black_or_white}list_table"));
		}

		$vars['cp_page_title'] =  lang("ref_view_{$black_or_white}list");

		ee()->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blacklist', lang('blacklist_module_name'));

		ee()->load->helper('form');

		$rows = array();
		$default = array('ip', 'url','agent');
		foreach ($default as $value)
		{
			$rows[$value] = '';
		}

		// Store by type with | between values
		ee()->db->order_by("{$black_or_white}listed_type", 'asc');
		$query = ee()->db->get("{$black_or_white}listed");

		if ($query->num_rows() != 0)
		{
			foreach($query->result_array() as $row)
			{
				$rows[$row["{$black_or_white}listed_type"]] = $row["{$black_or_white}listed_value"];
			}
		}

		$vars['form_action'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP."module=blacklist".AMP."method=update_{$black_or_white}list";

		//sort($rows);
		foreach($rows as $key => $value)
		{
			$vars['list_item'][$key]['name'] = $key;
			$vars['list_item'][$key]['id'] = $key;
			$vars['list_item'][$key]['value'] = str_replace('|',NL,$value);
			$vars['list_item'][$key]['class'] = 'module_textarea shun';
		}

		if (ee()->session->userdata('group_id') == '1' && ee()->config->item('htaccess_path') != '' && $black_or_white == 'black')
		{
			$vars['form_hidden']['htaccess_path'] = ee()->config->item('htaccess_path');
			$vars['write_to_htaccess'] = TRUE;
		}
		else
		{
			$vars['form_hidden']['htaccess_path'] = ''; // empty value for the view
			$vars['write_to_htaccess'] = FALSE;
		}

		return $vars;
	}

}
// END CLASS

/* End of file mcp.blacklist.php */
/* Location: ./system/expressionengine/modules/blacklist/mcp.blacklist.php */