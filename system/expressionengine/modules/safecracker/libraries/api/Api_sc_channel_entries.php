<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'libraries/api/Api_channel_entries.php';

class Api_sc_channel_entries extends Api_channel_entries
{
	/**
	 * Why? because I want to preserve fields that haven't been POSTed
	 *
	 **/
	public function _prepare_data(&$data, &$mod_data)
	{
		$this->_pre_prepare_data($data);
		parent::_prepare_data($data, $mod_data);
		$this->_post_prepare_data($data);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Pre Prepare Data
	 */
	public function _pre_prepare_data(&$data)
	{
		if ( ! $this->EE->safecracker->edit)
		{
			return;
		}
		
		foreach($this->EE->safecracker->custom_fields as $field)
		{
			if (empty($field['isset']))
			{
				//we put in a dummy value earlier, so _check_data_for_errors
				//wouldn't fail.
				//remove it from data so save() doesn't get called
				unset($data['field_id_'.$field['field_id']]);
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Post Prepare Data
	 */
	public function _post_prepare_data(&$data)
	{
		//remove unwanted keys
		foreach ($data as $key => $value)
		{
			$fields = $this->EE->db->list_fields('channel_data');
		
			if (preg_match('/^field_(id|ft|dt)/', $key) && ! in_array($key, $fields))
			{
				unset($data[$key]);
			}
		}
		
		if ( ! $this->EE->safecracker->edit)
		{
			return;
		}

		foreach($this->EE->safecracker->custom_fields as $field)
		{
			if ( ! $this->EE->safecracker->preserve_checkboxes && $field['field_type'] == 'checkboxes')
			{
				continue;
			}
			
			//preserve the original value if this field wasn't POSTed
			if (empty($field['isset']))
			{
				$data['field_id_'.$field['field_id']] = ($this->EE->safecracker->entry($field['field_name']) !== FALSE) ? $this->EE->safecracker->entry($field['field_name']) : '';
			}
		}
	}
}